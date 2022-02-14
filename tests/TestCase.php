<?php

namespace Shamaseen\Repository\Tests;

use DirectoryIterator;
use FilesystemIterator;
use Shamaseen\Repository\RepositoryServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected array $configs = [
        'repository.base_path' => 'app',
        'repository.stubs_path' => __DIR__.'/results/resources/stubs',
        'repository.lang_path' => __DIR__.'/results/resources/lang',
        'repository.controllers_path' => 'Http/Controllers',
        'repository.repositories_path' => 'Repositories',
        'repository.models_path' => 'Models',
        'repository.requests_path' => 'Http/Requests',
        'repository.json_resources_path' => 'Http/Resources',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->app->setBasePath(realpath(__DIR__.'/results'));
        $this->alterConfig();
        $this->makePathIfNotExist(config('repository.stubs_path'));
        $this->makePathIfNotExist(config('repository.lang_path'));
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
     * Using `vendor:publish --tag="repository-stubs"` didn't work as the test config path is not set yet.
     */
    public function publishStubs()
    {
        $iterator = new FilesystemIterator(__DIR__.'/../src/stubs');
        foreach ($iterator as $i) {
            /**
             * @var DirectoryIterator $i
             */
            if ($i->isFile()) {
                copy($i->getPathname(), $this->configs['repository.stubs_path'].'/'.$i->getFilename());
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
