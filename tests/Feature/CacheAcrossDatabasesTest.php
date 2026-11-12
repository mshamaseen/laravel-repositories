<?php

namespace Shamaseen\Repository\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Shamaseen\Repository\Tests\TestCase;
use Shamaseen\Repository\Utility\Model;

class Invoice extends Model
{
    protected $connection = 'tenant';
    protected $table = 'invoices';
    protected $guarded = [];
    public $timestamps = false;
}

/**
 * The per-request cache must never serve one database's rows to another.
 *
 * Multi-database tenancy (stancl/tenancy and friends) keeps one connection name and
 * repoints it at a different database per tenant, so identical SQL reaches different
 * data. These tests use two *file-based* SQLite databases: two `:memory:` databases
 * both report `:memory:` as their name and could not be told apart by anything.
 *
 * Every read runs outside a transaction -- `shouldCache()` skips caching inside one,
 * which would make these tests pass without proving anything.
 */
class CacheAcrossDatabasesTest extends TestCase
{
    private string $databaseA;
    private string $databaseB;

    public function setUp(): void
    {
        parent::setUp();

        config(['repository.disable_cache' => false]);

        $this->databaseA = $this->makeDatabase(['A-1']);
        $this->databaseB = $this->makeDatabase(['B-1', 'B-2']);

        $this->pointConnection('tenant', $this->databaseA);
        $this->pointConnection('tenant_a', $this->databaseA);
        $this->pointConnection('tenant_b', $this->databaseB);
    }

    public function tearDown(): void
    {
        foreach (['tenant', 'tenant_a', 'tenant_b'] as $name) {
            DB::purge($name);
        }

        foreach ([$this->databaseA ?? null, $this->databaseB ?? null] as $path) {
            if ($path !== null && file_exists($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    /**
     * Create a SQLite file holding an `invoices` table with the given numbers.
     */
    private function makeDatabase(array $numbers): string
    {
        $path = tempnam(sys_get_temp_dir(), 'repository-cache-').'.sqlite';
        touch($path);

        $name = 'setup_'.md5($path);
        $this->pointConnection($name, $path);

        Schema::connection($name)->create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number');
        });

        DB::connection($name)->table('invoices')->insert(
            array_map(fn ($number) => ['number' => $number], $numbers)
        );

        DB::purge($name);

        return $path;
    }

    private function pointConnection(string $name, string $path): void
    {
        config(["database.connections.$name" => [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        DB::purge($name);
    }

    /**
     * @return string[] the SQL of every query that actually reached the database
     */
    private function recordQueries(callable $callback): array
    {
        $queries = [];

        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $callback();

        return $queries;
    }

    private function numbers(?string $connection = null): array
    {
        $query = $connection === null ? Invoice::query() : Invoice::on($connection);

        return $query->orderBy('id')->pluck('number')->all();
    }

    public function testRepointingAConnectionDoesNotServeThePreviousDatabasesResults(): void
    {
        $this->assertSame(['A-1'], $this->numbers(), 'sanity: tenant starts on database A');

        // what a tenancy bootstrapper does on a tenant switch
        $this->pointConnection('tenant', $this->databaseB);

        $this->assertSame(['B-1', 'B-2'], $this->numbers(), 'database B must not get database A\'s cached rows');
    }

    public function testTwoConnectionsOnTwoDatabasesDoNotShareEntries(): void
    {
        $this->assertSame(['A-1'], $this->numbers('tenant_a'));
        $this->assertSame(['B-1', 'B-2'], $this->numbers('tenant_b'));
    }

    public function testSameConnectionAndDatabaseIsStillACacheHit(): void
    {
        $queries = $this->recordQueries(function () {
            $this->numbers();
            $this->numbers();
        });

        $selects = array_filter($queries, fn ($sql) => str_starts_with($sql, 'select'));

        $this->assertCount(1, $selects, 'the second identical read should not reach the database');
    }
}
