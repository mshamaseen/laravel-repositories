<?php

namespace Shamaseen\Repository\Tests\Feature;

use App\Http\Controllers\Tests\TestController;
use App\Models\Tests\Test;
use Illuminate\Support\Facades\Route;
use Shamaseen\Repository\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CrudTest extends TestCase
{
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
        $this->artisan("generate:repository $this->userPath/$this->modelName -f");
        $this->createDatabase();
    }

    public function tearDown(): void
    {
        $this->dropDatabase();
        parent::tearDown();
    }

    public function testIndex()
    {
        Route::get('tests', [TestController::class, 'index']);

        $response = $this->getJson('tests');

        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK, Response::HTTP_PARTIAL_CONTENT,
        ]);
    }

    public function testCreate()
    {
        Route::get('tests/create', [TestController::class, 'create']);

        $response = $this->getJson('tests/create');
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_NO_CONTENT,
        ]);
    }

    public function testShow()
    {
        $test = Test::create([
            'name' => 'test name',
            'type' => 'a test'
        ]);

        Route::get('tests/{id}', [TestController::class, 'show']);

        $response = $this->getJson('tests/'.$test->id);

        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK,
        ]);
    }

    public function testStore()
    {
        Route::post('tests', [TestController::class, 'store']);

        $data = [
            'name' => 'Create Test',
            'type' => 'New',
        ];

        $response = $this->postJson('tests', $data);
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_CREATED,
        ]);

        $content = json_decode($response->getContent())->data;

        $test = Test::findOrFail($content->id);
        $this->assertEquals($data['name'], $test->name);
        $this->assertEquals($data['type'], $test->type);

        return $content->id;
    }

    public function testUpdate()
    {
        Route::put('tests/{id}', [TestController::class, 'update']);

        $test = Test::create([
            'name' => 'test name',
            'type' => 'a test'
        ]);

        $data = [
            'name' => 'Update Test',
            'type' => 'Updated',
        ];

        $response = $this->putJson('tests/'.$test->id, $data);

        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK,
        ]);

        $test = Test::findOrFail($test->id);
        $this->assertEquals($data['name'], $test->name);
        $this->assertEquals($data['type'], $test->type);
    }

    public function testDelete()
    {
        Route::delete('tests/{id}', [TestController::class, 'destroy']);

        $test = Test::create([
            'name' => 'test name',
            'type' => 'a test'
        ]);

        $response = $this->deleteJson('tests/'.$test->id);
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK,
        ]);

        $this->assertNull(Test::find($test->id));
    }
}
