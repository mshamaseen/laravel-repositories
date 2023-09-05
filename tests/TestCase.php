<?php

namespace Shamaseen\Repository\Tests;

use DirectoryIterator;
use FilesystemIterator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shamaseen\Repository\RepositoryServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected string $databaseName = 'tests';
    protected string $modelName = 'Test';
    protected string $userPath = 'Tests';

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

        $this->app->setBasePath(realpath(__DIR__).'/results');
        $this->deletePathContentsIfExists(realpath(__DIR__).'/results');
        $this->makePathIfNotExist(realpath(__DIR__).'/results/app');
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

    public function makePathIfNotExist(string $path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    public function deletePathContentsIfExists($dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file[0] != '.') {
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    $this->deletePathContentsIfExists($path); // Recursively delete subdirectories
                } else {
                    unlink($path); // Delete files
                }
            }
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
        $path = base_path('resources/stubs/Model.stub');
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

    public function createDatabase(): void
    {
        Schema::create($this->databaseName, function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->string('type');
            $blueprint->timestamps();
        });
    }

    public function dropDatabase(): void
    {
        Schema::drop($this->databaseName);
    }
}
