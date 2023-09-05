<?php

namespace Shamaseen\Repository\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Shamaseen\Repository\Commands\Generator;
use Shamaseen\Repository\PathResolver;
use Shamaseen\Repository\Tests\TestCase;

class GenerateFilesTest extends TestCase
{
    private array $filesToGenerate = ['Controller', 'Repository', 'Model', 'Request', 'Resource', 'Collection', 'Policy', 'Test'];

    /**
     * @param string $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function testGenerate()
    {
        $this->artisan("generate:repository $this->userPath/$this->modelName -f");

        foreach ($this->filesToGenerate as $type) {
            $absolutePath = $this->generator->absolutePath(
                $this->pathResolver->outputPath($type)
            );

            $this->assertFileExists($absolutePath);
        }
    }

    public function testGenerateMCROnly()
    {
        $this->artisan("generate:repository $this->userPath/$this->modelName -f -mrc");

        $filesToGenerate = ['Controller', 'Repository', 'Model'];
        foreach ($filesToGenerate as $type) {
            $absolutePath = $this->generator->absolutePath(
                $this->pathResolver->outputPath($type)
            );

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
                    Generator::MODEL_OPTION,
                    Generator::CONTROLLER_OPTION,
                ],
                [
                    'Model',
                    'Controller',
                ],
            ],
            // running Request option should only generate Request
            [
                [
                    Generator::REQUEST_OPTION,
                ],
                [
                    'Request',
                ]
            ],
            // running Collection option should only generate Collection
            [
                [
                    Generator::COLLECTION_OPTION,
                ],
                [
                    'Collection',
                ]
            ],
        ];
    }

    /** @dataProvider defaultStubsConfigProvider */
    public function testDefaultStubsConfig(array $config, array $generatedNames)
    {
        Config::set('repository.default_generated_files', $config);
        $this->artisan("generate:repository $this->userPath/$this->modelName -f");

        foreach ($generatedNames as $generatedName) {
            $this->assertFileExists($this->generator->absolutePath(
                $this->pathResolver->outputPath($generatedName)
            ));
        }

        $allGeneratedStubs = array_keys(PathResolver::$configTypePathMap);
        $filesNotGenerated = array_diff($allGeneratedStubs, $generatedNames);

        foreach ($filesNotGenerated as $fileNotGenerated) {
            $this->assertFileDoesNotExist($this->generator->absolutePath(
                $this->pathResolver->outputPath($fileNotGenerated)
            ));
        }
    }
}
