# Release Notes

## 4.3.1 - 2026-11-12

### Fixed

The per-request query cache could serve one database's results to another. Cache keys were the resolved
SQL string alone, so when a connection was repointed at a different database -- multi-database tenancy
(stancl/tenancy and similar) does this on every tenant switch, as does changing config and calling
`DB::purge()` -- identical SQL against the new database returned the previous database's cached rows. The same happened
across two different connection names: identical SQL on a second connection returned the first one's rows.
Before 4.3.0 this was hidden because tenancy's cache bootstrapper happened to reset `Cache::store('array')`;
the scoped `RequestCacheStore` introduced in 4.3.0 is not reset on a tenant switch.

Keys now include the database the query actually runs against: connection name, host, port, database name
(as reported by `getDatabaseName()`, so `setDatabaseName()` is covered) and Postgres `search_path` /
`schema`. Apps no longer need a tenancy bootstrapper that flushes the cache. Invalidation is unchanged: a
write still clears the whole bucket, other databases' entries included.

Switching database with a raw statement on an open connection (`USE other_db`, `SET search_path ...`)
still can't be detected; call `clearCache()` after it.

**Note:** the key strings stored in the bucket changed. This only matters to code that reads raw keys from
`RequestCacheStore` or `RequestCache::all()`; the `RequestCache` / `RequestCacheStore` APIs are unchanged.

## 4.3.0 - 2026-08-20

### Fixed

The per-request query cache is now actually bounded by a request. Entries moved from
`Cache::store('array')` -- which hangs off the `CacheManager` singleton and is never reset -- to a
scoped `RequestCacheStore` binding, which Laravel forgets between queue jobs and between Octane
requests. Under `queue:work` cached rows previously survived from one job to the next for the life
of the worker process, producing stale reads with no bound on staleness and a cache that only grew.

**Possible breaking change:** code reading the package's entries out of `Cache::store('array')`
under the `repository-cache` key must resolve `Shamaseen\Repository\Utility\Models\RequestCacheStore`
from the container instead. The `RequestCache` public API (`get`/`set`/`delete`/`clear`/`all`) and the
`disableCache()` / `enableCache()` / `clearCache()` / `getRequestCacheKey()` model API are unchanged.

## 1.1.4

### Changed

Add $sortables to Criteriable trait
