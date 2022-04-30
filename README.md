# Laravel-Repositories
[![Build Status](https://scrutinizer-ci.com/g/mshamaseen/laravel-repositories/badges/build.png?b=main)](https://scrutinizer-ci.com/g/mshamaseen/laravel-repositories/build-status/main) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mshamaseen/laravel-repositories/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/mshamaseen/laravel-repositories/?branch=main) [![Code Intelligence Status](https://scrutinizer-ci.com/g/mshamaseen/laravel-repositories/badges/code-intelligence.svg?b=main)](https://scrutinizer-ci.com/code-intelligence)

A Laravel Repository generator with the best practice and tools already set and ready to be used.

## Introduction
This package aim to provide auto file generation and base classes for the repository design pattern on Laravel.

Repository pattern forces you to have repository files in mediate between controllers and models, acting like a container where data access logic and business logic are stored.

## Quick Usage

Run:

```php
php artisan generate:repository Test
```

This will generate the following files:
1. Test #The Model file
2. TestController
3. TestRepository
4. TestRequest
5. TestResource
6. TestPolicy

Read the full document on [ReadTheDoc](https://laravel-repository-pattern.readthedocs.io/en/latest/index.html).

## LICENSE
MIT License


## Versioning
We follow [Semantic Versioning](https://semver.org/)

## Roadmap
1. add the ability to disable caching from the config.
2. stop caching automatically when calling model refresh
