<?php

namespace App\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateCrudStack extends Command
{
    protected $signature = 'gen:crud {name} {--live} {--no-soft} {--no-test}';
    protected $description = 'Generate a full CRUD stack: Migration, Model, Factory, Seeder, Resource, Service, Controller, Tests, and API routes';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $live = $this->option('live');
        $noSoft = $this->option('no-soft');
        $noTest = $this->option('no-test');

        $this->info("🚀 Generating CRUD stack for {$name}...");

        // 1. Create Model, Migration, Factory, Seeder
        $this->call('make:model', ['name' => $name, '-m' => true, '-f' => true, '-s' => true]);

        // 1b. Add soft deletes if not opted out
        if (!$noSoft) {
            $this->addSoftDeletesToModel($name);
            $this->addSoftDeletesToMigration($name);
        }

        // 1c. Add data columns to migration
        $this->addDataColumnsToMigration($name);

        // 1d. Add factory defaults
        $this->addFactoryDefaults($name);

        // 1e. Add fillable fields
        $this->addFillableToModel($name);

        // 2. Create Resource
        $this->call('make:resource', ['name' => "{$name}Resource"]);

        // 3. Create Service
        $this->createService($name, $live, $noSoft);

        // 4. Create Controller with Boilerplate (delegates to Service)
        $this->createController($name, $live);

        // 5. Register Routes in api.php
        $this->registerRoutes($name);

        // 6. Create Feature Test
        if (!$noTest) {
            $this->createFeatureTest($name);
        }

        // 7. Create Unit Test
        if (!$noTest) {
            $this->createUnitTest($name);
        }

        // 8. Create Test Data payloads
        if (!$noTest) {
            $this->createTestData($name);
        }

        $this->info("✅ CRUD stack for {$name} created successfully!");
        $this->info("🔗 API endpoints registered in routes/api.php");

