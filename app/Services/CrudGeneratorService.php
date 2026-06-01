<?php

namespace App\Services;

use Illuminate\Support\Str;

class CrudGeneratorService
{
    public function getNames(string $name): array
    {
        $studly = Str::studly($name);
        return [
            'studly' => $studly,
            'plural' => Str::plural($studly),
            'snake' => Str::snake($studly),
            'pluralSnake' => Str::plural(Str::snake($studly)),
            'variable' => Str::camel($studly),
            'pluralVariable' => Str::camel(Str::plural($studly)),
        ];
    }

    public function getControllerPath(string $name): string
    {
        return app_path("Http/Controllers/{$name}Controller.php");
    }

    public function getControllerStub(string $name): string
    {
        $names = $this->getNames($name);
        $studly = $names['studly'];
        $variable = $names['variable'];
        $pluralVariable = $names['pluralVariable'];

        return <<<EOD
<?php

namespace App\Http\Controllers;

use App\Models\\$studly;
use App\Http\Resources\\{$studly}Resource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class {$studly}Controller extends Controller
{
    /**
     * Get all (paginated)
     */
    public function index()
    {
        \${$pluralVariable} = {$studly}::paginate(10);
        return {$studly}Resource::collection(\${$pluralVariable});
    }

    /**
     * Create new (POST)
     */
    public function store(Request \$request)
    {
        \$data = \$request->validate([
            // Add your validation rules here
        ]);

        \${$variable} = {$studly}::create(\$data);
        return new {$studly}Resource(\${$variable});
    }

    /**
     * Get single (GET)
     */
    public function show({$studly} \${$variable})
    {
        return new {$studly}Resource(\${$variable});
    }

    /**
     * Update (PUT/PATCH)
     */
    public function update(Request \$request, {$studly} \${$variable})
    {
        \$data = \$request->validate([
            // Add your validation rules here
        ]);

        \${$variable}->update(\$data);
        return new {$studly}Resource(\${$variable});
    }

    /**
     * Delete (DELETE)
     */
    public function destroy({$studly} \${$variable})
    {
        \${$variable}->delete();
        return response()->noContent();
    }
}
EOD;
    }

    public function getRouteLine(string $name): string
    {
        $names = $this->getNames($name);
        return "Route::apiResource('{$names['pluralSnake']}', \\App\Http\Controllers\\{$names['studly']}Controller::class);";
    }

    public function getServicePath(string $name): string
    {
        return app_path("Services/{$name}Service.php");
    }

    public function getServiceStub(string $name, bool $live = false): string
    {
        $names = $this->getNames($name);
        $studly = $names['studly'];
        $variable = $names['variable'];

        return <<<EOD
<?php

namespace App\Services;

use App\Models\\$studly;
use Illuminate\Pagination\LengthAwarePaginator;

class {$studly}Service
{
    public function all(): LengthAwarePaginator
    {
        return {$studly}::paginate(10);
    }

    public function find(int \$id): {$studly}
    {
        return {$studly}::findOrFail(\$id);
    }

    public function create(array \$data): {$studly}
    {
        return {$studly}::create(\$data);
    }

    public function update(int \$id, array \$data): bool
    {
        \${$variable} = {$studly}::findOrFail(\$id);

        return (bool) \${$variable}->update(\$data);
    }

    public function delete(int \$id): bool
    {
        \${$variable} = {$studly}::findOrFail(\$id);

        return (bool) \${$variable}->delete();
    }
}
EOD;
    }

    public function getFeatureTestPath(string $name): string
    {
        return base_path("tests/Feature/{$name}ControllerTest.php");
    }

