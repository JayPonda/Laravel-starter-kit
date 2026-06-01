<?php

namespace App\Console\Commands\Make;

use App\Services\CrudGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateCrudStack extends Command
{
    protected $signature = 'make:crud {name} {--live} {--no-soft}';
    protected $description = 'Create a full CRUD stack: Migration, Model, Factory, Seeder, Resource, and Controller with API routes';

    public function __construct(
        private CrudGeneratorService $generator
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $names = $this->generator->getNames($name);
        $live = $this->option('live');
        $noSoft = $this->option('no-soft');

        $this->info("🚀 Generating CRUD stack for {$name}...");

        // 1. Create Model, Migration, Factory, Seeder
        $this->call('make:model', ['name' => $name, '-m' => true, '-f' => true, '-s' => true]);

        // 1b. Add soft deletes if not opted out
        if (!$noSoft) {
            $this->addSoftDeletesToModel($name);
            $this->addSoftDeletesToMigration($name);
        }

        // 2. Create Resource
        $this->call('make:resource', ['name' => "{$name}Resource"]);

        // 3. Create Service
        $this->createService($name, $live, $noSoft);

        // 4. Create Controller with Boilerplate (delegates to Service)
        $this->createController($name, $live);

        // 5. Register Routes in api.php
        $this->registerRoutes($name);

        $this->info("✅ CRUD stack for {$name} created successfully!");
        $this->info("🔗 API endpoints registered in routes/api.php");
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

    protected function createService($name, bool $live, bool $noSoft)
    {
        $path = $this->generator->getServicePath($name);
        $stub = $this->generator->getServiceStub($name, $live);

        File::put($path, $stub);
        $this->info("📄 Created Service: {$name}Service");
    }

    protected function createController($name, bool $live)
    {
        $path = $this->generator->getControllerPath($name);
        $stub = $this->generator->getControllerStub($name, $live);

        File::put($path, $stub);
        $this->info("📄 Created Controller: {$name}Controller");
    }

    protected function registerRoutes($name)
    {
        $routeLine = $this->generator->getRouteLine($name);
        $path = base_path('routes/api.php');
        $content = File::get($path);
        
        if (!Str::contains($content, "{$name}Controller::class")) {
            File::append($path, "\n" . $routeLine . "\n");
            $this->info("🛣 Registered routes for " . Str::plural(Str::snake($name)));
        }
    }
}