        if (!$noTest) {
            $this->info("🧪 Feature & Unit tests created in tests/");
            $this->info("📁 Test data payloads created in tests/Data/");
        }
    }

    // ─────────────────────────────────────────────
    //  Model modifiers
    // ─────────────────────────────────────────────

    protected function addFillableToModel(string $name): void
    {
        $path = app_path("Models/{$name}.php");
        $content = File::get($path);

        if (Str::contains($content, '$fillable')) {
            return;
        }

        $fillable = <<<'PHP'

    protected $fillable = [
        'name',
        'description',
        'status',
    ];
PHP;

        if (Str::contains($content, 'use SoftDeletes;')) {
            $content = Str::replaceFirst(
                "use SoftDeletes;",
                "use SoftDeletes;{$fillable}",
                $content,
            );
        } else {
            $content = Str::replaceFirst(
                "use HasFactory;",
                "use HasFactory;{$fillable}",
                $content,
            );
        }

        File::put($path, $content);
        $this->info("   ✓ Added fillable fields to Model");
    }

    protected function addSoftDeletesToModel(string $name): void
    {
        $path = app_path("Models/{$name}.php");
        $content = File::get($path);

        if (Str::contains($content, 'use SoftDeletes;')) {
            return;
        }

        $content = Str::replaceFirst(
            'use Illuminate\Database\Eloquent\Model;',
            "use Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\SoftDeletes;",
            $content,
        );

        $content = Str::replaceFirst(
            "use HasFactory;",
            "use HasFactory;\n    use SoftDeletes;",
            $content,
        );

        File::put($path, $content);
        $this->info("   ✓ Added SoftDeletes trait to Model");
    }

    protected function addSoftDeletesToMigration(string $name): void
    {
        $plural = Str::plural(Str::snake($name));
        $migration = glob(database_path("migrations/*_create_{$plural}_table.php"));
        $migration = $migration[0] ?? null;

        if (!$migration) {
            return;
        }

        $content = File::get($migration);

        if (Str::contains($content, 'softDeletes')) {
            return;
        }

        $content = Str::replaceFirst(
            '$table->timestamps();',
            "\$table->timestamps();\n            \$table->softDeletes();",
            $content,
        );

        File::put($migration, $content);
        $this->info("   ✓ Added softDeletes column to Migration");
    }

    protected function addDataColumnsToMigration(string $name): void
    {
        $plural = Str::plural(Str::snake($name));
        $migration = glob(database_path("migrations/*_create_{$plural}_table.php"));
        $migration = $migration[0] ?? null;

        if (!$migration) {
            return;
        }

        $content = File::get($migration);

        if (Str::contains($content, "\$table->string('name'")) {
            return;
        }

        $content = Str::replaceFirst(
            '$table->id();',
            "\$table->id();\n            \$table->string('name');\n            \$table->text('description')->nullable();\n            \$table->string('status')->default('active');",
            $content,
        );

        File::put($migration, $content);
        $this->info("   ✓ Added data columns to Migration");
    }

    protected function addFactoryDefaults(string $name): void
    {
        $plural = Str::plural(Str::snake($name));
        $path = database_path("factories/{$name}Factory.php");
        $content = File::get($path);

        if (Str::contains($content, "'name' =>")) {
            return;
        }

        $defaults = <<<'PHP'
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(['active', 'inactive']),
PHP;

        $content = Str::replaceFirst(
            'return [',
            "return [\n{$defaults}",
            $content,
        );

        File::put($path, $content);
        $this->info("   ✓ Added default values to Factory");
    }

    // ─────────────────────────────────────────────
    //  Name helpers
    // ─────────────────────────────────────────────

    protected function getNames(string $name): array
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

    // ─────────────────────────────────────────────
    //  Path helpers
    // ─────────────────────────────────────────────

    protected function getControllerPath(string $name): string
    {
        return app_path("Http/Controllers/{$name}Controller.php");
    }

    protected function getServicePath(string $name): string
    {
        return app_path("Services/{$name}Service.php");
    }

    protected function getFeatureTestPath(string $name): string
    {
        return base_path("tests/Feature/{$name}ControllerTest.php");
    }

    protected function getUnitTestPath(string $name): string
    {
        return base_path("tests/Unit/{$name}ServiceTest.php");
    }

    protected function getTestDataDir(string $name): string
    {
        return base_path('tests/Data/' . Str::snake($name));
    }

    // ─────────────────────────────────────────────
    //  Stubs
    // ─────────────────────────────────────────────

    protected function getControllerStub(string $name): string
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
            'name' => 'required|string',
            'description' => 'required|string',
            'status' => 'required|string',
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
            'name' => 'required|string',
            'description' => 'required|string',
            'status' => 'required|string',
        ]);

        \${$variable}->update(\$data);
        return new {$studly}Resource(\${$variable});
    }

    /**
     * Delete (DELETE)
     */
    public function destroy({$studly} \${$variable})
    {
        \${$variable}->forceDelete();
        return response()->noContent();
    }
}
EOD;
    }

    protected function getRouteLine(string $name): string
    {
        $names = $this->getNames($name);
        return "Route::apiResource('{$names['pluralSnake']}', \\App\Http\Controllers\\{$names['studly']}Controller::class);";
    }

    protected function getServiceStub(string $name, bool $live = false): string
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
        \${$variable} = {$studly}::withTrashed()->findOrFail(\$id);

        return (bool) \${$variable}->forceDelete();
    }
}
EOD;
    }

    protected function getFeatureTestStub(string $name): string
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
            file_get_contents(__DIR__ . '/../Data/{$snake}/store-payload.json'),
            true
        );
        \$this->updatePayload = json_decode(
            file_get_contents(__DIR__ . '/../Data/{$snake}/update-payload.json'),
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

    protected function getUnitTestStub(string $name): string
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

    protected function getTestDataFiles(string $name): array
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

    // ─────────────────────────────────────────────
    //  Writers
    // ─────────────────────────────────────────────

    protected function createService($name, bool $live, bool $noSoft)
    {
        $path = $this->getServicePath($name);
        $stub = $this->getServiceStub($name, $live);

        File::put($path, $stub);
        $this->info("📄 Created Service: {$name}Service");
    }

    protected function createController($name, bool $live)
    {
        $path = $this->getControllerPath($name);
        $stub = $this->getControllerStub($name, $live);

        File::put($path, $stub);
        $this->info("📄 Created Controller: {$name}Controller");
    }

    protected function registerRoutes($name)
    {
        $routeLine = $this->getRouteLine($name);
        $path = base_path('routes/api.php');
        $content = File::get($path);

        if (!Str::contains($content, "{$name}Controller::class")) {
            File::append($path, "\n" . $routeLine . "\n");
            $this->info("🛣 Registered routes for " . Str::plural(Str::snake($name)));
        }
    }

    protected function createFeatureTest($name): void
    {
        $path = $this->getFeatureTestPath($name);
        $stub = $this->getFeatureTestStub($name);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);
        $this->info("🧪 Created Feature Test: {$name}ControllerTest");
    }

    protected function createUnitTest($name): void
    {
        $path = $this->getUnitTestPath($name);
        $stub = $this->getUnitTestStub($name);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);
        $this->info("🧪 Created Unit Test: {$name}ServiceTest");
    }

    protected function createTestData($name): void
    {
        $dir = $this->getTestDataDir($name);
        $files = $this->getTestDataFiles($name);

        File::ensureDirectoryExists($dir);

        foreach ($files as $filename => $content) {
            File::put($dir . '/' . $filename, $content);
        }

        $this->info("📁 Created test data payloads in tests/Data/" . Str::snake($name));
    }
}
