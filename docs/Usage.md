# Usage

### Generate files
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

### Remove files
To remove repository files use the following command:
```
php artisan ungenerate:repository Tests/Test
```
