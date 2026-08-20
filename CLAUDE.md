# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`shamaseen/laravel-repositories` — a Laravel package (not an application) that (a) generates repository-pattern
file sets via artisan commands and (b) ships the base classes those generated files extend. Namespace
`Shamaseen\Repository\` → `src/`. Requires PHP >= 8.3 and Laravel ^13; branch `4.x` is the active line.

## Commands

**Always run tests through Docker.** Never invoke `vendor/bin/phpunit` or `composer test` directly with the
host PHP during local development — the container (`Dockerfile`, PHP 8.4) is the only supported test environment.

```bash
docker compose run --rm tests                                       # full suite (container CMD)
docker compose run --rm tests ./vendor/bin/phpunit tests/Feature/CrudTest.php   # one file
docker compose run --rm tests ./vendor/bin/phpunit --filter testSearch          # one test
docker compose run --rm tests composer test                         # purge testbench skeleton + full suite
docker compose build tests                                          # rebuild after composer.json changes
composer serve                                                      # boot the workbench app (testbench)
```

`phpunit.xml` enumerates test files explicitly in the `<testsuite>` block — `SecurityTest.php` and
`DisableCacheConfigTest.php` are **not** listed and never run under a bare `vendor/bin/phpunit`. Add a `<file>`
entry when you add a test file, or run it by path.

`phpcs.xml` exists but neither PHP_CodeSniffer nor php-cs-fixer is a declared dev dependency; there is no lint step
in CI. CI (`.github/workflows/QA.yml`) runs only phpunit on PHP 8.3 against Laravel ^13 / testbench ^11.

Docs are MkDocs (`mkdocs.yml` → `docs/`, published to ReadTheDocs). Update `docs/` when behavior changes.

## Architecture

### Generation side (`src/Commands`, `src/PathResolver.php`, `src/stubs`)

`generate:repository Path/Name` (and `ungenerate:repository`) resolve every output through **`PathResolver`**, which
is the single place path/namespace logic lives:

- `PathResolver::$configTypePathMap` maps a file *type* (`Controller`, `Model`, `Repository`, `Request`, `Resource`,
  `Collection`, `Policy`, `Test`) to its `config('repository.*_path')` key. Adding a new generated file type means
  touching this map, the config, a stub, and the option constants on `Generator`.
- Paths in config are relative to `repository.base_path` (itself relative to the project root); `--base` overrides.
  `typeNamespace()` derives the PHP namespace from the resolved filesystem path, so path config and namespaces are
  coupled by construction.
- Stubs come from `config('repository.stubs_path')` if the user published them, else `src/stubs/`. Placeholders are
  substituted in `Generator::generate()` (`{{modelName}}`, `{{namespace}}`, `{{parentClass}}`, the
  `{{*Namespace}}` set, and the conditional `{{ResourcesProperties}}` / `{{RequestProperty}}` / `{{PolicyProperty}}`).
- Generated parent classes are config-driven (`repository.controller_parent`, `model_parent`, …), so users can swap
  in their own base classes.
- Both commands `shell_exec('composer dump-autoload')` at the end and fire `RepositoryFilesGenerated` /
  `RepositoryFilesRemoved`.

### Runtime side (`src/Utility`)

Request flow: route → `Utility\Controller` → `Utility\AbstractRepository` → `Utility\Model` → `ResponseDispatcher`
→ resource/view.

- **`Controller`** overrides `callAction()` — that is where the `$requestClass` is resolved from the container (with
  the repository injected), the policy is authorized via `$policyClass`, `limit` is clamped to `$maxLimit`,
  trash flags are applied, and `ResponseDispatcher` is built. Anything depending on the request instance must go
  there, not the constructor. The seven CRUD actions are inherited; subclasses normally only set properties
  (`$resourceClass`, `$collectionClass`, `$requestClass`, `$policyClass`, view names, `$pageTitle`).
- **`ResponseDispatcher`** picks `APIResponses` or `WebResponses` based on `config('repository.responses')`
  (`api` / `web` / `both`); under `both` it follows `$request->expectsJson()`. Both implement `Interfaces\CrudResponse`
  — keep the three in sync when changing a CRUD signature.
- **`AbstractRepository`** is generic over its model (`@template TModel`) and every read builds a fresh builder via
  `getNewBuilderWithScope()`, which applies and then **clears** callables registered with `scope()` (one-shot scopes).
  Query methods chain the criteria scopes `orderByCriteria` → `searchByCriteria` → `filterByCriteria`.
  `Repositories\SoftDeletesRepository` adds restore/forceDelete/withTrash/onlyTrash on top.
- **`Models\Criteriable`** implements search/filter/sort straight from request query params. Defaults for
  searchables/filterables/sortables are `fillable - hidden`; override the `$searchables` / `$filterables` /
  `$sortables` properties (or the `set*`/`append*` scopes) to restrict. Associative entries mean relations
  (`['relationName' => ['col', …]]`). Filters accept operator arrays (`?price[gte]=10`) limited to
  `$allowedFilterOperators`. `config('repository.filter_key')` scopes filters to one query param when set.
- **`Models\CachePerRequest` + `ConnectionProxy` + `RequestCache`** implement per-request read caching: the model
  returns a `ConnectionProxy` from `getConnection()`, and `selectOne`/`select`/`scalar` route through `cacheOrNext()`
  backed by the `array` cache store (`cursor()` is *not* cached — it streams straight through). Writes
  (`insert`/`update`/`delete`/`statement`/`affectingStatement`/`unprepared`) run through `writeAndInvalidate()`, which
  flushes the **whole** bucket afterwards — entries are keyed by SQL string, so selective eviction isn't possible.
  `shouldCache()` additionally refuses to cache locking reads (`for update`, `lock in share mode`, sqlsrv hints) and
  anything read while `transactionLevel() > 0`, so a cached row can never replace a real row lock or survive a
  rollback. `config('repository.disable_cache')` kills it globally; `disableCache()`/`enableCache()`/`clearCache()`
  scopes toggle it per model instance. Note the cache is one shared bucket: `getRequestCacheKey()` returns a constant,
  so all models collide unless a model overrides it (which then splits invalidation per bucket).
- **`Utility\Request`** composes rules from `$rules` + `Http{Method}Rules()` + `{action}Rules()` (resolved from the
  route action name, and only when declared on the child class itself).
- **`Utility\Resource`/`ResourceCollection`** — collections call `toCollection()` on each item, letting a resource
  render differently in a list than alone.

## Tests

`tests/TestCase.php` (Orchestra Testbench) repoints the app base path at `tests/results/`, wipes it, copies
`src/stubs` into `tests/results/resources/stubs`, and rewrites `$fillable` → `$guarded` in the copied Model stub.
Feature tests then actually run `generate:repository`, so generated classes are loaded from `tests/results/app/`
via the `App\` PSR-4 mapping in `composer.json`. `CrudTest`/`SecurityTest` instead point `stubs_path` at
`tests/stubs/` (fixtures with real fillables/searchables) and create a `tests` table by hand.

Because generation happens inside tests, a change to a stub or to `PathResolver` will surface as a test failure in
generation *and* CRUD tests.
