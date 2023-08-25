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
    public const REPOSITORY_OPTION = 'repository';
    public const CONTROLLER_OPTION = 'controller';
    public const MODEL_OPTION = 'model';
    public const RESOURCE_OPTION = 'transformer';
    public const POLICY_OPTION = 'policy';
    public const REQUEST_OPTION = 'input';
    public const TEST_OPTION = 'test';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:repository
    {name : Class (singular) for example User}
    {--base= : Base path to inject the files\folders in}
    {--f|force : force overwrite files}
    {--no-override : don\'t override any file}
    {--m|model : model only}
    {--c|controller : controller only}
    {--r|repository : repository only}
    {--t|transformer : transformers (API resources) only}
    {--p|policy : policy only}
    {--test : test only}
    {--i|input : input validation (request file) only}';

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
     */
    public function handle(): int
    {
        // if no file is specified, then generate them all
        if (!$this->option(self::REQUEST_OPTION) && !$this->option(self::CONTROLLER_OPTION)
            && !$this->option(self::REPOSITORY_OPTION) && !$this->option(self::RESOURCE_OPTION)
            && !$this->option(self::MODEL_OPTION) && !$this->option(self::POLICY_OPTION)
            && !$this->option(self::TEST_OPTION)
        ) {
            $options = $this->getFileGeneration();
            foreach ($options as $option) {
                $this->input->setOption($option, true);
            }
        }

        $paths = preg_split(' ([/\\\]) ', $this->argument('name'));

        if (!$paths) {
            $this->error('Name argument is not correct.');

            return self::FAILURE;
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

        if ($this->option(self::CONTROLLER_OPTION)) {
            $this->generate('Controller', $controllerParent);
        }

        if ($this->option(self::MODEL_OPTION)) {
            $this->generate('Model', $modelParent);
        }

        if ($this->option(self::REQUEST_OPTION)) {
            $this->generate('Request', $requestParent);
            $this->generate('Collection', $collectionParent);
        }

        if ($this->option(self::REPOSITORY_OPTION)) {
            $this->generate('Repository', $repositoryParent);
        }

        if ($this->option(self::RESOURCE_OPTION)) {
            $this->generate('Resource', $resourceParent);
        }

        if ($this->option(self::POLICY_OPTION)) {
            $this->generate('Policy');
        }

        if ($this->option(self::POLICY_OPTION)) {
            $this->generate('Test');
        }

        RepositoryFilesGenerated::dispatch($this->basePath, $this->userPath, $this->modelName);

        $this->dumpAutoload();

        return self::SUCCESS;
    }

    /**
     * @param string $type define which kind of files should be generated
     */
    protected function generate(string $type, string $parentClass = ''): bool
    {
        $outputPath = $this->pathResolver->outputPath($type);

        if (!$this->option('force') && $realpath = realpath($this->generator->absolutePath($outputPath))) {
            if ($this->option('no-override') || !$this->confirm('File '.$realpath.' exists, do you want to overwrite it?')) {
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
            ->replace('{{PoliciesNamespace}}', $this->pathResolver->typeNamespace('Policy'))
            ->replace('{{ResourcesProperties}}', $this->resourceProperty())
            ->replace('{{RequestProperty}}', $this->requestProperty())
            ->replace('{{PolicyProperty}}', $this->policyProperty())
            ->output($outputPath);

        return true;
    }

    public function resourceProperty(): string
    {
        $result = '';

        if ($this->option(self::RESOURCE_OPTION)) {
            $result .= "\n\t".'public ?string $resourceClass = '. $this->modelName .'Resource::class;'."\n";
            $result .= "\n\t".'public ?string $collectionClass = '. $this->modelName .'Collection::class;'."\n";
        }

        return $result;
    }

    public function requestProperty(): string
    {
        $result = '';

        if ($this->option(self::RESOURCE_OPTION)) {
            $result .= "\n\t".'public string $requestClass = '. $this->modelName .'Request::class;'."\n";
        }

        return $result;
    }

    public function policyProperty(): string
    {
        $result = '';

        if ($this->option(self::RESOURCE_OPTION)) {
            $result .= "\n\t".'public ?string $policyClass = '. $this->modelName .'Policy::class;'."\n";
        }

        return $result;
    }

    private function dumpAutoload(): void
    {
        shell_exec('composer dump-autoload');
    }

    public function getFileGeneration(): array
    {
        return config('repository.default_stubs');
    }

    /** @return string[] */
    public static function getDefaultFileGeneration(): array
    {
        return [
            self::REPOSITORY_OPTION,
            self::CONTROLLER_OPTION,
            self::MODEL_OPTION,
            self::RESOURCE_OPTION,
            self::POLICY_OPTION,
            self::REQUEST_OPTION,

        ];
    }
}
