<?php

namespace KRepository\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Container\Container;

class KGeneratorRepositoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {name : name of the repository}
        {--migration= : create with migration}
    ';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository.';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @return void
     */

    /**
     * @var \Illuminate\Foundation\Composer | \Illuminate\Support\Composer
     */
    private $composer;

    /**
     * Meta information for the requested migration.
     *
     * @var array
     */
    protected $meta;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;

        if (class_exists(\Illuminate\Support\Composer::class)) {
            $this->composer = app(\Illuminate\Support\Composer::class);
        } else {
            $this->composer = app(\Illuminate\Foundation\Composer::class);
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->meta['name'] = $this->generateName();
        $this->meta['namespace'] = $this->generateNamespace();
        $this->meta['filenames'] = $this->generateFileNames();
        $this->meta['paths'] = $this->generatePaths();
        $this->meta['service_provider_path'] = './config/kproviders.php';
        $this->makeRepository();
    }

    protected function makeRepository()
    {
        // [
        //   "name" => "Test"
        //   "namespace" => "Darkness\Repositories\Test"
        //   "filenames" => [
        //     "interface" => "TestRepository"
        //     "data_mapper" => "DbTestRepository"
        //     "model" => "Test"
        //     "service_provider" => "TestServiceProvider"
        //   ]
        //   "paths" => [
        //     "interface" => "./app/Repositories/Test/TestRepository.php"
        //     "data_mapper" => "./app/Repositories/Test/DbTestRepository.php"
        //     "model" => "./app/Repositories/Test/Test.php"
        //     "service_provider" => "./app/Providers/TestServiceProvider.php"
        //   ]
        //   "service_provider_path" => "./config/kproviders.php"
        // ]
        foreach ($this->meta['paths'] as $key => $path) {
            if ($this->files->exists($path)) {
                return $this->error($this->meta['filenames'][$key] . ' already exists!');
            }
        }
        $this->makeDirectory($this->meta['paths']['model']);

        $this->makeModel();
        $this->makeInterface();
        $this->makeDataMapper();
        $this->makeServiceProvider();

        if ($this->option('migration')) {
            $this->call('make:migration', [
                'name' => 'create_' . str_replace('\\', '', Str::snake(Str::plural($this->meta['name']))) . '_table',
                '--create' => str_replace('\\', '', Str::snake(Str::plural($this->meta['name'])))
            ]);
        }

        $this->composer->dumpAutoloads();
    }

    /**
     * @return array
     */
    private function generateNamespace()
    {
        return $this->getAppNamespace() . $this->cleanNamespaces(config('krepository.path')) . '\\' . Str::plural($this->meta['name']);
    }
    /**
     * @return string
     */
    private function cleanNamespaces($str)
    {
        return str_replace('\\\\', '\\', str_replace('/', '\\', $str));
    }
    /**
     * @return array
     */
    private function generatePaths()
    {
        return [
            'interface' => './app/' . config('krepository.path') . '/' . Str::plural($this->meta['name']) .  '/' . $this->meta['filenames']['interface'] . '.php',
            'data_mapper' => './app/' . config('krepository.path') . '/' . Str::plural($this->meta['name']) .  '/' . $this->meta['filenames']['data_mapper'] . '.php',
            'model' => './app/' . config('krepository.path') . '/' . Str::plural($this->meta['name']) .  '/' . $this->meta['filenames']['model'] . '.php',
            'service_provider' => './app/Providers/' . $this->meta['filenames']['service_provider'] . '.php',
        ];
    }
    /**
     * @return array
     */
    private function generateName()
    {
        return ucfirst(preg_replace("/.*?([^\\\\\\/ ]*)$/", "$1", $this->argument('name')));
    }
    /**
     * @return array
     */
    private function generateFileNames()
    {
        return [
            'interface' => str_replace('{name}', $this->meta['name'], config('krepository.files.interface')),
            'data_mapper' => str_replace('{name}', $this->meta['name'], config('krepository.files.data_mapper')),
            'model' => str_replace('{name}', $this->meta['name'], config('krepository.files.model')),
            'service_provider' => $this->meta['name'] . 'ServiceProvider'
        ];
    }

    private function makeModel()
    {
        if (config('krepository.parent.model.config')) {
            $stub = $this->files->get(__DIR__ . '/../stubs/model_with_parent.stub');
        } else {
            $stub = $this->files->get(__DIR__ . '/../stubs/model.stub');
        }
        $this->replaceNameSpace($stub)
            ->replaceModel($stub);

        $this->files->put($this->meta['paths']['model'], $stub);

        $this->info($this->meta['filenames']['model'] . '(' . str_replace('./app/', '', $this->meta['paths']['model']) . ') created successfully.');
    }

    private function makeInterface()
    {
        $stub = $this->files->get(__DIR__ . '/../stubs/interface.stub');
        $this->replaceNameSpace($stub)
            ->replaceInterface($stub);

        $this->files->put($this->meta['paths']['interface'], $stub);

        $this->info($this->meta['filenames']['interface'] . '(' . str_replace('./app/', '', $this->meta['paths']['interface']) . ') created successfully.');
    }

    private function makeDataMapper() {
        if (config('krepository.parent.data_mapper.config')) {
            $stub = $this->files->get(__DIR__ . '/../stubs/db_with_parent.stub');
        } else {
            $stub = $this->files->get(__DIR__ . '/../stubs/db.stub');
        }
        $this->replaceNameSpace($stub)
            ->replaceModel($stub)
            ->replaceInterface($stub)
            ->replaceDataMapper($stub);

        $this->files->put($this->meta['paths']['data_mapper'], $stub);

        $this->info($this->meta['filenames']['data_mapper'] . '(' . str_replace('./app/', '', $this->meta['paths']['data_mapper']) . ') created successfully.');
    }

    private function makeServiceProvider() {
        $stub = $this->files->get(__DIR__ . '/../stubs/service_provider.stub');
        $this->replaceAppName($stub)
            ->replaceNameSpace($stub)
            ->replaceServiceProvider($stub)
            ->replaceInterface($stub)
            ->replaceDataMapper($stub);

        $this->files->put($this->meta['paths']['service_provider'], $stub);

        // register provider
        $existsProviders = config('kproviders');
        $existsProviders[] = $this->getAppNamespace() .  'Providers\\' . $this->meta['filenames']['service_provider'];
        $this->files->put($this->meta['service_provider_path'], '<?php ' . PHP_EOL);
        $this->files->append($this->meta['service_provider_path'], 'return [ ' . PHP_EOL);
        foreach ($existsProviders as $key => $value) {
            $this->files->append($this->meta['service_provider_path'], "\t" . $value . '::class,' . PHP_EOL);
        }
        $this->files->append($this->meta['service_provider_path'], '];' . PHP_EOL);

        $this->info($this->meta['filenames']['service_provider'] . '(' . str_replace('./app/', '', $this->meta['paths']['service_provider']) . ') created successfully.');
    }

    /**
     * Replace the schema for the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceNameSpace(&$stub)
    {
        $stub = str_replace('{{namespace}}', $this->meta['namespace'], $stub);
        return $this;
    }

    /**
     * Replace the schema for the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceModel(&$stub)
    {
        $stub = str_replace('{{model_name}}', $this->meta['filenames']['model'], $stub);
        $stub = str_replace('{{model_lower_name}}', lcfirst($this->meta['filenames']['model']), $stub);
        if (config('krepository.parent.model.config')) {
            $stub = str_replace('{{parent_model_namespace}}', config('krepository.parent.model.namespace'), $stub);
            $stub = str_replace('{{parent_model_name}}', config('krepository.parent.model.class_name'), $stub);
        }
        return $this;
    }

    /**
     * Replace the schema for the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceInterface(&$stub)
    {
        $stub = str_replace('{{interface_name}}', $this->meta['filenames']['interface'], $stub);
        return $this;
    }

    /**
     * Replace the schema for the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceDataMapper(&$stub)
    {
        $stub = str_replace('{{data_mapper_name}}', $this->meta['filenames']['data_mapper'], $stub);
        if (config('krepository.parent.data_mapper.config')) {
            $stub = str_replace('{{parent_namespace}}', config('krepository.parent.data_mapper.namespace'), $stub);
            $stub = str_replace('{{parent_name}}', config('krepository.parent.data_mapper.class_name'), $stub);
        }
        return $this;
    }

    /**
     * Replace the schema for the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceAppName(&$stub)
    {
        $stub = str_replace('{{app_name}}', $this->getAppNamespace(), $stub);
        return $this;
    }
    /**
     * Replace the schema for the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceServiceProvider(&$stub)
    {
        $stub = str_replace('{{service_provider_name}}', $this->meta['filenames']['service_provider'], $stub);
        return $this;
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true, true);
        }
    }

    /**
     * Get the application namespace.
     *
     * @return string
     */
    protected function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }

}
