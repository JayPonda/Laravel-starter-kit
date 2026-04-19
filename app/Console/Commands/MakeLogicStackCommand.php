<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeLogicStackCommand extends Command
{
    protected $signature = 'make:logic-stack {name}';
    protected $description = 'Generate Controller, Service, FormRequest, RequestObject, and ResponseObject';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));

        $this->generateService($name);
        $this->generateRequest($name);
        $this->generateRequestObject($name);
        $this->generateResponseObject($name);
        $this->generateController($name);

        $this->info("✅ Logic stack for {$name} created successfully!");
    }

    protected function generateService($name)
    {
        $path = app_path("Services/{$name}Service.php");
        if (!File::isDirectory(app_path('Services'))) {
            File::makeDirectory(app_path('Services'), 0755, true);
        }

        $stub = <<<EOD
<?php

namespace App\Services;

class {$name}Service
{
    public function execute(array \$data)
    {
        // Logic goes here
        return ['status' => 'success'];
    }
}
EOD;
        File::put($path, $stub);
        $this->info("📄 Created Service: {$name}Service");
    }

    protected function generateRequest($name)
    {
        $this->call('make:request', ['name' => "{$name}Request"]);
    }

    protected function generateRequestObject($name)
    {
        $path = app_path("DTOs/{$name}RequestObject.php");
        if (!File::isDirectory(app_path('DTOs'))) {
            File::makeDirectory(app_path('DTOs'), 0755, true);
        }

        $stub = <<<EOD
<?php

namespace App\DTOs;

class {$name}RequestObject
{
    public function __construct(
        public readonly array \$data
    ) {}

    public static function fromRequest(\$request): self
    {
        return new self(\$request->validated());
    }
}
EOD;
        File::put($path, $stub);
        $this->info("📄 Created DTO: {$name}RequestObject");
    }

    protected function generateResponseObject($name)
    {
        $path = app_path("DTOs/{$name}ResponseObject.php");
        if (!File::isDirectory(app_path('DTOs'))) {
            File::makeDirectory(app_path('DTOs'), 0755, true);
        }

        $stub = <<<EOD
<?php

namespace App\DTOs;

use Illuminate\Http\JsonResponse;

class {$name}ResponseObject
{
    public function __construct(
        public readonly mixed \$data,
        public readonly int \$status = 200
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'data' => \$this->data,
        ], \$this->status);
    }
}
EOD;
        File::put($path, $stub);
        $this->info("📄 Created DTO: {$name}ResponseObject");
    }

    protected function generateController($name)
    {
        $path = app_path("Http/Controllers/{$name}Controller.php");
        $stub = <<<EOD
<?php

namespace App\Http\Controllers;

use App\Http\Requests\\{$name}Request;
use App\Services\\{$name}Service;
use App\DTOs\\{$name}RequestObject;
use App\DTOs\\{$name}ResponseObject;

class {$name}Controller extends Controller
{
    public function __construct(
        protected {$name}Service \$service
    ) {}

    public function __invoke({$name}Request \$request)
    {
        \$dto = {$name}RequestObject::fromRequest(\$request);
        
        \$result = \$this->service->execute(\$dto->data);
        
        return (new {$name}ResponseObject(\$result))->toResponse();
    }
}
EOD;
        File::put($path, $stub);
        $this->info("📄 Created Controller: {$name}Controller");
    }
}
