# Model

`Shamaseen\Repository\Utility\Model` is the base Eloquent model every generated model extends. It composes two traits that add per-request caching and criteria-driven query scopes to every model in your application.

```php
use Shamaseen\Repository\Utility\Model;

class Post extends Model
{
    protected $fillable = ['title', 'body', 'status'];
}
```

---

## CachePerRequest

The `CachePerRequest` trait caches SELECT query results for the lifetime of the current request using Laravel's built-in `array` cache driver. Identical queries (same SQL + bindings) executed more than once within the same request are served from memory instead of hitting the database again.

It is enabled by default on every model extending `Shamaseen\Repository\Utility\Model`.

### How it works

The trait replaces the model's database connection with a thin `ConnectionProxy`. Every `select` / `selectOne` / `scalar` call builds a cache key from the fully-resolved SQL string. On a cache hit the result is returned immediately; on a miss the real query runs and the result is stored before being returned.

### What invalidates the cache

**Every write through the model's connection clears the entire cache.** `insert`, `update`, `delete`, `statement`, `affectingStatement` and `unprepared` all flush it once the write completes, so a read after a write always reflects the new state:

```php
$account = Account::find(1);                    // reads 2500, cached
Account::where('id', 1)->update(['balance' => 999]);  // cache flushed
$account = Account::find(1);                    // reads 999 from the database
```

Invalidation is deliberately coarse. Entries are keyed by SQL string, so there is no reliable way to know which cached reads a given write affected — dropping all of them is the only conservative option. In a read-heavy request (a policy, a controller and a resource all fetching the same row) the cache still does its job; in a write-heavy one it effectively turns itself off.

### What is never cached

Some reads are always sent to the database, regardless of the flags below:

| Query | Why |
|---|---|
| Locking reads — `lockForUpdate()`, `sharedLock()`, or any SQL containing `FOR UPDATE`, `FOR SHARE`, `LOCK IN SHARE MODE`, or SQL Server's `UPDLOCK` / `HOLDLOCK` / `ROWLOCK` hints | A locking read served from cache never reaches the database, so the row lock would never be taken |
| Any read while `DB::transactionLevel() > 0` | A value cached inside a transaction would survive a rollback, leaving the cache asserting a state that was never committed |
| `cursor()` | Streams straight from the connection by design |

### Known limitations

- **Writes that bypass the model are invisible to the cache.** A raw `DB::table('accounts')->update(...)`, a raw PDO statement, or a database trigger does not go through the proxy and therefore does not invalidate anything. Route writes through the model, or call `clearCache()` yourself.
- **Another process writing the same row cannot invalidate your cache.** The cache is per-process and lives for the request; it makes no cross-process guarantees.
- **Overriding `getRequestCacheKey()` splits the cache into separate buckets.** A write made through one model then only flushes that model's bucket. Only override it if you understand that consequence.

### Disabling cache globally

Set `disable_cache` to `true` in `config/repository.php` to turn off caching for every model. This flag cannot be overridden at runtime.

```php
// config/repository.php
'disable_cache' => true,
```

> **Note:** if you disable the cache in your test suite, none of the caching behaviour above is exercised by your tests while it stays fully active in production. Consider leaving it on and disabling it only for the tests that need determinism.

### Disabling cache per model

Set the `$requestCacheEnabled` property to `false` on a specific model to opt it out of caching entirely.

```php
class AuditLog extends Model
{
    public bool $requestCacheEnabled = false;
}
```

### Runtime cache manipulation

Three chainable query scopes let you control caching on a per-query basis:

| Scope | Effect |
|---|---|
| `disableCache()` | Turns off caching for subsequent queries on this model instance |
| `enableCache()` | Turns caching back on |
| `clearCache()` | Wipes the in-memory cache bucket for this model |

```php
// Run a query without using or storing the cache
$posts = Post::disableCache()->where('status', 'draft')->get();

// Clear stale cache entries then re-query
$posts = Post::clearCache()->where('status', 'published')->get();

// Chain with any other builder methods
Post::disableCache()->where('user_id', $userId)->orderBy('created_at')->get();
```

