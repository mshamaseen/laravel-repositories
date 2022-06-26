<?php

namespace Shamaseen\Repository\Tests\Feature;

use Shamaseen\Repository\PathResolver;
use Shamaseen\Repository\Tests\TestCase;

class GenerateFilesTest extends TestCase
{
    /**
     * @var mixed
     */
    private PathResolver $pathResolver;

    private array $filesToGenerate = ['Controller', 'Repository', 'Model', 'Request', 'Resource', 'Collection', 'Policy'];

    protected string $modelName = 'Test';
    protected string $userPath = 'Tests';

    /**
     * @param string $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->pathResolver = new PathResolver($this->modelName, $this->userPath, config('repository.base_path'));
    }

    public function testGenerate()
    {
        $this->artisan("generate:repository $this->userPath/$this->modelName");

        foreach ($this->filesToGenerate as $type) {
            $absolutePath = $this->pathResolver->absolutePath($type);

            $this->assertFileExists($absolutePath);
        }
    }

    public function testUngenerate()
    {
        $this->artisan("ungenerate:repository $this->userPath/$this->modelName")
            ->expectsConfirmation('This will delete Test files and folder, Do you want to continue ?', 'yes');

        foreach ($this->filesToGenerate as $type) {
            $outputPath = $this->pathResolver->absolutePath($type);
            $this->assertFileDoesNotExist($outputPath);
        }
    }
}
