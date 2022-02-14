# Usage

## Generate files
To generate new repository files use this command:

```
php artisan generate:repository Tests/Test
```

This will generate the following files:
1. Test #The Model file
2. TestController
3. TestRepository
4. TestRequest
5. TestResource
6. TestPolicy

If you are using another architecture than the one Laravel uses (let us say you are using modular architecture) you can pass the base path to your files like this:
```
php artisan generate:repository Test --base=app/Modules
```

This will generate the files inside the app/Modules directory and will set the namespace accordingly. 

!!! note

    See the config file for more information about paths.

## Remove files
To remove repository files use the following command:
```
php artisan ungenerate:repository Tests/Test
```


## Event

Whenever this package generates new files it will emit `Shamaseen\Repository\Events\RepositoryFilesGenerated` event, so that you can hook it to generate even more files (let us says view files) or do other stuff.

For example, to generate view files with the repository files create a new event listener, register it under `RepositoryFilesGenerated` event, and add this to your handler:

```php
\FilesGenerator::stub(resource_path('stubs/view/create.blade.php'))
            ->output(resource_path('views/create.blade.php'));
```

This will generate a new file from your custom stub whenever repository files are generated.
