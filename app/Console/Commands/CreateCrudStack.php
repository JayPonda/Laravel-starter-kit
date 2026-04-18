<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateCrudStack extends Command
{
    protected $signature = 'make:crud {name}';
    protected $description = 'Create a full CRUD stack: Migration, Model, Factory, Seeder, Resource, and Controller with API routes';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $plural = Str::plural($name);
        $snake = Str::snake($name);
        $pluralSnake = Str::plural($snake);

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
        $plural = Str::plural($name);
        $variable = Str::camel($name);
        $pluralVariable = Str::camel($plural);
        $path = app_path("Http/Controllers/{$name}Controller.php");

        $stub = <<<EOD
<?php

namespace App\Http\Controllers;

use App\Models\\$name;
use App\Http\Resources\\{$name}Resource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class {$name}Controller extends Controller
{
    /**
     * Get all (paginated)
     */
    public function index()
    {
        \${$pluralVariable} = {$name}::paginate(10);
        return {$name}Resource::collection(\${$pluralVariable});
    }

    /**
     * Create new (POST)
     */
    public function store(Request \$request)
    {
        \$data = \$request->validate([
            // Add your validation rules here
        ]);

        \${$variable} = {$name}::create(\$data);
        return new {$name}Resource(\${$variable});
    }

    /**
     * Get single (GET)
     */
    public function show({$name} \${$variable})
    {
        return new {$name}Resource(\${$variable});
    }

    /**
     * Update (PUT/PATCH)
     */
    public function update(Request \$request, {$name} \${$variable})
    {
        \$data = \$request->validate([
            // Add your validation rules here
        ]);

        \${$variable}->update(\$data);
        return new {$name}Resource(\${$variable});
    }

    /**
     * Delete (DELETE)
     */
    public function destroy({$name} \${$variable})
    {
        \${$variable}->delete();
        return response()->noContent();
    }
}
EOD;

        File::put($path, $stub);
        $this->info("📄 Created Controller: {$name}Controller");
    }

    protected function registerRoutes($name)
    {
        $pluralSnake = Str::plural(Str::snake($name));
        $route = "Route::apiResource('{$pluralSnake}', \\App\Http\Controllers\\{$name}Controller::class);";
        $path = base_path('routes/api.php');

        $content = File::get($path);
        
        if (!Str::contains($content, "{$name}Controller::class")) {
            File::append($path, "\n" . $route . "\n");
            $this->info("🛣 Registered routes for {$pluralSnake}");
        }
    }
}
