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
}
