<?php

namespace Shamaseen\Repository\Tests\Feature;

use Shamaseen\Repository\Tests\TestCase;
use Shamaseen\Repository\PathResolver;

class GenerateFilesTest extends TestCase
{
    /**
     * @var mixed
     */
    private PathResolver $pathResolver;

    private array $filesToGenerate = ['Controller','Repository','Model','Request','Resource'];

    /**
     * Some
     *
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    function setUp(): void
    {
        parent::setUp();
        $this->pathResolver = $this->app->make(PathResolver::class);
    }

    function test_command_generate()
    {
        $this->artisan('make:repository Tests/Test');

        $this->checkFiles();
    }

    private function checkFiles()
    {
        foreach ($this->filesToGenerate as $type) {
            $outputPath = $this->pathResolver->outputPath($type,'Tests','Test');

            $this->assertFileExists($outputPath);
        }
    }
}
