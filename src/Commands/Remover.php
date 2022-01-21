<?php

namespace Shamaseen\Repository\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Shamaseen\Generator\Ungenerator;
use Shamaseen\Repository\Events\RepositoryFilesRemoved;
use Shamaseen\Repository\PathResolver;

class Remover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ungenerate:repository
    {name : Class (singular) for example User}
    {--base= : Base path to inject the files\folders in}
    {--f|force : force deleting the files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove repository files';

    protected array $userPaths;
    protected string $modelName;
    protected string $userPath;
    protected string $basePath;
    private Ungenerator $ungenerator;
    private PathResolver $pathResolver;

    /**
     * Create a new command instance.
     */
    public function __construct(Ungenerator $ungenerator)
    {
        parent::__construct();
        $this->ungenerator = $ungenerator;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->userPaths = preg_split(' ([/\\\]) ', $this->argument('name'));

        $this->modelName = array_pop($this->userPaths);
        $this->userPath = implode('/', str_replace('\\', '/', $this->userPaths));
        $this->basePath = $this->option('base') ? $this->option('base') : Config::get('repository.base_path');

        $this->pathResolver = new PathResolver($this->modelName, $this->userPath, $this->basePath);
        // Configure the generator
        config(['generator.base_path' => base_path($this->basePath)]);

        if (!$this->option('force') && !$this->confirm('This will delete ' . $this->modelName . ' files and folder, Do you want to continue ?')) {
            return false;
        }

        $this->remove('Controller');
        $this->remove('Model');
        $this->remove('Request');
        $this->remove('Repository');
        $this->remove('Resource');

        RepositoryFilesRemoved::dispatch($this->basePath, $this->userPath, $this->modelName);

        $this->dumpAutoload();

        return Command::SUCCESS;
    }

    public function remove($type): bool
    {
        $fileToDelete = $this->pathResolver->outputPath($type);

        if (is_file($this->ungenerator->absolutePath($fileToDelete))) {
            $this->ungenerator->output($fileToDelete);
        }

        $pathsToDelete = count($this->userPaths);
        for ($i = 0; $i < $pathsToDelete; $i++) {
            $pathFromBase = $this->ungenerator->absolutePath(
                $this->pathResolver->directionFromBase($type)
            );

            if (is_dir($pathFromBase) && !(new FilesystemIterator($pathFromBase))->valid()) {
                rmdir($pathFromBase);
            }
        }

        return true;
    }

    private function dumpAutoload()
    {
        shell_exec('composer dump-autoload');
    }
}
