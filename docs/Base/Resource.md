# Resource
Eloquent resources provide granular and robust control over the JSON serialization of your models and their relationships.

whenever you create new repository files two resources files will be created, one for collections and one for an individual resource.

They act exactly like documented in [Laravel Resource](https://laravel.com/docs/eloquent-resources) The only difference is that the individual resource has a `toCollection` method which allows you to specify individual model JSON structure inside a collection.

## toCollection

By default, Laravel will read from the `toArray` method in the individual resource to structure each model JSON for both collection calls and individual model calls.

Even though that may sound confusing, because the collection resource also has a `toArray` method, but for collections call Laravel will read from the individual resource first for each individual model then read from the collection resource to make the overall structure.

to change that behavior, this package adds a `toCollection` method to the individual resource, which allows you to structure each model differently on a collections call.

meaning that, if you have the two methods defined in the individual resource, it will act like this:

```
return new Resource(User::find($id)); // will read from toArray method in the individual resource

return new ResourceCollection(User::all()); // will read from toCollection method in the individual resource then toArray method in the resource collection
```

