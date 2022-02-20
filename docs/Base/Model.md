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

Only `fillables` are filterable/searchable by default, to override this behavior, define `$searchables` or `$filterable` properties in your model and fill it with the desired columns. 

### Relation filter/search
To filter\search base on a relation you can define the relation name as the key and their columns as the value:
```
    protected ?array $filterable = [
        'roles' => [
            'id',
            'name',        
        ]    
    ];

```

### Runtime manipulation
You can define what filterable/searchable at run time by calling these functions:

```
Model::setSearchables(['name'])
Model::setFilterables(['name']);
```

Note that these methods are not query methods, meaning that you should call them on the model instance and not on the builder instance.
