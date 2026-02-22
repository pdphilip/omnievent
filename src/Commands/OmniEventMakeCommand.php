<?php

// Eleganced at 2026-02-22 19:15

declare(strict_types=1);

namespace PDPhilip\OmniEvent\Commands;

use Exception;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use OmniTerm\HasOmniTerm;
use ReflectionClass;
use RuntimeException;

class OmniEventMakeCommand extends GeneratorCommand
{
    use HasOmniTerm;

    public $signature = 'omnievent:make {model}';

    public $description = 'Make a new event model for the specified model';

    protected $type = 'Model';

    public function handle(): int
    {
        $this->newLine();
        $model = Str::studly($this->argument('model'));

        $modelClass = config('omnievent.namespaces.models', 'App\Models').'\\'.$model;
        if (! $this->classExistsCaseSensitive($modelClass)) {
            $this->omni->statusError('ERROR', 'Base Model ('.$model.') was not found at: '.$modelClass);
            $this->newLine();

            return self::FAILURE;
        }

        $eventModelClass = config('omnievent.namespaces.events', 'App\Models\Events').'\\'.$model.'Event';
        if ($this->classExistsCaseSensitive($eventModelClass)) {
            $this->omni->statusError('ERROR', 'Event Model (for '.$model.' Model) already exists at: '.$eventModelClass);
            $this->newLine();

            return self::FAILURE;
        }

        $name = $this->qualifyClass($eventModelClass);
        $path = $this->getPath($name);

        $this->makeDirectory($path);

        $stub = $this->files->get($this->getStub());
        $stub = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);

        $this->files->put($path, $stub);

        $this->omni->statusSuccess('SUCCESS', 'Event Model (for '.$model.' Model) created at: '.$eventModelClass);
        $this->omni->render((string) view('omnievent::cli.components.code-trait', [
            'model' => $model,
        ]));

        return self::SUCCESS;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return config('omnievent.namespaces.events', $rootNamespace.'\\Models\\Events');
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

        $modelsNamespace = config('omnievent.namespaces.models', 'App\\Models');

        $stub = str_replace('{{ namespacedModel }}', $modelsNamespace, $stub);
        $stub = str_replace('{{ model }}', Str::studly($this->argument('model')), $stub);

        return $stub;
    }

    private function classExistsCaseSensitive(string $className): bool
    {
        if (in_array($className, get_declared_classes(), true)) {
            return true;
        }

        try {
            return (new ReflectionClass($className))->getName() === $className;
        } catch (Exception) {
            return false;
        }
    }
}
