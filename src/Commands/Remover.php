<?php

namespace Shamaseen\Repository\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Shamaseen\Generator\Ungenerator;
use Shamaseen\Repository\PathResolver;

class Remover extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:repository
    {name : Class (singular) for example User} {--only-view}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove repository files';

    /**
     * The path entered to set the files in.
     *
     * @var string
     */
    protected string $path;

    /**
     * The entered paths by the user
     *
     * @var array
     */
    protected array $paths;

    /**
     *
     * @var string
     */
    protected string $modelName;
    private Ungenerator $ungenerator;
    private PathResolver $pathResolver;

    /**
     * Create a new command instance.
     */
    public function __construct(Ungenerator $ungenerator, PathResolver $pathResolver)
    {
        parent::__construct();
        $this->ungenerator = $ungenerator;
        $this->pathResolver = $pathResolver;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->paths = preg_split(' ([/\\\]) ', $this->argument('name'));

        if (!$this->paths) {
            return 'Name argument is not correct.';
        }

        $this->modelName = $this->paths[count($this->paths) - 1];

        unset($this->paths[count($this->paths) - 1]);
        $this->path = implode('/', str_replace('\\', '/', $this->paths));

        // Configure the generator
        config(['generator.base_path' => '']);

        if (!$this->confirm('This will delete ' . $this->modelName . ' files and folder, Do you want to continue ?')) {
            return false;
        }

        // Paths
        $controller = Config::get('repository.controllers_path');
        $model = Config::get('repository.models_path');
        $repository = Config::get('repository.repositories_path');
        $request = Config::get('repository.requests_path');
        $resource = Config::get('repository.json_resources_path');

        $this->remove('Controller', $controller);
        $this->remove('Model', $model);
        $this->remove('Request', $request);
        $this->remove('Repository', $repository);
        $this->remove('Resource', $resource);
        return true;
    }

    public function remove($type, $typePath): bool
    {
        $fileToDelete = $this->pathResolver->outputPath($type, $this->path, $this->modelName);

        if (is_file($fileToDelete)) {
            $this->ungenerator->output($fileToDelete);
        }

        for ($i = 0; $i < count($this->paths); $i++) {
            $pathToDelete = implode("/", array_slice($this->paths, 0, count($this->paths) - $i));
            $pathFromBase = $typePath . "/" . $pathToDelete;
            if (is_dir($pathFromBase) && !(new FilesystemIterator($pathFromBase))->valid()) {
                rmdir($pathFromBase);
            }
        }

        return true;
    }
}
