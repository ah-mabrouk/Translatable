<?php

namespace Mabrouk\Translatable\Http\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class MakeTranslationModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:translation-model
                            {model? : The model class name to create a translation for}
                            {--foreignKey= : The foreign key name for the translation model}
                            {--m|migration : Create a migration for the translation table}
                            {--force : Overwrite existing translation model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a translation model and optional migration for a specified translatable model';
    protected $baseDir;
    protected $namespace;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->setNamespaceAndBaseDir();
            
            if (!$this->argument('model')) {
                $modelName = $this->chooseModel();
                $this->input->setArgument('model', $modelName);
            }

            // Find and validate the parent model first
            $modelName = $this->argument('model');
            $modelPath = $this->findModelFile($modelName, $this->baseDir);
            
            if (!$modelPath) {
                throw new InvalidArgumentException("Model {$modelName} not found!");
            }
            
            $modelInfo = $this->getModelInfo();
            
            $this->ensureDirectoryExists($modelInfo['baseDir']);
            
            if (!$this->canCreateFile($modelInfo['filePath'])) {
                return 1;
            }

            $this->createTranslationModel($modelInfo);

            if ($this->option('migration')) {
                $this->createMigration($modelInfo);
            }

            $this->components->info("Translation Model [{$modelInfo['filePath']}] created successfully.");
            return 0;
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Set the namespace and base directory for the models.
     *
     * @return void
     */
    protected function setNamespaceAndBaseDir(): void
    {
        $this->namespace = config('translatable.translation_models_path', 'App\\Models');
        $this->baseDir = app_path(str_replace('App\\', '', str_replace('App\\\\', '/', $this->namespace)));
    }

    /**
     * Get model information including paths and names.
     *
     * @return array
     */
    protected function getModelInfo(): array
    {
        $parentModelClassname = $this->argument('model');
        $namespace = $this->namespace;
        $baseDir = $this->baseDir;

        if (strpos($parentModelClassname, '/') !== false) {
            [$subdirectory, $parentModelClassname, $namespace, $baseDir] = $this->handleSubdirectory($parentModelClassname);
        }

        // Clean up the model name to ensure proper format
        $parentModelClassname = $this->normalizeModelName($parentModelClassname);

        $modelPath = $this->findModelFile($parentModelClassname, $baseDir);
        $translatedFields = $this->extractTranslatedAttributes($modelPath);

        $translationModelName = $parentModelClassname . 'Translation';
        $parentModelName = lcfirst($parentModelClassname); // Convert to camelCase
        $tableName = $this->formatTableName($parentModelClassname); // Use snake_case for table/column names
        $foreignKey = $this->option('foreignKey') ?: $tableName . '_id';
        $filePath = $baseDir . '/' . $translationModelName . '.php';

        return [
            'parentModelClassname' => $parentModelClassname,
            'translationModelName' => $translationModelName,
            'parentModelName' => $parentModelName,
            'foreignKey' => $foreignKey,
            'filePath' => $filePath,
            'namespace' => $namespace,
            'baseDir' => $baseDir,
            'translatedFields' => $translatedFields
        ];
    }

    /**
     * Normalize the model name to StudlyCase.
     *
     * @param string $name
     * @return string
     */
    protected function normalizeModelName(string $name): string
    {
        // Remove any spaces and convert to StudlyCase
        $name = str_replace(['-', '_', ' '], '', ucwords($name, '-_ '));
        
        // Ensure the first character is uppercase
        return ucfirst($name);
    }

    /**
     * Format model name to snake_case for table name.
     *
     * @param string $name
     * @return string
     */
    protected function formatTableName(string $name): string
    {
        // Convert camel case to snake case
        $name = preg_replace('/([a-z])([A-Z])/', '$1_$2', $name);
        return strtolower($name);
    }

    /**
     * Handle subdirectory in model path.
     *
     * @param string $parentModelClassname
     * @return array
     */
    protected function handleSubdirectory(string $parentModelClassname): array
    {
        $parts = explode('/', $parentModelClassname);
        $modelName = array_pop($parts);
        $subdirectory = implode('/', $parts);
        $namespace = $this->namespace . '\\' . str_replace('/', '\\', $subdirectory);
        $baseDir = $this->baseDir . '/' . $subdirectory;

        return [$subdirectory, $modelName, $namespace, $baseDir];
    }

    /**
     * Ensure the directory exists, create if it doesn't.
     *
     * @param string $directory
     * @return void
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Check if the file can be created.
     *
     * @param string $filePath
     * @return bool
     */
    protected function canCreateFile(string $filePath): bool
    {
        if (File::exists($filePath) && !$this->option('force')) {
            $this->components->error("Translation Model [{$filePath}] already exists.");
            return false;
        }

        return true;
    }

    /**
     * Create the translation model file.
     *
     * @param array $modelInfo
     * @return void
     */
    protected function createTranslationModel(array $modelInfo): void
    {
        $stubPath = __DIR__ . '/../../stubs/translation-model.stub';
        $stub = File::get($stubPath);

        if (!$stub) {
            throw new InvalidArgumentException("Translation model stub file not found at {$stubPath}");
        }

        // Format the fields string only if there are translated fields
        $fieldsString = empty($modelInfo['translatedFields']) ? '' : ",\n        '" . implode("',\n        '", $modelInfo['translatedFields']) . "'";

        $stub = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ parentModelName }}',
                '{{ parentModelClassname }}',
                '{{ foreignKey }}',
                '{{ fields }}'
            ],
            [
                $modelInfo['namespace'],
                $modelInfo['translationModelName'],
                $modelInfo['parentModelName'],
                $modelInfo['parentModelClassname'],
                $modelInfo['foreignKey'],
                $fieldsString
            ],
            $stub
        );

        File::put($modelInfo['filePath'], $stub);
    }

    /**
     * Let user choose a model from available models.
     *
     * @return string
     */
    protected function chooseModel(): string
    {
        $models = $this->getAvailableModels();

        if (empty($models)) {
            throw new InvalidArgumentException('No models found in ' . $this->namespace);
        }

        return $this->choice(
            'Which model would you like to create a translation for?',
            $models
        );
    }

    /**
     * Get available models in the models directory.
     *
     * @return array
     */
    protected function getAvailableModels(): array
    {
        if (!File::isDirectory($this->baseDir)) {
            return [];
        }

        $files = File::allFiles($this->baseDir);
        $models = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $name = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
                // Skip existing translation models
                if (!str_ends_with($name, 'Translation')) {
                    $models[] = $name;
                }
            }
        }

        return $models;
    }
    
    /**
     * Find the model file.
     *
     * @param string $modelName
     * @param string $basePath
     * @return string|null
     */
    protected function findModelFile(string $modelName, string $basePath): ?string
    {
        $modelFile = $basePath . '/' . $modelName . '.php';
        
        if (File::exists($modelFile)) {
            return $modelFile;
        }
        
        $files = File::allFiles($basePath);
        foreach ($files as $file) {
            if ($file->getFilename() === $modelName . '.php') {
                return $file->getPathname();
            }
        }
        
        return null;
    }

    /**
     * Create a migration for the translation table.
     *
     * @param array $modelInfo
     * @return void
     */
    protected function createMigration(array $modelInfo): void
    {
        $tableName = Str::snake(Str::plural($modelInfo['parentModelClassname'])) . '_translations';
        
        $stub = File::get(__DIR__ . '/../../stubs/translation-migration.stub');

        if (!$stub) {
            throw new InvalidArgumentException("Translation migration stub file not found at {$stubPath}");
        }

        $stub = str_replace(
            ['{{ table }}', '{{ foreign_key }}', '{{ model }}'],
            [
                $tableName,
                $modelInfo['foreignKey'],
                $modelInfo['parentModelClassname']
            ],
            $stub
        );

        $timestamp = date('Y_m_d_His');
        $filename = $timestamp . '_create_' . $tableName . '_table.php';

        $path = database_path('migrations/' . $filename);

        File::put($path, $stub);

        $this->components->info("Migration [{$path}] created successfully.");
    }

    /**
     * Extract translated attributes from the parent model.
     *
     * @param string $modelPath
     * @return array
     */
    protected function extractTranslatedAttributes(string $modelPath): array
    {
        $content = File::get($modelPath);
        $warningMessage = '';

        if (!preg_match('/public\s+\$translatedAttributes\s*=\s*\[(.*?)\]/s', $content, $matches)) {
            $warningMessage = 'Could not find translatedAttributes property in the model.';
        } else {
            $attributesString = $matches[1];
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $attributesString, $attributes);

            if (!empty($attributes[1])) {
                return $attributes[1];
            }
            $warningMessage = 'No translated attributes found in the model.';
        }

        $this->components->warn($warningMessage);
        if (!$this->confirm('Would you like to continue without auto-filling the fillable attributes?', true)) {
            $this->components->info('Command cancelled.');
            exit(1);
        }
        
        return [];
    }
}
