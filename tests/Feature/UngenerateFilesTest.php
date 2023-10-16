<?php

namespace Shamaseen\Repository\Tests\Feature;

use Shamaseen\Repository\PathResolver;
use Shamaseen\Repository\Tests\TestCase;

class UngenerateFilesTest extends TestCase
{
    private array $filesToGenerate = ['Controller', 'Repository', 'Model', 'Request', 'Resource', 'Collection', 'Policy', 'Test'];

    protected string $modelName = 'Test';
    protected string $userPath = 'Tests';

    /**
     * @param string $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function testUngenerate()
    {
        $this->artisan("ungenerate:repository $this->userPath/$this->modelName")
            ->expectsConfirmation('This will delete Test files and folder, Do you want to continue ?', 'yes');

        foreach ($this->filesToGenerate as $type) {
            $outputPath = $this->generator->absolutePath($this->pathResolver->outputPath($type));
            $this->assertFileDoesNotExist($outputPath);
        }
    }
}