> **Note:** `disableCache()` only bypasses the cache — it neither evicts the existing entry nor stores the fresh result. Use `clearCache()` when you want the stale entry gone.

> **Note:** `refresh()` on a model instance automatically bypasses the cache so the reloaded attributes always reflect the current database state.

---

## Criteriable

The `Criteriable` trait provides three Eloquent query scopes that translate an associative `$criteria` array — typically built from HTTP request parameters — into `WHERE`, `LIKE`, and `ORDER BY` clauses.

```php
$criteria = $request->all(); // or $request->validated()

Post::filterByCriteria($criteria)
    ->searchByCriteria($criteria)
    ->orderByCriteria($criteria)
    ->paginate();
```

### Filterable, Searchable, and Sortable columns

By default all `$fillable` columns that are not in `$hidden` are filterable, searchable, and sortable. Override this for any or all three by declaring the corresponding property on your model:

```php
class Post extends Model
{
    protected $fillable = ['title', 'body', 'status', 'published_at'];
    protected $hidden   = ['body'];

    // Only these columns can be filtered
    protected ?array $filterables = ['status', 'published_at'];

    // Only these columns are searched with LIKE
    protected ?array $searchables = ['title'];

    // Only these columns are accepted as sort keys
    protected ?array $sortables = ['title', 'published_at'];
}
```

---

### scopeSearchByCriteria — `LIKE` search

Looks for a `search` key in `$criteria` and applies `WHERE column LIKE '%value%'` across all searchable columns.

**Criteria key:** `search`

```php
// GET /posts?search=laravel
$criteria = ['search' => 'laravel'];

Post::searchByCriteria($criteria)->get();
// → WHERE (title LIKE '%laravel%')
```

#### Searching in a relationship

Add the relation name as the key and its columns as the value in `$searchables`:

```php
protected ?array $searchables = [
    'title',
    'author' => ['name', 'bio'], // searches inside the "author" relation
];
```

```php
$criteria = ['search' => 'john'];

Post::searchByCriteria($criteria)->get();
// → WHERE (title LIKE '%john%'
//      OR EXISTS (SELECT * FROM authors WHERE posts.author_id = authors.id
//                 AND (name LIKE '%john%' OR bio LIKE '%john%')))
```

#### Full-text search

For MySQL/MariaDB full-text indexes declare `$fulltextSearch` on your model. Each entry in the array becomes a separate `MATCH … AGAINST` clause.

```php
// Local full-text index on (firstname, lastname)
protected ?array $fulltextSearch = [
    ['firstname', 'lastname'],
];
```

```php
$criteria = ['search' => 'Jane Doe'];

User::searchByCriteria($criteria)->get();
// → WHERE (MATCH(firstname, lastname) AGAINST('Jane Doe'))
```

Full-text search in a relationship:

```php
protected ?array $fulltextSearch = [
    'posts' => ['title', 'body'],
];
```

```php
User::searchByCriteria(['search' => 'eloquent'])->get();
// → WHERE EXISTS (SELECT * FROM posts WHERE …
//        AND MATCH(title, body) AGAINST('eloquent'))
```

Both syntaxes can be combined:

```php
protected ?array $fulltextSearch = [
    ['firstname', 'lastname'],      // local index
    'posts' => ['title', 'body'],   // relation index
];
```

> **Limitation:** A relation may only have one full-text index entry (a single flat array of columns). Multiple index arrays for the same relation are not supported.

---

### scopeFilterByCriteria — exact / operator filters

Applies `WHERE column = value` for each filterable column found in `$criteria`.

