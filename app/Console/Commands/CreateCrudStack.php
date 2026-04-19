<?php

namespace App\Console\Commands;

use App\Services\CrudGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateCrudStack extends Command
{
    protected $signature = 'make:crud {name}';
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

        $this->info("🚀 Generating CRUD stack for {$name}...");

        // 1. Create Model, Migration, Factory, Seeder
        $this->call('make:model', ['name' => $name, '-m' => true, '-f' => true, '-s' => true]);

        // 2. Create Resource
        $this->call('make:resource', ['name' => "{$name}Resource"]);

        // 3. Create Controller with Boilerplate
        $this->createController($name);

        // 4. Register Routes in api.php
        $this->registerRoutes($name);

        $this->info("✅ CRUD stack for {$name} created successfully!");
        $this->info("🔗 API endpoints registered in routes/api.php");
    }

    protected function createController($name)
    {
        $path = $this->generator->getControllerPath($name);
        $stub = $this->generator->getControllerStub($name);

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
