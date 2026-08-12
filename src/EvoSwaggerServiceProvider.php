<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoSwagger;

use EvolutionCMS\EvoSwagger\Console\GenerateOpenApiCommand;
use EvolutionCMS\ServiceProvider;

class EvoSwaggerServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected string $namespace = 'evo-swagger';

    /**
     * @var list<class-string>
     */
    protected array $commands = [
        GenerateOpenApiCommand::class,
    ];

    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/evo-swagger.php', 'evo-swagger');
        $this->commands($this->commands);
        $this->registerManagerModule();
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->ensureConfigPublished();

        $this->publishes([
            __DIR__ . '/../config/evo-swagger.php' => config_path('evo-swagger.php', true),
        ], 'evo-swagger-config');

        $this->publishes([
            __DIR__ . '/../assets/modules/ApiDocs' => Module::publishedRoot(),
        ], 'evo-swagger-assets');
    }

    /**
     * @return void
     */
    private function ensureConfigPublished(): void
    {
        $target = config_path('evo-swagger.php', true);
        if (is_file($target)) {
            return;
        }

        $source = __DIR__ . '/../config/evo-swagger.php';
        if (!is_file($source)) {
            return;
        }

        $dir = dirname($target);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }

        copy($source, $target);
    }

    /**
     * @return void
     */
    private function registerManagerModule(): void
    {
        $moduleFile = Module::packageModuleRoot() . '/module.php';
        if (!is_file($moduleFile)) {
            return;
        }

        $name = (string) config('evo-swagger.module_name', 'API документация');
        $this->app->registerModule($name, $moduleFile, 'fa fa-file-code-o');
    }
}
