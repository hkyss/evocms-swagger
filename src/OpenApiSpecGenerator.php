<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoSwagger;

use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;
use RuntimeException;
use Throwable;

final readonly class OpenApiSpecGenerator
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private array $config,
    ) {}

    /**
     * @return self
     */
    public static function make(): self
    {
        return new self(Module::config());
    }

    /**
     * @return OpenApi
     * @throws RuntimeException
     */
    public function generate(): OpenApi
    {
        $sources = $this->scanPaths();

        if ($sources === []) {
            throw new RuntimeException(
                'evo-swagger: controllers_path не задан или каталог не существует (core/custom/config/evo-swagger.php).'
            );
        }

        try {
            $openapi = (new Generator())
                ->setVersion((string) ($this->config['openapi'] ?? '3.0.0'))
                ->generate($sources);
        } catch (Throwable $e) {
            throw new RuntimeException('evo-swagger: генерация упала: ' . $e->getMessage(), 0, $e);
        }

        if ($openapi === null) {
            throw new RuntimeException(
                'evo-swagger: генератор вернул null (нет Info/PathItem в documentation path).'
            );
        }

        return $openapi;
    }

    /**
     * @param ?int $flags
     * @return string
     * @throws RuntimeException
     */
    public function toJson(?int $flags = null): string
    {
        return $this->generate()->toJson($flags);
    }

    /**
     * @return string
     */
    public function defaultOutputPath(): string
    {
        return (string) ($this->config['output'] ?? Module::publishedRoot() . '/docs/openapi.json');
    }

    /**
     * @return list<string>
     */
    public function scanPaths(): array
    {
        $path = $this->config['controllers_path'] ?? null;

        if (!is_string($path) || $path === '' || !is_dir($path)) {
            return [];
        }

        return [$path];
    }
}
