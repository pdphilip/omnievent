<?php

declare(strict_types=1);

namespace PDPhilip\OmniEvent\Commands;

use Exception;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use ReflectionClass;
use RuntimeException;

use function Termwind\render;

class OmniEventMakeCommand extends GeneratorCommand
{
    public $signature = 'omnievent:make {model}';

    public $description = 'Make a new event for the specified model';

    public function handle(): int
    {
        $this->newLine();
        $model = $this->argument('model');
        //ensure casing is correct
        $model = Str::studly($model);

        //Check if model exists
        $modelCheck = config('omnievent.namespaces.models', 'App\Models').'\\'.$model;
        if (! $this->class_exists_case_sensitive($modelCheck)) {

            render((string) view('omnievent::cli.components.status', [
                'name' => 'ERROR',
                'status' => 'error',
                'title' => 'Base Model ('.$model.') was not found at: '.$modelCheck,
            ]));

            $this->newLine();

            return self::FAILURE;

        }

        //check if there already is an indexedModel for the model
        $eventModel = config('omnievent.namespaces.events', 'App\Models\Events').'\\'.$model.'Event';
        if ($this->class_exists_case_sensitive($eventModel)) {

            render((string) view('omnievent::cli.components.status', [
                'name' => 'ERROR',
                'status' => 'error',
                'title' => 'Event Model (for '.$model.' Model) already exists at: '.$eventModel,
            ]));

            $this->newLine();

            return self::FAILURE;
        }

        // Set the fully qualified class name for the new indexed model
        $name = $this->qualifyClass($eventModel);

        // Get the destination path for the generated file
        $path = $this->getPath($name);

        // Make sure the directory exists
        $this->makeDirectory($path);

        // Get the stub file contents
        $stub = $this->files->get($this->getStub());

        // Replace the stub variables
        $stub = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);

        // Write the file to disk
        $this->files->put($path, $stub);

        render((string) view('omnievent::cli.components.status', [
            'name' => 'SUCCESS',
            'status' => 'success',
            'title' => 'Event Model (for '.$model.' Model) created at: '.$eventModel,
        ]));
        render((string) view('omnievent::cli.components.code-trait', [
            'model' => $model,
        ]));

        return self::SUCCESS;
    }

    protected $type = 'Model';

    protected function getDefaultNamespace($rootNamespace): string
    {
        return config('omnievent.namespaces.events', $rootNamespace.'\\Models\Events');
    }

    protected function getStub(): string
    {
        $stubPath = __DIR__.'/../../resources/stubs/EventBase.php.stub';

        if (! file_exists($stubPath)) {
            throw new RuntimeException('Stub file not found: '.$stubPath);
        }

        return $stubPath;
    }

    public function replaceClass($stub, $name): string
    {
        $stub = parent::replaceClass($stub, $name);

        return str_replace('{{ model }}', $this->argument('model'), $stub);
    }

    public function class_exists_case_sensitive(string $class_name): bool
    {
        if (in_array($class_name, get_declared_classes(), true)) {
            return true;
        }

        try {
            $reflectionClass = new ReflectionClass($class_name);

            return $reflectionClass->getName() === $class_name;
        } catch (Exception $e) {
            // Class doesn't exist or couldn't be autoloaded
            return false;
        }

    }
}
