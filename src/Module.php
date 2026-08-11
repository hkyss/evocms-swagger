<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoSwagger;

use RuntimeException;

final class Module
{
    /**
     * @return string
     */
    public static function packageRoot(): string
    {
        return dirname(__DIR__);
    }

    /**
     * @return string
     */
    public static function packageModuleRoot(): string
    {
        return self::packageRoot() . '/assets/modules/ApiDocs';
    }

    /**
     * @return string
     */
    public static function publishedRoot(): string
    {
        return self::basePath() . '/assets/modules/ApiDocs';
    }

    /**
     * @return string
     */
    public static function assetsRoot(): string
    {
        $published = self::publishedRoot();
        if (is_dir($published . '/frontend')) {
            return $published;
        }

        return self::packageModuleRoot();
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    public static function frontendUrl(): string
    {
        $assetsRoot = self::normalizePath(self::assetsRoot());
        $basePath = self::basePath();

        $realBase = self::normalizePath((string) (realpath($basePath) ?: $basePath));
        $realAssets = self::normalizePath((string) (realpath($assetsRoot) ?: $assetsRoot));

        if ($realAssets !== $realBase && !str_starts_with($realAssets, $realBase . '/')) {
            throw new RuntimeException(
                'evo-swagger: frontend вне MODX_BASE_PATH (' . $realAssets . '). '
                . 'Опубликуйте UI: php artisan vendor:publish '
                . '--provider="EvolutionCMS\\EvoSwagger\\EvoSwaggerServiceProvider" --tag=evo-swagger-assets'
            );
        }

        $relative = $realAssets === $realBase
            ? ''
            : substr($realAssets, strlen($realBase) + 1);

        $prefix = rtrim((string) MODX_SITE_URL, '/');
        $path = $relative === '' ? 'frontend' : $relative . '/frontend';

        return $prefix . '/' . $path;
    }

    /**
     * @return array{
     *     openapi: string,
     *     controllers_path: string,
     *     output: string,
     *     module_name: string
     * }
     */
    public static function config(): array
    {
        if (function_exists('config')) {
            $fromApp = config('evo-swagger');
            if (is_array($fromApp)) {
                return self::normalizeConfig($fromApp);
            }
        }

        $file = self::packageRoot() . '/config/evo-swagger.php';
        if (is_file($file)) {
            /** @var array<string, mixed> $local */
            $local = require $file;

            return self::normalizeConfig($local);
        }

        return self::normalizeConfig([]);
    }

    /**
     * @param array<string, mixed> $config
     * @return array{
     *     openapi: string,
     *     controllers_path: string,
     *     output: string,
     *     module_name: string
     * }
     */
    private static function normalizeConfig(array $config): array
    {
        $output = $config['output'] ?? null;
        if (!is_string($output) || $output === '') {
            $output = self::publishedRoot() . '/docs/openapi.json';
        }

        return [
            'openapi' => (string) ($config['openapi'] ?? '3.0.0'),
            'controllers_path' => (string) ($config['controllers_path'] ?? ''),
            'output' => $output,
            'module_name' => (string) ($config['module_name'] ?? 'API документация'),
        ];
    }

    /**
     * @return string
     */
    private static function basePath(): string
    {
        return self::normalizePath(rtrim((string) MODX_BASE_PATH, '/\\'));
    }

    /**
     * @param string $path
     * @return string
     */
    private static function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
