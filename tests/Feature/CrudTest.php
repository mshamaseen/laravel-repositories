<?php

namespace Shamaseen\Repository\Tests\Feature;

use App\Models\Tests\Test;
use Illuminate\Support\Facades\Route;
use Shamaseen\Repository\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CrudTest extends TestCase
{
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
    }

    public function test_index()
    {
        Route::get('tests', [\App\Http\Controllers\Tests\TestController::class, 'index']);

        $response = $this->getJson('tests');
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK, Response::HTTP_PARTIAL_CONTENT
        ]);
    }

    public function test_show()
    {
        Route::get('tests/{id}', [\App\Http\Controllers\Tests\TestController::class, 'show']);

        $response = $this->getJson('tests/1');

        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK
        ]);
    }

    public function test_create()
    {
        Route::get('tests/create', [\App\Http\Controllers\Tests\TestController::class, 'create']);

        $response = $this->getJson('tests/create');
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_NO_CONTENT
        ]);
    }

    public function test_store()
    {
        Route::post('tests', [\App\Http\Controllers\Tests\TestController::class, 'store']);

        $data = [
            'name' => 'Create Test',
            'type' => 'New'
        ];

        $response = $this->postJson('tests', $data);
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_CREATED
        ]);

        $content = json_decode($response->getContent())->data;

        $test = Test::findOrFail($content->id);
        $this->assertEquals($data['name'], $test->name);
        $this->assertEquals($data['type'], $test->type);
        return $content->id;
    }

    /**
     * @depends test_store
     */
    public function test_update(int $id)
    {
        Route::put('tests/{id}', [\App\Http\Controllers\Tests\TestController::class, 'update']);

        $data = [
            'name' => 'Update Test',
            'type' => 'Updated'
        ];

        $response = $this->putJson('tests/'.$id, $data);

        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK
        ]);

        $test = Test::findOrFail($id);
        $this->assertEquals($data['name'], $test->name);
        $this->assertEquals($data['type'], $test->type);
    }

    /**
     * @depends test_store
     */
    public function test_delete(int $id)
    {
        Route::delete('tests/{id}', [\App\Http\Controllers\Tests\TestController::class, 'destroy']);

        $response = $this->deleteJson('tests/'.$id);
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK
        ]);
    }
}
