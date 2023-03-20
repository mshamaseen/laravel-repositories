# Model

Models are the middleware between the application and the database, you should define all the relations, getter/setter attributes, and anything related to the database in there.

## Request Cache

Request cache allow you to cache queries results for the current request, it uses the **array** driver to cache the results per each request.

You can disable caching for the current model by setting the `$requestCacheEnabled` property to false.

### Runtime cache manipulation

You can disable\enable\clear the cache on real time for specific builder by calling these methods:

```
Model::DisableCache();
Model::EnableCache();
Model::ClearCache();
```

These methods are chainable, meaning that you can call them while building your query:
```
Model::DisableCache()->where('name', 'Mohammad')->get();
```

## Criteria Scopes

Most of the time you want to search your query base on the front-end criteria, we have built a common used methods to filter/search/order by criteria and add them as a [query scopes](https://laravel.com/docs/9.x/eloquent#query-scopes).


These methods are:
```
Model::filterByCriteria($criteria);
Model::searchByCriteria($criteria);
Model::orderByCriteria($criteria);
```

Only `fillables` are filterable/searchable by default, to override this behavior, define `$searchables` or `$filterables` or `$sortables` properties in your model and fill it with the desired columns.

### Fulltext search

If you want to use fulltext search instead of `like` search, you should define your fulltext columns in the `fulltextSearch` array in your model:

```
    protected ?array $fulltextSearch = [
        [
            'firstname', 'lastname',
        ],
    ];
```

Each array inside `$fulltextSearch` represent the fulltext indexes that will be used inside the `match` query.

You may search in a relationship that has a fulltext search index.

```
    protected ?array $fulltextSearch = [
        'posts' => [
            'content'
        ]
    ];
```

Although when searching in a relationship you may only search at one index or composite index, this means the following wouldn't work

```
    protected ?array $fulltextSearch = [
        'posts' => [
            ['content'],
            ['exerpt'],
        ]
    ];
```

Both syntaxes may be combined to search locally and in relationships.

```
    protected ?array $fulltextSearch = [
        [
            'firstname', 'lastname',
        ],
        // can't search in different indexes.
        'parents' => [
            'firstname', 'lastname',
        ]
    ];
```

In simple and most common scenarios [MYSQL](https://dev.mysql.com/doc/refman/8.0/en/fulltext-natural-language.html) will provide an order based on the relevance of the results of full text search results, for more complex queries use the `relevance` parameter in `searchByCriteria` scope.

### Relation filter/search
To filter\search base on a relation you can define the relation name as the key and their columns as the value:
```
    protected ?array $filterables = [
        'roles' => [
            'id',
            'name',
        ]
    ];

```

### Runtime manipulation
You can define what filterable/searchable/sortable at run time by calling these functions:

```
Model::setSearchables(['name'])
Model::setFilterables(['name']);
Model::setSortables(['name']);
```

Or append to the array that already defined in the model:
```
Model::appendSearchables(['name'])
Model::appendFilterables(['name']);
Model::appendSortables(['name']);
```
