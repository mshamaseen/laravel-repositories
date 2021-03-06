<?php

namespace Shamaseen\Repository\Tests;

use App\Models\Tests\Test;
use DirectoryIterator;
use FilesystemIterator;
use Shamaseen\Repository\RepositoryServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    // Shamaseen\Repository\Tests\results\app
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
            mkdir($path, 0775, true);
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

        // change fillable to guarded for the next test.
        $path = 'tests/results/resources/stubs/Model.stub';
        $modelContent = file_get_contents($path);
        $modelContent = str_replace('$fillable', '$guarded', $modelContent);
        file_put_contents($path, $modelContent);
    }

    public function alterConfig()
    {
        foreach ($this->configs as $key => $value) {
            config()->set($key, $value);
        }
    }
}
