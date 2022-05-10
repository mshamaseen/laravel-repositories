#Installation

Via composer:
```composer
composer require shamaseen/laravel-repositories
```


**Optional:** to publish the repository config file
```
php artisan vendor:publish --tag=repository
```

**Optional:** to publish the stubs files
```
php artisan vendor:publish --tag=repository-stubs --force
```

!!! note "  Forcing the stub files"

    Unless you have made your own changes on the stub files, you should always force the publish to get the latest version