**Criteria keys:** the column names themselves (or nested under a `filter_key` — see [Filter Key](#filter-key)).

#### Simple (equality) filter

```php
// GET /posts?status=published
$criteria = ['status' => 'published'];

Post::filterByCriteria($criteria)->get();
// → WHERE status = 'published'
```

Multiple filters are combined with `AND`:

```php
$criteria = ['status' => 'published', 'published_at' => '2024-01-01'];

Post::filterByCriteria($criteria)->get();
// → WHERE status = 'published' AND published_at = '2024-01-01'
```

#### Operator filters

Pass an associative array as the column's value to use a comparison operator. Supported operators:

| Key | SQL operator |
|-----|-------------|
| `eq` | `=` |
| `lt` | `<` |
| `gt` | `>` |
| `lte` | `<=` |
| `gte` | `>=` |
| `ne` | `!=` |

```php
// GET /posts?published_at[gte]=2024-01-01&published_at[lt]=2025-01-01
$criteria = [
    'published_at' => [
        'gte' => '2024-01-01',
        'lt'  => '2025-01-01',
    ],
];

Post::filterByCriteria($criteria)->get();
// → WHERE published_at >= '2024-01-01' AND published_at < '2025-01-01'
```

Multiple operators on the same column are all applied:

```php
$criteria = [
    'views' => ['gte' => 100, 'lte' => 500],
];

Post::filterByCriteria($criteria)->get();
// → WHERE views >= 100 AND views <= 500
```

#### Relation filter

Add the relation name as the key and its filterable columns as the value in `$filterables`. The criteria array must then nest the filter values under the relation name:

```php
protected ?array $filterables = [
    'tags' => ['name', 'slug'],
];
```

```php
// GET /posts?tags[name]=php
$criteria = [
    'tags' => ['name' => 'php'],
];

Post::filterByCriteria($criteria)->get();
// → WHERE EXISTS (SELECT * FROM tags WHERE … AND name = 'php')
```

#### Filter Key

By default, every key in `$criteria` is treated as a potential filter. If you want filters to live under a dedicated namespace (e.g. to keep them separate from pagination and search parameters), set `filter_key` in `config/repository.php`:

```php
// config/repository.php
'filter_key' => 'filters',
```

```php
// GET /posts?filters[status]=published&page=2
$criteria = [
    'filters' => ['status' => 'published'],
    'page'    => 2,          // ignored by filterByCriteria
    'search'  => 'laravel',  // used by searchByCriteria
];

Post::filterByCriteria($criteria)->get();
// → WHERE status = 'published'
```

---

### scopeOrderByCriteria — sorting

Looks for `order` (column) and `direction` (`asc` / `desc`, defaults to `desc`) keys in `$criteria`. The column must be in the model's sortable list or it is silently ignored.

**Criteria keys:** `order`, `direction`

```php
// GET /posts?order=published_at&direction=asc
$criteria = ['order' => 'published_at', 'direction' => 'asc'];

Post::orderByCriteria($criteria)->get();
// → ORDER BY published_at ASC
```

```php
// Direction defaults to desc when omitted
Post::orderByCriteria(['order' => 'title'])->get();
// → ORDER BY title DESC
```

---

### Combining all three scopes

```php
$criteria = $request->all();
// e.g. ?search=laravel&status=published&views[gte]=100&order=published_at&direction=asc

Post::searchByCriteria($criteria)
    ->filterByCriteria($criteria)
    ->orderByCriteria($criteria)
    ->paginate(15);
```

---

### Runtime manipulation

Override or extend filterable / searchable / sortable columns at runtime without changing the model definition:

```php
// Replace the list entirely
Post::setSearchables(['title', 'excerpt'])->searchByCriteria($criteria)->get();
Post::setFilterables(['status'])->filterByCriteria($criteria)->get();
Post::setSortables(['title', 'created_at'])->orderByCriteria($criteria)->get();

// Append to the existing list
Post::appendSearchables(['summary'])->searchByCriteria($criteria)->get();
Post::appendFilterables(['category_id'])->filterByCriteria($criteria)->get();
Post::appendSortables(['views'])->orderByCriteria($criteria)->get();
```

These scopes are chainable with any other builder call:

```php
Post::setFilterables(['status', 'category_id'])
    ->filterByCriteria($criteria)
    ->where('user_id', auth()->id())
    ->latest()
    ->get();
```
