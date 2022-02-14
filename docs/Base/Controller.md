# Controller

The main responsibility of the controller is to receive the request and return a response, the controller should NEVER connect to the model directly or make DB queries.

Controller can return either web responses, API responses or both depending on your configuration on the repository.php config file, by default it will return `both` responses, meaning that it will depend on the `Content-type` attached in the request header to decide the response type.

Some notes about controllers:
1. Never call the model directly from the controller.
2. Controller methods should always return responses as a type.
3. It is ok to inject multiple repositories into one controller, and call multiple repositories in the same method.

## Web
You should define the following properties to allow web CRUD responses:
```
    // Can be either a route name or a URL.
    public string $routeIndex = '';
    public string $createRoute = '';

    public string $viewIndex = '';
    public string $viewCreate = '';
    public string $viewEdit = '';
    public string $viewShow = '';

```

They are self-explanatory.

## API
 
You need to define the following properties to allow API CRUD responses:

```
    public ?string $resourceClass;
    public ?string $collectionClass;
```

Feel free to go through the Controller properties, they are self-explanatory.
