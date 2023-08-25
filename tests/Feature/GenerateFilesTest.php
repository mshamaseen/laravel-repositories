<?php

namespace Shamaseen\Repository\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Shamaseen\Repository\Commands\Generator;
use Shamaseen\Repository\PathResolver;
use Shamaseen\Repository\Tests\TestCase;

class GenerateFilesTest extends TestCase
{
    /**
     * @var mixed
     */
    private PathResolver $pathResolver;

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

    public function setUp(): void
    {
        parent::setUp();
        $this->pathResolver = new PathResolver($this->modelName, $this->userPath, config('repository.base_path'));
    }

    public function testGenerate()
    {
        $this->artisan("generate:repository $this->userPath/$this->modelName -f");

        foreach ($this->filesToGenerate as $type) {
            $absolutePath = $this->pathResolver->absolutePath($type);

            $this->assertFileExists($absolutePath);
        }
    }

    public function testGenerateMCROnly()
    {
        $this->artisan("generate:repository $this->userPath/$this->modelName -f -mrc");

        $filesToGenerate = ['Controller', 'Repository', 'Model'];
        foreach ($filesToGenerate as $type) {
            $absolutePath = $this->pathResolver->absolutePath($type);

            $this->assertFileExists($absolutePath);
        }
    }

    public function defaultStubsConfigProvider(): array
    {
        return [
            // run 1
            [
                [
                    Generator::RESOURCE_OPTION
                ],
                [
                    'Resource',
                ]
            ],
            // run 2
            [
                [
                    Generator::RESOURCE_OPTION,
                    Generator::CONTROLLER_OPTION,
                ],
                [
                    'Controller',
                    'Resource',
                ],
            ],
        ];
    }

    /** @dataProvider defaultStubsConfigProvider */
    public function testDefaultStubsConfig(array $config, array $generatedNames)
    {
        Config::set('repository.default_stubs', $config);
        $this->artisan("generate:repository $this->userPath/$this->modelName -f");

        foreach ($generatedNames as $generatedName) {
            $this->assertFileExists($this->pathResolver->absolutePath($generatedName));
        }

        $allGeneratedStubs = array_keys(PathResolver::$configTypePathMap);
        $filesNotGenerated = array_diff($allGeneratedStubs, $generatedNames);

        foreach ($filesNotGenerated as $fileNotGenerated) {
            $this->assertFileDoesNotExist($this->pathResolver->absolutePath($fileNotGenerated));
        }
    }
}
