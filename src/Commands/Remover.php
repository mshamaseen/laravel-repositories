<?php
/**
 * Created by PhpStorm.
 * User: Shamaseen
 * Date: 10/08/19
 * Time: 04:48 م
 */

namespace Shamaseen\Repository\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Shamaseen\Generator\Ungenerator;
use Shamaseen\Repository\Traits\PathsResolver;

class Remover extends Command
{
    use PathsResolver;

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

        $controller = Config::get('repository.controllers_path');
        $model = Str::plural(Config::get('repository.model'));
        $repository = Str::plural(Config::get('repository.repository'));
        $request = Config::get('repository.requests_path');

        $this->remove('Controller', $controller);
        $this->remove('Entity', $model);
        $this->remove('Request', $request);
        $this->remove('Repository', $repository);
        return true;
    }

    public function remove($type, $typePath): bool
    {
        $fileToDelete = $typePath . "/" . $this->path . "/" . $this->modelName . $type . ".php";

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
