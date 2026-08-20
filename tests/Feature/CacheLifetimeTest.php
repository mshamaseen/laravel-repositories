<?php

namespace Shamaseen\Repository\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;
use Shamaseen\Repository\Tests\TestCase;
use Shamaseen\Repository\Utility\Model;
use Shamaseen\Repository\Utility\Models\RequestCacheStore;

class Ledger extends Model
{
    protected $table = 'ledgers';
    protected $guarded = [];
    public $timestamps = false;
}

/**
 * The per-request cache must actually be bounded by a request.
 *
 * Storing entries in `Cache::store('array')` did not do that: `CacheManager` is a
 * singleton and memoizes its stores, so in a `queue:work` daemon cached rows lived
 * for the life of the worker process. These tests pin the boundary down by running
 * the real closure the framework hands the queue worker between two jobs.
 */
class CacheLifetimeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['repository.disable_cache' => false]);

        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->integer('balance');
        });

        Ledger::query()->insert(['id' => 1, 'balance' => 2500]);
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('ledgers');
        parent::tearDown();
    }

    /**
     * Run the *actual* closure `Illuminate\Queue\QueueServiceProvider` hands the
     * worker -- everything the framework does between two jobs, no more and no less.
     */
    private function runWorkerResetScope(): void
    {
        $worker = $this->app->make('queue.worker');
        $this->assertInstanceOf(Worker::class, $worker);

        $resetScope = (new ReflectionProperty(Worker::class, 'resetScope'))->getValue($worker);
        $this->assertIsCallable($resetScope, 'the framework registers a resetScope callable');

        $resetScope();
    }

    public function testCacheEntriesDoNotSurviveTheQueueWorkersResetScope(): void
    {
        Ledger::query()->find(1);

        $this->assertNotEmpty((new Ledger())->requestCache->all(), 'sanity: the read was cached');

        $this->runWorkerResetScope();

        $this->assertSame(
            [],
            (new Ledger())->requestCache->all(),
            'job 2 must not inherit job 1 cached rows'
        );
    }

    public function testTheStoreIsRebuiltBetweenJobs(): void
    {
        $before = $this->app->make(RequestCacheStore::class);

        $this->runWorkerResetScope();

        $after = $this->app->make(RequestCacheStore::class);

        $this->assertNotSame($before, $after, 'a scoped binding is forgotten between jobs');
    }

    public function testReadIsFreshAfterAnOutOfBandWriteAcrossAJobBoundary(): void
    {
        $this->assertSame(2500, (int) Ledger::query()->find(1)->balance);

        // A write that does not go through the proxy: another process, a raw
        // DB::table() call, or simply a different model class.
        DB::table('ledgers')->where('id', 1)->update(['balance' => 10]);

        $this->runWorkerResetScope();

        $this->assertSame(
            10,
            (int) Ledger::query()->find(1)->balance,
            'staleness must end at the job boundary, not at worker restart'
        );
    }

    public function testCacheDoesNotAccumulateAcrossJobs(): void
    {
        $model = new Ledger();

        for ($i = 0; $i < 50; ++$i) {
            Ledger::query()->where('balance', $i)->get();
        }

        $this->assertCount(50, $model->requestCache->all(), 'sanity: each distinct SQL is an entry');

        $this->runWorkerResetScope();

        $this->assertCount(
            0,
            $model->requestCache->all(),
            'a long-lived worker must not grow an unbounded cache'
        );
    }

    public function testCachingStillWorksWithinASingleJob(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        Ledger::query()->find(1);
        Ledger::query()->find(1);

        $selects = array_filter($queries, fn ($sql) => str_starts_with($sql, 'select'));

        $this->assertCount(1, $selects, 'the cache still caches inside one request');
    }

    public function testAModelInstanceThatOutlivesAJobReadsTheNewScopesStore(): void
    {
        $model = new Ledger();
        Ledger::query()->find(1);

        $this->runWorkerResetScope();

        // The same PHP object, held across the boundary, must not still see job 1
        // entries -- RequestCache resolves the store per call rather than holding it.
        $this->assertSame([], $model->requestCache->all());
    }

    public function testStoreSelfRegistersWhenTheProviderHasNotBound(): void
    {
        // A model used without the package's service provider (or after something
        // rebuilt the container) must still get a working, scoped store.
        $this->app->forgetInstance(RequestCacheStore::class);
        $this->app->offsetUnset(RequestCacheStore::class);

        $this->assertFalse($this->app->bound(RequestCacheStore::class), 'sanity: binding is gone');

        Ledger::query()->find(1);

        $this->assertNotEmpty((new Ledger())->requestCache->all(), 'the store rebound itself');

        $this->runWorkerResetScope();

        $this->assertSame([], (new Ledger())->requestCache->all(), 'and rebound as scoped, not singleton');
    }
}
