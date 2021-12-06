<?php

namespace Shamaseen\Repository\Tests;

use DirectoryIterator;
use FilesystemIterator;
use Shamaseen\Repository\RepositoryServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected array $configs = [
        'repository.route_path' => __DIR__ . '/results/routes/',
        'repository.resources_path' => __DIR__ . '/results/resources',
        'repository.stubs_path' => __DIR__ . '/results/resources' . '/stubs/',
        'repository.lang_path' => __DIR__ . '/results/resources/lang/',
        'repository.controllers_path' => __DIR__ . '/results/app/Http/Controllers',
        'repository.repositories_path' => __DIR__ . '/results/app/Repositories',
        'repository.models_path' => __DIR__ . '/results/app/Models',
        'repository.requests_path' => __DIR__ . '/results/app/Http/Requests',
        'repository.json_resources_path' => __DIR__ . '/results/app/Http/Resources',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->alterConfig();
        $this->makePathIfNotExist(config('repository.route_path'));
        $this->makePathIfNotExist(config('repository.resources_path'));
        $this->makePathIfNotExist(config('repository.stubs_path'));
        $this->makePathIfNotExist(config('repository.lang_path'));
        $this->makePathIfNotExist(config('repository.controllers_path'));
        $this->makePathIfNotExist(config('repository.repositories_path'));
        $this->makePathIfNotExist(config('repository.models_path'));
        $this->makePathIfNotExist(config('repository.requests_path'));
        $this->makePathIfNotExist(config('repository.json_resources_path'));

        $this->publishStubs();
    }

    protected function getPackageProviders($app): array
    {
        return [
            RepositoryServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // perform environment setup
    }

    public function makePathIfNotExist($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 775, true);
        }
    }

    /**
     * Using `vendor:publish --tag="repository-stubs"` didn't work as the test config path is not set yet
     */
    public function publishStubs()
    {
        $iterator = new FilesystemIterator(__DIR__."/../src/stubs");
        foreach ($iterator as $i) {
            /**
             * @var DirectoryIterator $i
             */
            if ($i->isFile()) {
                copy($i->getPathname(), $this->configs['repository.stubs_path']."/".$i->getFilename());
            }
        }
    }

    public function alterConfig()
    {
        foreach ($this->configs as $key => $value) {
            config()->set($key, $value);
        }
    }
}
