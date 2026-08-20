<?php

namespace Shamaseen\Repository\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use RuntimeException;
use Shamaseen\Repository\Tests\TestCase;
use Shamaseen\Repository\Utility\Model;
use Shamaseen\Repository\Utility\Models\ConnectionProxy;

class Account extends Model
{
    protected $table = 'accounts';
    protected $guarded = [];
    public $timestamps = false;
}

/**
 * Regression tests for the per-request query cache.
 *
 * These deliberately run with `repository.disable_cache => false` -- the rest of the
 * suite disables the cache, so without this file the whole caching layer is untested.
 */
class CachePerRequestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['repository.disable_cache' => false]);

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('balance');
        });

        Account::query()->insert(['id' => 1, 'balance' => 2500]);
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('accounts');
        parent::tearDown();
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

    public function testRepeatedIdenticalReadIsServedFromCache(): void
    {
        $queries = $this->recordQueries(function () {
            Account::query()->find(1);
            Account::query()->find(1);
        });

        $selects = array_filter($queries, fn ($sql) => str_starts_with($sql, 'select'));

        $this->assertCount(1, $selects, 'the second identical read should not reach the database');
    }

    public function testEmptyResultIsCachedToo(): void
    {
        $queries = $this->recordQueries(function () {
            Account::query()->where('id', 999)->first();
            Account::query()->where('id', 999)->first();
        });

        $selects = array_filter($queries, fn ($sql) => str_starts_with($sql, 'select'));

        $this->assertCount(1, $selects, 'a cached empty result should still count as a hit');
    }

    public function testWriteInvalidatesCachedRead(): void
    {
        $this->assertSame(2500, (int) Account::query()->find(1)->balance);

        Account::query()->where('id', 1)->update(['balance' => 999]);

        $this->assertSame(999, (int) Account::query()->find(1)->balance);
    }

    public function testReadModifyWriteDoesNotLoseUpdates(): void
    {
        for ($i = 0; $i < 2; ++$i) {
            $account = Account::query()->lockForUpdate()->find(1);
            $account->balance -= 300;
            $account->save();
        }

        $this->assertSame(1900, (int) DB::table('accounts')->where('id', 1)->value('balance'));
    }

    public function testRolledBackWriteDoesNotPoisonCache(): void
    {
        try {
            DB::transaction(function () {
                Account::query()->where('id', 1)->update(['balance' => 100]);
                Account::query()->find(1); // must not be cached: it is never committed
                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(2500, (int) Account::query()->find(1)->balance);
    }

    public function testReadsInsideATransactionAreNotCached(): void
    {
        $queries = $this->recordQueries(function () {
            DB::transaction(function () {
                Account::query()->find(1);
                Account::query()->find(1);
            });
        });

        $selects = array_filter($queries, fn ($sql) => str_starts_with($sql, 'select'));

        $this->assertCount(2, $selects, 'reads inside a transaction must always hit the database');
    }

    public function testScalarDoesNotRecurse(): void
    {
        $balance = (new Account())->getConnection()
            ->scalar('select balance from accounts where id = ?', [1]);

        $this->assertSame(2500, (int) $balance);
    }

    #[DataProvider('lockingQueries')]
    public function testLockingReadsAreNeverCached(string $sql, bool $expected): void
    {
        $method = new ReflectionMethod(ConnectionProxy::class, 'queryTakesLock');

        $this->assertSame($expected, $method->invoke((new Account())->getConnection(), $sql), $sql);
    }

    public static function lockingQueries(): array
    {
        return [
            'plain select' => ['select * from accounts where id = ?', false],
            'mysql/pgsql for update' => ['select * from accounts where id = ? for update', true],
            'mysql 5.7 shared lock' => ['select * from accounts where id = ? lock in share mode', true],
            'mysql 8 / pgsql for share' => ['select * from accounts where id = ? for share', true],
            'pgsql for no key update' => ['select * from accounts where id = ? for no key update', true],
            'pgsql for key share' => ['select * from accounts where id = ? for key share', true],
            'sqlsrv hints' => ['select * from [accounts] with(rowlock,updlock,holdlock) where [id] = ?', true],
            'column named "for_update"' => ['select for_update from accounts where id = ?', false],
        ];
    }
}
