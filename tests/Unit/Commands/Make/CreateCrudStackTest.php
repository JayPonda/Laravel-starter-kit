<?php

namespace Tests\Unit\Commands\Make;

use App\Console\Commands\Make\CreateCrudStack;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;
use Mockery;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class CreateCrudStackTest extends TestCase
{
    private CreateCrudStack $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new CreateCrudStack();
        $this->command->setOutput(
            new OutputStyle(new ArrayInput([]), new BufferedOutput())
        );
    }



    private function invokeProtected(string $method, mixed ...$args): mixed
    {
        $ref = new \ReflectionMethod($this->command, $method);
        $ref->setAccessible(true);
        return $ref->invoke($this->command, ...$args);
    }

    public function test_get_names(): void
    {
        $names = $this->invokeProtected('getNames', 'BlogPost');

        $this->assertEquals('BlogPost', $names['studly']);
        $this->assertEquals('blog_post', $names['snake']);
        $this->assertEquals('blog_posts', $names['pluralSnake']);
        $this->assertEquals('blogPost', $names['variable']);
    }

    public function test_get_controller_path(): void
    {
        $path = $this->invokeProtected('getControllerPath', 'BlogPost');
        $this->assertStringEndsWith('app/Http/Controllers/BlogPostController.php', $path);
    }

    public function test_get_service_path(): void
    {
        $path = $this->invokeProtected('getServicePath', 'BlogPost');
        $this->assertStringEndsWith('app/Services/BlogPostService.php', $path);
    }

    public function test_get_feature_test_path(): void
    {
        $path = $this->invokeProtected('getFeatureTestPath', 'BlogPost');
        $this->assertStringEndsWith('tests/Feature/BlogPostControllerTest.php', $path);
    }

    public function test_get_unit_test_path(): void
    {
        $path = $this->invokeProtected('getUnitTestPath', 'BlogPost');
        $this->assertStringEndsWith('tests/Unit/BlogPostServiceTest.php', $path);
    }

    public function test_get_test_data_dir(): void
    {
        $dir = $this->invokeProtected('getTestDataDir', 'BlogPost');
        $this->assertStringEndsWith('tests/Data/blog_post', $dir);
    }

    public function test_get_route_line(): void
    {
        $line = $this->invokeProtected('getRouteLine', 'BlogPost');
        $this->assertEquals(
            "Route::apiResource('blog_posts', \\App\Http\Controllers\\BlogPostController::class);",
            $line
        );
    }

    public function test_get_controller_stub(): void
    {
        $stub = $this->invokeProtected('getControllerStub', 'BlogPost');

        $this->assertStringContainsString('class BlogPostController', $stub);
        $this->assertStringContainsString('BlogPost::paginate(10)', $stub);
        $this->assertStringContainsString('new BlogPostResource($blogPost)', $stub);
        $this->assertStringContainsString("'name' => 'required|string'", $stub);
        $this->assertStringContainsString('$blogPost->forceDelete();', $stub);
    }

    public function test_get_service_stub(): void
    {
        $stub = $this->invokeProtected('getServiceStub', 'BlogPost');

        $this->assertStringContainsString('class BlogPostService', $stub);
        $this->assertStringContainsString('BlogPost::paginate(10)', $stub);
        $this->assertStringContainsString('withTrashed()->findOrFail', $stub);
        $this->assertStringContainsString('forceDelete();', $stub);
    }

    public function test_get_feature_test_stub(): void
    {
        $stub = $this->invokeProtected('getFeatureTestStub', 'BlogPost');

        $this->assertStringContainsString('class BlogPostControllerTest', $stub);
        $this->assertStringContainsString("__DIR__ . '/../Data/blog_post/store-payload.json'", $stub);
    }

    public function test_get_unit_test_stub(): void
    {
        $stub = $this->invokeProtected('getUnitTestStub', 'BlogPost');

        $this->assertStringContainsString('class BlogPostServiceTest', $stub);
        $this->assertStringContainsString("__DIR__ . '/../Data/blog_post/store-payload.json'", $stub);
    }

    public function test_get_test_data_files(): void
    {
        $files = $this->invokeProtected('getTestDataFiles', 'BlogPost');

        $this->assertArrayHasKey('store-payload.json', $files);
        $this->assertArrayHasKey('update-payload.json', $files);

        $store = json_decode($files['store-payload.json'], true);
        $this->assertEquals('Sample Name', $store['name']);
    }

    // ──── Path helpers ─────────────────────────────────────────

    public function test_get_feature_test_path_returns_correct_path(): void
    {
        $path = $this->invokeProtected('getFeatureTestPath', 'BlogPost');
        $this->assertStringEndsWith('tests/Feature/BlogPostControllerTest.php', $path);
        $this->assertStringStartsWith(base_path(), $path);
    }

    public function test_get_unit_test_path_returns_correct_path(): void
    {
        $path = $this->invokeProtected('getUnitTestPath', 'BlogPost');
        $this->assertStringEndsWith('tests/Unit/BlogPostServiceTest.php', $path);
        $this->assertStringStartsWith(base_path(), $path);
    }

    public function test_get_test_data_dir_returns_correct_path(): void
    {
        $dir = $this->invokeProtected('getTestDataDir', 'BlogPost');
        $this->assertStringEndsWith('tests/Data/blog_post', $dir);
        $this->assertStringStartsWith(base_path(), $dir);
    }

    // ──── Writer methods ────────────────────────────────────────

    public function test_create_service_writes_file(): void
    {
        File::shouldReceive('put')
            ->once()
            ->withArgs(fn($path) => str_contains($path, 'Services/BlogPostService.php'))
            ->andReturn(100);

        $this->invokeProtected('createService', 'BlogPost', false, false);
    }

    public function test_create_controller_writes_file(): void
    {
        File::shouldReceive('put')
            ->once()
            ->withArgs(fn($path) => str_contains($path, 'Controllers/BlogPostController.php'))
            ->andReturn(100);

        $this->invokeProtected('createController', 'BlogPost', false);
    }

    public function test_register_routes_appends_to_api(): void
    {
        File::partialMock();
        File::shouldReceive('get')
            ->once()
            ->withArgs(fn($path) => str_contains($path, 'routes/api.php'))
            ->andReturn('<?php' . "\n");
        File::shouldReceive('append')
            ->once()
            ->withArgs(fn($path, $line) => str_contains($line, "Route::apiResource('blog_posts'"))
            ->andReturnNull();

        $this->invokeProtected('registerRoutes', 'BlogPost');
    }

    public function test_register_routes_skips_if_already_registered(): void
    {
        File::shouldReceive('get')
            ->once()
            ->andReturn("BlogPostController::class");
        File::shouldReceive('append')->never();

        $this->invokeProtected('registerRoutes', 'BlogPost');
    }

    public function test_create_feature_test_creates_directory_and_writes(): void
    {
        File::shouldReceive('ensureDirectoryExists')->once()->andReturnTrue();
        File::shouldReceive('put')
            ->once()
            ->withArgs(fn($path, $stub) => str_contains($stub, 'class BlogPostControllerTest'))
            ->andReturn(100);

        $this->invokeProtected('createFeatureTest', 'BlogPost');
    }

    public function test_create_unit_test_creates_directory_and_writes(): void
    {
        File::shouldReceive('ensureDirectoryExists')->once()->andReturnTrue();
        File::shouldReceive('put')
            ->once()
            ->withArgs(fn($path, $stub) => str_contains($stub, 'class BlogPostServiceTest'))
            ->andReturn(100);

        $this->invokeProtected('createUnitTest', 'BlogPost');
    }

    public function test_create_test_data_writes_payload_files(): void
    {
        File::shouldReceive('ensureDirectoryExists')->once()->andReturnTrue();
        File::shouldReceive('put')->twice()->andReturn(100);

        $this->invokeProtected('createTestData', 'BlogPost');
    }

    // ──── Model modifier methods ────────────────────────────────

    public function test_add_fillable_to_model_without_soft_deletes(): void
    {
        $content = "<?php\n\nnamespace App;\n\nuse Illuminate\\Database\\Eloquent\\Model;\nuse Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\n\nclass BlogPost extends Model\n{\n    use HasFactory;\n}\n";

        File::shouldReceive('get')
            ->once()
            ->andReturn($content);
        File::shouldReceive('put')
            ->once()
            ->withArgs(fn($path, $updated) => str_contains($updated, '$fillable'))
            ->andReturn(100);

        $this->invokeProtected('addFillableToModel', 'BlogPost');
    }

    public function test_add_fillable_to_model_with_soft_deletes(): void
    {
        $content = "<?php\n\nnamespace App;\n\nuse Illuminate\\Database\\Eloquent\\Model;\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;\nuse Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\n\nclass BlogPost extends Model\n{\n    use HasFactory;\n    use SoftDeletes;\n}\n";

        File::shouldReceive('get')
            ->once()
            ->andReturn($content);
        File::shouldReceive('put')
            ->once()
            ->withArgs(fn($path, $updated) => str_contains($updated, '$fillable'))
            ->andReturn(100);

        $this->invokeProtected('addFillableToModel', 'BlogPost');
    }

    public function test_add_fillable_to_model_skips_if_exists(): void
    {
        $content = "protected \$fillable = [];";

        File::shouldReceive('get')
            ->once()
            ->andReturn($content);
        File::shouldReceive('put')->never();

        $this->invokeProtected('addFillableToModel', 'BlogPost');
    }

    public function test_add_soft_deletes_to_model(): void
    {
        $content = "<?php\n\nnamespace App;\n\nuse Illuminate\\Database\\Eloquent\\Model;\nuse Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\n\nclass BlogPost extends Model\n{\n    use HasFactory;\n}\n";

        File::shouldReceive('get')
            ->once()
            ->andReturn($content);
        File::shouldReceive('put')
            ->once()
            ->withArgs(fn($path, $updated) =>
                str_contains($updated, 'use SoftDeletes;')
            )->andReturn(100);

        $this->invokeProtected('addSoftDeletesToModel', 'BlogPost');
    }

    public function test_add_soft_deletes_to_model_skips_if_exists(): void
    {
        File::shouldReceive('get')
            ->once()
            ->andReturn('use SoftDeletes;');
        File::shouldReceive('put')->never();

        $this->invokeProtected('addSoftDeletesToModel', 'BlogPost');
    }

    public function test_add_soft_deletes_to_migration(): void
    {
        $dir = database_path('migrations');
        $path = "$dir/2024_01_01_000000_create_blog_posts_table.php";
        File::ensureDirectoryExists($dir);

        try {
            File::put($path, <<<'PHP'
<?php
// ...
            $table->timestamps();
// ...
PHP
            );

            $this->invokeProtected('addSoftDeletesToMigration', 'BlogPost');

            $updated = File::get($path);
            $this->assertStringContainsString('softDeletes', $updated);
        } finally {
            File::delete($path);
        }
    }

    public function test_add_soft_deletes_to_migration_skips_if_exists(): void
    {
        $dir = database_path('migrations');
        $path = "$dir/2024_01_01_000000_create_blog_posts_table.php";
        File::ensureDirectoryExists($dir);

        try {
            File::put($path, <<<'PHP'
<?php
// ...
            $table->softDeletes();
// ...
PHP
            );

            $this->invokeProtected('addSoftDeletesToMigration', 'BlogPost');

            $this->assertStringContainsString('softDeletes', File::get($path));
        } finally {
            File::delete($path);
        }
    }

    public function test_add_data_columns_to_migration(): void
    {
        $dir = database_path('migrations');
        $path = "$dir/2024_01_01_000000_create_blog_posts_table.php";
        File::ensureDirectoryExists($dir);

        try {
            File::put($path, <<<'PHP'
<?php
// ...
            $table->id();
            $table->timestamps();
// ...
PHP
            );

            $this->invokeProtected('addDataColumnsToMigration', 'BlogPost');

            $updated = File::get($path);
            $this->assertStringContainsString("\$table->string('name')", $updated);
            $this->assertStringContainsString("\$table->text('description')", $updated);
        } finally {
            File::delete($path);
        }
    }

    public function test_add_data_columns_to_migration_skips_if_exists(): void
    {
        $dir = database_path('migrations');
        $path = "$dir/2024_01_01_000000_create_blog_posts_table.php";
        File::ensureDirectoryExists($dir);

        try {
            File::put($path, <<<'PHP'
<?php
// ...
            $table->string('name');
// ...
PHP
            );

            $this->invokeProtected('addDataColumnsToMigration', 'BlogPost');

            $this->assertStringContainsString("\$table->string('name')", File::get($path));
        } finally {
            File::delete($path);
        }
    }

    public function test_add_factory_defaults(): void
    {
        $content = <<<'PHP'
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BlogPostFactory extends Factory
{
    public function definition(): array
    {
        return [
        ];
    }
}
PHP;

        File::shouldReceive('get')
            ->once()
            ->andReturn($content);
        File::shouldReceive('put')
            ->once()
            ->withArgs(fn($path, $updated) =>
                str_contains($updated, "'name' => fake()->name()")
            )->andReturn(100);

        $this->invokeProtected('addFactoryDefaults', 'BlogPost');
    }

    public function test_add_factory_defaults_skips_if_exists(): void
    {
        File::shouldReceive('get')
            ->once()
            ->andReturn("'name' =>");
        File::shouldReceive('put')->never();

        $this->invokeProtected('addFactoryDefaults', 'BlogPost');
    }

    // ──── Migration file not found edge cases ───────────────────

    public function test_add_soft_deletes_to_migration_handles_missing_file(): void
    {
        File::shouldReceive('put')->never();
        $this->invokeProtected('addSoftDeletesToMigration', 'NonExistent');
    }

    public function test_add_data_columns_to_migration_handles_missing_file(): void
    {
        File::shouldReceive('put')->never();
        $this->invokeProtected('addDataColumnsToMigration', 'NonExistent');
    }

    // ──── handle method (flag routing) ─────────────────────────

    private function setInput(CreateCrudStack $command, array $params): void
    {
        $input = new ArrayInput($params);
        $input->bind($command->getDefinition());
        $ref = new \ReflectionProperty(Command::class, 'input');
        $ref->setAccessible(true);
        $ref->setValue($command, $input);
    }

    private function realMethods(): array
    {
        return [
            'call', 'info',
            'addSoftDeletesToModel', 'addSoftDeletesToMigration',
            'addDataColumnsToMigration', 'addFactoryDefaults', 'addFillableToModel',
            'getNames', 'getControllerPath', 'getServicePath',
            'getControllerStub', 'getServiceStub', 'getRouteLine',
            'createService', 'createController', 'registerRoutes',
            'createFeatureTest', 'createUnitTest', 'createTestData',
            'getFeatureTestStub', 'getUnitTestStub', 'getTestDataFiles',
            'getFeatureTestPath', 'getUnitTestPath', 'getTestDataDir',
        ];
    }

    public function test_handle_calls_soft_deletes_and_tests_by_default(): void
    {
        $command = $this->getMockBuilder(CreateCrudStack::class)
            ->enableOriginalConstructor()
            ->onlyMethods($this->realMethods())
            ->getMock();

        $command->expects($this->exactly(2))->method('call')->willReturn(0);
        $command->expects($this->any())->method('info')->willReturn(null);
        $command->expects($this->once())->method('addSoftDeletesToModel')->with('BlogPost');
        $command->expects($this->once())->method('addSoftDeletesToMigration')->with('BlogPost');
        $command->expects($this->once())->method('addDataColumnsToMigration')->with('BlogPost');
        $command->expects($this->once())->method('addFactoryDefaults')->with('BlogPost');
        $command->expects($this->once())->method('addFillableToModel')->with('BlogPost');
        $command->expects($this->once())->method('createService')->with('BlogPost', false, false);
        $command->expects($this->once())->method('createController')->with('BlogPost', false);
        $command->expects($this->once())->method('registerRoutes')->with('BlogPost');
        $command->expects($this->once())->method('createFeatureTest')->with('BlogPost');
        $command->expects($this->once())->method('createUnitTest')->with('BlogPost');
        $command->expects($this->once())->method('createTestData')->with('BlogPost');

        $this->setInput($command, ['name' => 'BlogPost']);
        $command->handle();
    }

    public function test_handle_with_no_soft_skips_soft_deletes(): void
    {
        $command = $this->getMockBuilder(CreateCrudStack::class)
            ->enableOriginalConstructor()
            ->onlyMethods($this->realMethods())
            ->getMock();

        $command->expects($this->exactly(2))->method('call')->willReturn(0);
        $command->expects($this->any())->method('info')->willReturn(null);
        $command->expects($this->never())->method('addSoftDeletesToModel');
        $command->expects($this->never())->method('addSoftDeletesToMigration');
        $command->expects($this->once())->method('addDataColumnsToMigration');
        $command->expects($this->once())->method('addFactoryDefaults');
        $command->expects($this->once())->method('addFillableToModel');
        $command->expects($this->once())->method('createService')->with('BlogPost', false, true);
        $command->expects($this->once())->method('createController');
        $command->expects($this->once())->method('registerRoutes');
        $command->expects($this->once())->method('createFeatureTest');
        $command->expects($this->once())->method('createUnitTest');
        $command->expects($this->once())->method('createTestData');

        $this->setInput($command, ['name' => 'BlogPost', '--no-soft' => true]);
        $command->handle();
    }

    public function test_handle_with_no_test_skips_test_creation(): void
    {
        $command = $this->getMockBuilder(CreateCrudStack::class)
            ->enableOriginalConstructor()
            ->onlyMethods($this->realMethods())
            ->getMock();

        $command->expects($this->exactly(2))->method('call')->willReturn(0);
        $command->expects($this->any())->method('info')->willReturn(null);
        $command->expects($this->once())->method('addSoftDeletesToModel');
        $command->expects($this->once())->method('addSoftDeletesToMigration');
        $command->expects($this->once())->method('addDataColumnsToMigration');
        $command->expects($this->once())->method('addFactoryDefaults');
        $command->expects($this->once())->method('addFillableToModel');
        $command->expects($this->once())->method('createService');
        $command->expects($this->once())->method('createController');
        $command->expects($this->once())->method('registerRoutes');
        $command->expects($this->never())->method('createFeatureTest');
        $command->expects($this->never())->method('createUnitTest');
        $command->expects($this->never())->method('createTestData');

        $this->setInput($command, ['name' => 'BlogPost', '--no-test' => true]);
        $command->handle();
    }

    public function test_handle_with_live_option_passes_live_to_writers(): void
    {
        $command = $this->getMockBuilder(CreateCrudStack::class)
            ->enableOriginalConstructor()
            ->onlyMethods($this->realMethods())
            ->getMock();

        $command->expects($this->exactly(2))->method('call')->willReturn(0);
        $command->expects($this->any())->method('info')->willReturn(null);
        $command->expects($this->once())->method('addSoftDeletesToModel');
        $command->expects($this->once())->method('addSoftDeletesToMigration');
        $command->expects($this->once())->method('addDataColumnsToMigration');
        $command->expects($this->once())->method('addFactoryDefaults');
        $command->expects($this->once())->method('addFillableToModel');
        $command->expects($this->once())->method('createService')->with('BlogPost', true, false);
        $command->expects($this->once())->method('createController')->with('BlogPost', true);
        $command->expects($this->once())->method('registerRoutes');
        $command->expects($this->once())->method('createFeatureTest');
        $command->expects($this->once())->method('createUnitTest');
        $command->expects($this->once())->method('createTestData');

        $this->setInput($command, ['name' => 'BlogPost', '--live' => true]);
        $command->handle();
    }

    public function test_handle_with_all_flags_combined(): void
    {
        $command = $this->getMockBuilder(CreateCrudStack::class)
            ->enableOriginalConstructor()
            ->onlyMethods($this->realMethods())
            ->getMock();

        $command->expects($this->exactly(2))->method('call')->willReturn(0);
        $command->expects($this->any())->method('info')->willReturn(null);
        $command->expects($this->never())->method('addSoftDeletesToModel');
        $command->expects($this->never())->method('addSoftDeletesToMigration');
        $command->expects($this->once())->method('addDataColumnsToMigration');
        $command->expects($this->once())->method('addFactoryDefaults');
        $command->expects($this->once())->method('addFillableToModel');
        $command->expects($this->once())->method('createService')->with('BlogPost', true, true);
        $command->expects($this->once())->method('createController');
        $command->expects($this->once())->method('registerRoutes');
        $command->expects($this->never())->method('createFeatureTest');
        $command->expects($this->never())->method('createUnitTest');
        $command->expects($this->never())->method('createTestData');

        $this->setInput($command, [
            'name' => 'BlogPost',
            '--no-soft' => true,
            '--no-test' => true,
            '--live' => true,
        ]);
        $command->handle();
    }
}
