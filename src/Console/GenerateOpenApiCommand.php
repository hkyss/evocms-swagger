<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoSwagger\Console;

use EvolutionCMS\EvoSwagger\Module;
use EvolutionCMS\EvoSwagger\OpenApiSpecGenerator;
use Illuminate\Console\Command;
use Throwable;

final class GenerateOpenApiCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'apidocs:generate
                            {--output= : Путь к файлу (по умолчанию из config)}
                            {--format=json : json|yaml}';

    /**
     * @var string
     */
    protected $description = 'Сгенерировать OpenAPI документацию';

    /**
     * @return int
     */
    public function handle(): int
    {
        $format = strtolower((string) $this->option('format'));
        if (!in_array($format, ['json', 'yaml'], true)) {
            $this->error('format должен быть json или yaml.');

            return self::FAILURE;
        }

        $generator = OpenApiSpecGenerator::make();
        $custom = (string) ($this->option('output') ?? '');
        $output = $custom !== ''
            ? Module::resolvePath($custom)
            : $generator->defaultOutputPath();
        if ($format === 'yaml' && str_ends_with($output, '.json')) {
            $output = substr($output, 0, -5) . '.yaml';
        }

        $this->info('Scan:');
        foreach ($generator->scanPaths() as $source) {
            $this->line('  - ' . $source);
        }

        try {
            $openapi = $generator->generate();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $directory = dirname($output);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $this->error("Не удалось создать каталог: {$directory}");

            return self::FAILURE;
        }

        if ($format === 'yaml') {
            file_put_contents($output, $openapi->toYaml());
        } else {
            $openapi->saveAs($output);
        }

        $this->info('Written: ' . $output);

        return self::SUCCESS;
    }
}
