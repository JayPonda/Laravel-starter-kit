<?php

namespace App\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCommand extends Command
{
    protected $signature = 'generate:command
        {name : The class name of the command}
        {signature : The artisan signature (e.g., app:import-users)}
        {--file-read : Include CSV file reading with streaming handler}
        {--batch= : Process rows in batches; specify batch size (e.g., --batch=100)}';

    protected $description = 'Generate a new artisan command with optional CSV streaming and batch processing';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $signature = $this->argument('signature');
        $hasFileRead = $this->option('file-read');
        $batch = $this->option('batch');

        $path = app_path("Console/Commands/{$name}.php");

        if (File::exists($path)) {
            $this->error("Command {$name} already exists!");
            return 1;
        }

        $stub = $this->buildStub($name, $signature, $hasFileRead, $batch);

        File::put($path, $stub);
        $this->info("Created Command: {$name}");
        $this->info("Signature: {$signature}");

        return 0;
    }

    protected function buildStub(
        string $name,
        string $signature,
        bool $hasFileRead,
        ?string $batch,
    ): string {
        $commandSignature = $signature;

        if ($hasFileRead) {
            $commandSignature .= ' {filename} {--dry} {--unique-column=} {--unique-column-value=}';
        }

        if ($batch) {
            $commandSignature .= ' {--batch=}';
        }

        $uses = $hasFileRead ? "use Illuminate\Support\Facades\File;\nuse Illuminate\Support\Facades\Storage;\n" : '';
        $handlerMethod = $hasFileRead ? $this->buildHandlerMethod($batch) : '';
        $batchMethod = ($hasFileRead && $batch) ? $this->buildBatchMethod() : '';
        $handle = $hasFileRead
            ? $this->buildHandle($batch)
            : "        \$this->startedAt = now();\n        \$this->info('Command executed successfully.');\n        \$ms = \$this->startedAt->diffInMilliseconds(now());\n        \$duration = \$ms >= 1000 ? number_format(\$ms / 1000, 2) . 's' : \$ms . 'ms';\n        \$this->info(\"Command completed in {\$duration}.\");\n\n        return Command::SUCCESS;";

        return <<<PHP
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

{$uses}#[Signature('{$commandSignature}')]
#[Description('Generated command for processing')]
class {$name} extends Command
{
    protected bool \$dry = false;

    protected \$startedAt;

    public function handle()
    {
{$handle}
    }
{$handlerMethod}
{$batchMethod}
}

PHP;
    }

    protected function buildHandle(?string $batch): string
    {
        $body = '';
        $body .= "        \$this->startedAt = now();\n";
        $body .= "        \$filename = \$this->argument('filename');\n";
        $body .= "        \$this->dry = \$this->option('dry');\n";
        $body .= "        \$uniqueColumn = \$this->option('unique-column');\n";
        $body .= "        \$uniqueValue = \$this->option('unique-column-value');\n\n";
        $body .= "        if (!str_contains(\$filename, '/') && !str_contains(\$filename, '\\\\')) {\n";
        $body .= "            \$filename = Storage::disk('local')->path(\$filename);\n";
        $body .= "        }\n\n";
        $body .= "        if (!File::exists(\$filename)) {\n";
        $body .= "            \$this->error(\"File not found: {\$filename}\");\n";
        $body .= "            return Command::FAILURE;\n";
        $body .= "        }\n\n";
        $body .= "        if (\$this->dry) {\n";
        $body .= "            \$this->warn('DRY RUN — no changes will be made');\n";
        $body .= "            \$this->info('Processing will be simulated without applying changes');\n";
        $body .= "        } else {\n";
        $body .= "            \$this->info('LIVE RUN — changes will be applied');\n";
        $body .= "        }\n\n";
        $body .= "        \$lines = File::lines(\$filename);\n";
        $body .= "        \$headers = str_getcsv(\$lines->first());\n";
        $body .= "        \$total = 0;\n\n";

        if ($batch) {
            $body .= "        \$batchSize = (int) (\$this->option('batch') ?: {$batch});\n";
            $body .= "        \$batch = [];\n\n";
        }

        $body .= "        foreach (\$lines->skip(1) as \$line) {\n";
        $body .= "            \$line = trim(\$line);\n";
        $body .= "            if (\$line === '') {\n";
        $body .= "                continue;\n";
        $body .= "            }\n\n";
        $body .= "            \$values = str_getcsv(\$line);\n\n";
        $body .= "            if (count(\$headers) !== count(\$values)) {\n";
        $body .= "                continue;\n";
        $body .= "            }\n\n";
        $body .= "            \$record = array_combine(\$headers, \$values);\n\n";
        $body .= "            if (\$uniqueColumn && \$uniqueValue && (\$record[\$uniqueColumn] ?? null) === \$uniqueValue) {\n";
        $body .= "                continue;\n";
        $body .= "            }\n\n";

        if ($batch) {
            $body .= "            \$batch[] = \$record;\n\n";
            $body .= "            if (count(\$batch) >= \$batchSize) {\n";
            $body .= "                \$this->handleBatch(\$batch);\n";
            $body .= "                \$this->output->write('.');\n";
            $body .= "                \$batch = [];\n";
            $body .= "            }\n";
        } else {
            $body .= "            \$this->handleRow(\$record);\n";
            $body .= "            \$this->output->write('.');\n";
        }

        $body .= "            \$total++;\n";
        $body .= "        }\n\n";

        if ($batch) {
            $body .= "        if (!empty(\$batch)) {\n";
            $body .= "            \$this->handleBatch(\$batch);\n";
            $body .= "            \$this->output->write('.');\n";
            $body .= "        }\n\n";
        }

        $body .= "        \$this->newLine();\n";
        $body .= "        \$this->info(\"Processed {\$total} rows.\");\n";
        $body .= "        \$ms = \$this->startedAt->diffInMilliseconds(now());\n";
        $body .= "        \$duration = \$ms >= 1000 ? number_format(\$ms / 1000, 2) . 's' : \$ms . 'ms';\n";
        $body .= "        \$this->info(\"Command completed in {\$duration}.\");\n\n";
        $body .= "        return Command::SUCCESS;";

        return $body;
    }

    protected function buildHandlerMethod(?string $batch): string
    {
        if ($batch) {
            return '';
        }

        return <<<'METHODS'

    protected function handleRow(array $row): void
    {
        // TODO: Implement row processing logic
    }

METHODS;
    }

    protected function buildBatchMethod(): string
    {
        return <<<'METHODS'

    protected function handleBatch(array $rows): void
    {
        // TODO: Implement batch processing logic
    }

METHODS;
    }
}
