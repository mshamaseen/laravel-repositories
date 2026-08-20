# Release Notes

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
