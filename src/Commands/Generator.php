<?php

namespace Shamaseen\Repository\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Shamaseen\Generator\Generator as GeneratorService;
use Shamaseen\Repository\Events\RepositoryFilesGenerated;
use Shamaseen\Repository\PathResolver;

/**
 * Class RepositoryGenerator.
 */
class Generator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:repository
    {name : Class (singular) for example User}
    {--base= : Base path to inject the files\folders in}
    {--f|force : force overwrite files}';

    protected $description = 'Create repository files';

    protected string $modelName;
    protected string $userPath;
    // relative to project directory
    protected string $basePath;
    private GeneratorService $generator;

    private PathResolver $pathResolver;

    /**
     * Create a new command instance.
     */
    public function __construct(GeneratorService $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle(): int
    {
        $paths = preg_split(' ([/\\\]) ', $this->argument('name'));

        if (!$paths) {
            return 'Name argument is not correct.';
        }

        $this->modelName = array_pop($paths);
        $this->userPath = implode('/', str_replace('\\', '/', $paths));
        $this->basePath = $this->option('base') ? $this->option('base') : Config::get('repository.base_path');

        $this->pathResolver = new PathResolver($this->modelName, $this->userPath, $this->basePath);

        config(['generator.base_path' => base_path($this->basePath)]);

        return $this->makeRepositoryPatternFiles();
    }

    public function makeRepositoryPatternFiles(): int
    {
        // Parent Classes
        $modelParent = Config::get('repository.model_parent');
        $repositoryParent = Config::get('repository.repository_parent');
        $controllerParent = Config::get('repository.controller_parent');
        $requestParent = Config::get('repository.request_parent');
        $resourceParent = Config::get('repository.resource_parent');
        $collectionParent = Config::get('repository.collection_parent');

        $this->generate('Controller', $controllerParent);
        $this->generate('Model', $modelParent);
        $this->generate('Request', $requestParent);
        $this->generate('Repository', $repositoryParent);
        $this->generate('Resource', $resourceParent);
        $this->generate('Collection', $collectionParent);

        RepositoryFilesGenerated::dispatch($this->basePath, $this->userPath, $this->modelName);

        $this->dumpAutoload();

        return Command::SUCCESS;
    }

    /**
     * @param string $type define which kind of files should be generated
     * @param string $parentClass
     *
     * @return bool
     */
    protected function generate(string $type, string $parentClass = ''): bool
    {
        $outputPath = $this->pathResolver->outputPath($type);

        if (!$this->option('force') && $realpath = realpath($outputPath)) {
            if (!$this->confirm('File '.$realpath.' is exist, do you want to overwrite it?')) {
                return false;
            }
        }

        $namespace = $this->pathResolver->typeNamespace($type);
        $lcModelName = Str::lower($this->modelName);
        $lcPluralModelName = Str::plural($lcModelName);
        $snackLcPluralModelName = Str::snake($this->modelName);

        $this->generator->stub($this->pathResolver->getStubPath($type))
            ->replace('{{parentClass}}', $parentClass)
            ->replace('{{modelName}}', $this->modelName)
            ->replace('{{lcModelName}}', $lcModelName)
            ->replace('{{lcPluralModelName}}', $lcPluralModelName)
            ->replace('{{snackLcPluralModelName}}', $snackLcPluralModelName)
            ->replace('{{namespace}}', $namespace)
            ->replace('{{RequestsNamespace}}', $this->pathResolver->typeNamespace('Request'))
            ->replace('{{RepositoriesNamespace}}', $this->pathResolver->typeNamespace('Repository'))
            ->replace('{{ResourcesNamespace}}', $this->pathResolver->typeNamespace('Resource'))
            ->replace('{{ModelNamespace}}', $this->pathResolver->typeNamespace('Model'))
            ->output($outputPath);

        return true;
    }

    private function dumpAutoload()
    {
        shell_exec('composer dump-autoload');
    }
}
