<?php

namespace Shamaseen\Repository\Tests\Feature;

use App\Http\Controllers\Tests\TestController;
use App\Models\Tests\Test;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Shamaseen\Repository\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security tests for the Repository pattern.
 *
 * Vulnerabilities under test:
 *
 * [CRITICAL] VUL-1: Mass Assignment bypass via AbstractRepository::update()
 *   AbstractRepository::update() calls Builder::update($data) — a raw query-builder
 *   method that bypasses Eloquent's $fillable/$guarded protection entirely.
 *   Even a model with a strict $fillable list cannot prevent an attacker from
 *   overwriting arbitrary columns through the HTTP update endpoint.
 *
 * [HIGH]     VUL-2: No default request validation
 *   The base Request class has $rules = [] and all Http*Rules() return []. Any data
 *   submitted by a client is accepted without server-side type/format/value checks
 *   unless a child Request class explicitly overrides those methods.
 *
 * [MEDIUM]   VUL-3: Unguarded model ($guarded = []) allows mass assignment via store()
 *   When the model is fully unguarded, every column in the table is mass-assignable
 *   through the POST endpoint, including system/sensitive columns.
 */
class SecurityTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config(['repository.disable_cache' => true]);
        config(['repository.stubs_path' => __DIR__.'/../stubs']);
        $this->artisan("generate:repository $this->userPath/$this->modelName -f");
        $this->createTable();
    }

    public function tearDown(): void
    {
        $this->dropDatabase();
        parent::tearDown();
    }

    /**
     * Override to add a sensitive column that should never be user-writable.
     */
    public function createTable(): void
    {
        Schema::create($this->table, function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->string('type');
            $blueprint->boolean('is_admin')->default(false); // Protected — should NOT be user-writable
            $blueprint->timestamps();
        });
    }

    // -------------------------------------------------------------------------
    // VUL-1: Builder::update() bypasses $fillable — update endpoint is unsafe
    // -------------------------------------------------------------------------

    /**
     * Ensure that both create() and update() respect $fillable.
     */
    public function testCreateAndUpdateRespectFillable(): void
    {
        // --- Setup: seed a row with is_admin = false via raw DB to avoid any ORM path ---
        $id = DB::table($this->table)->insertGetId([
            'name'       => 'normal_user',
            'type'       => 'user',
            'is_admin'   => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a subclass of Test with a strict $fillable that excludes is_admin.
        // This simulates a properly hardened production model.
        $protectedModelClass = new class extends Test {
            protected $table = 'tests'; // Explicit: anonymous classes get mangled names
            // Only name and type are intentionally fillable; is_admin is NOT.
            protected $fillable = ['name', 'type'];
            // Override guarded so fillable whitelist is authoritative.
            protected $guarded = [];
        };

        // Bind a repository that uses the protected model subclass.
        $repository = new class($protectedModelClass) extends \Shamaseen\Repository\Utility\AbstractRepository {
            private \Illuminate\Database\Eloquent\Model $modelInstance;

            public function __construct(\Illuminate\Database\Eloquent\Model $model)
            {
                $this->modelInstance = $model;
                // Bypass App::make in parent constructor.
                $this->model = $model;
            }

            public function getModelClass(): string
            {
                return get_class($this->modelInstance);
            }
        };

        // --- Step 1: Verify create() respects $fillable — is_admin should be blocked ---
        // Builder::create() goes through Model::fill() which honours the $fillable whitelist.
        $created = $repository->create(['name' => 'attempt', 'type' => 'user', 'is_admin' => true]);
        $this->assertFalse(
            (bool) DB::table($this->table)->where('id', $created->id)->value('is_admin'),
            'create() should respect $fillable and NOT store is_admin.'
        );

        // --- Step 2: Verify update() BYPASSES $fillable — is_admin IS updated ---
        // Builder::update() is a direct SQL UPDATE; it never calls fill() so $fillable is ignored.
        $repository->update($id, ['name' => 'normal_user', 'is_admin' => true]);

        $row = DB::table($this->table)->where('id', $id)->first();
        $this->assertFalse(
            (bool) $row->is_admin,
            'AbstractRepository::update() uses Builder::update() which ' .
            'bypasses $fillable and updated is_admin even though it is NOT in the fillable list.'
        );
    }
}