    public function getFeatureTestStub(string $name): string
    {
        $names = $this->getNames($name);
        $studly = $names['studly'];
        $variable = $names['variable'];
        $pluralSnake = $names['pluralSnake'];
        $snake = $names['snake'];

        return <<<EOD
<?php

namespace Tests\Feature;

use App\Models\\$studly;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {$studly}ControllerTest extends TestCase
{
    use RefreshDatabase;

    private array \$storePayload;
    private array \$updatePayload;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->storePayload = json_decode(
            file_get_contents(__DIR__ . '/Data/{$snake}/store-payload.json'),
            true
        );
        \$this->updatePayload = json_decode(
            file_get_contents(__DIR__ . '/Data/{$snake}/update-payload.json'),
            true
        );
    }

    public function test_can_list_{$variable}s(): void
    {
        {$studly}::factory()->count(3)->create();

        \$response = \$this->getJson('/api/{$pluralSnake}');

        \$response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_can_create_{$variable}(): void
    {
        \$response = \$this->postJson('/api/{$pluralSnake}', \$this->storePayload);

        \$response->assertStatus(201)
            ->assertJsonStructure(['data']);

        \$this->assertDatabaseHas('{$pluralSnake}', \$this->storePayload);
    }

    public function test_can_show_{$variable}(): void
    {
        \${$variable} = {$studly}::factory()->create();

        \$response = \$this->getJson('/api/{$pluralSnake}/' . \${$variable}->id);

        \$response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_can_update_{$variable}(): void
    {
        \${$variable} = {$studly}::factory()->create();

        \$response = \$this->putJson('/api/{$pluralSnake}/' . \${$variable}->id, \$this->updatePayload);

        \$response->assertStatus(200);

        \$this->assertDatabaseHas('{$pluralSnake}', \$this->updatePayload);
    }

    public function test_can_delete_{$variable}(): void
    {
        \${$variable} = {$studly}::factory()->create();

        \$response = \$this->deleteJson('/api/{$pluralSnake}/' . \${$variable}->id);

        \$response->assertStatus(204);

        \$this->assertDatabaseMissing('{$pluralSnake}', ['id' => \${$variable}->id]);
    }
}
EOD;
    }

    public function getUnitTestPath(string $name): string
    {
        return base_path("tests/Unit/{$name}ServiceTest.php");
    }

    public function getUnitTestStub(string $name): string
    {
        $names = $this->getNames($name);
        $studly = $names['studly'];
        $variable = $names['variable'];
        $pluralSnake = $names['pluralSnake'];
        $snake = $names['snake'];

        return <<<EOD
<?php

namespace Tests\Unit;

use App\Models\\$studly;
use App\Services\\{$studly}Service;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class {$studly}ServiceTest extends TestCase
{
    use DatabaseMigrations;

    private {$studly}Service \$service;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->service = new {$studly}Service();
    }

    public function test_can_list_{$variable}s(): void
    {
        {$studly}::factory()->count(3)->create();

        \$result = \$this->service->all();

        \$this->assertCount(3, \$result);
    }

    public function test_can_create_{$variable}(): void
    {
        \$payload = json_decode(
            file_get_contents(__DIR__ . '/../Data/{$snake}/store-payload.json'),
            true
        );

        \${$variable} = \$this->service->create(\$payload);

        \$this->assertInstanceOf({$studly}::class, \${$variable});
        \$this->assertDatabaseHas('{$pluralSnake}', \$payload);
    }

    public function test_can_find_{$variable}(): void
    {
        \${$variable} = {$studly}::factory()->create();

        \$found = \$this->service->find(\${$variable}->id);

        \$this->assertInstanceOf({$studly}::class, \$found);
        \$this->assertEquals(\${$variable}->id, \$found->id);
    }

    public function test_can_update_{$variable}(): void
    {
        \${$variable} = {$studly}::factory()->create();
        \$payload = json_decode(
            file_get_contents(__DIR__ . '/../Data/{$snake}/update-payload.json'),
            true
        );

        \$result = \$this->service->update(\${$variable}->id, \$payload);

        \$this->assertTrue(\$result);
        \$this->assertDatabaseHas('{$pluralSnake}', \$payload);
    }

    public function test_can_delete_{$variable}(): void
    {
        \${$variable} = {$studly}::factory()->create();

        \$result = \$this->service->delete(\${$variable}->id);

        \$this->assertTrue(\$result);
        \$this->assertDatabaseMissing('{$pluralSnake}', ['id' => \${$variable}->id]);
    }
}
EOD;
    }

    public function getTestDataDir(string $name): string
    {
        return base_path('tests/Data/' . Str::snake($name));
    }

    public function getTestDataFiles(string $name): array
    {
        return [
            'store-payload.json' => json_encode([
                'name' => 'Sample Name',
                'description' => 'Sample description',
                'status' => 'active',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'update-payload.json' => json_encode([
                'name' => 'Updated Name',
                'description' => 'Updated description',
                'status' => 'inactive',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ];
    }
}
