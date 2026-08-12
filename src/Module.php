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
    public static function moduleUrl(): string
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

        return $relative === '' ? $prefix : $prefix . '/' . $relative;
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    public static function frontendUrl(): string
    {
        return self::moduleUrl() . '/frontend';
    }

    /**
     * @return array{
     *     openapi: string,
     *     controllers_path: list<string>,
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
            $local = require $file;

            return self::normalizeConfig($local);
        }

        return self::normalizeConfig([]);
    }

    /**
     * @param array<string, mixed> $config
     * @return array{
     *     openapi: string,
     *     controllers_path: list<string>,
     *     output: string,
     *     module_name: string
     * }
     */
    private static function normalizeConfig(array $config): array
    {
        $output = $config['output'] ?? null;
        $output = is_string($output) && trim($output) !== ''
            ? self::resolvePath(trim($output))
            : self::publishedRoot() . '/docs/openapi.json';

        return [
            'openapi' => (string) ($config['openapi'] ?? '3.0.0'),
            'controllers_path' => self::normalizePaths($config['controllers_path'] ?? []),
            'output' => $output,
            'module_name' => (string) ($config['module_name'] ?? 'API документация'),
        ];
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private static function normalizePaths(mixed $value): array
    {
        if (is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $paths = [];
        foreach ($value as $path) {
            if (!is_string($path)) {
                continue;
            }

            $path = trim($path);
            if ($path === '') {
                continue;
            }

            $absolute = self::resolvePath($path);

            if (!in_array($absolute, $paths, true)) {
                $paths[] = $absolute;
            }
        }

        return $paths;
    }

    /**
     * @param string $path
     * @return string
     */
    public static function resolvePath(string $path): string
    {
        $path = trim($path);

        return self::canonicalize(self::isAbsolutePath($path)
            ? $path
            : self::basePath() . '/' . trim(self::normalizePath($path), '/'));
    }

    /**
     * @param string $path
     * @return string
     */
    public static function relativizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        $path = self::canonicalize($path);

        $base = self::basePath();
        if ($path === $base) {
            return '';
        }

        return str_starts_with($path, $base . '/')
            ? substr($path, strlen($base) + 1)
            : $path;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || (bool) preg_match('#^[A-Za-z]:[\\\\/]#', $path);
    }

    /**
     * @return string
     */
    public static function configFile(): string
    {
        return self::canonicalize(config_path('evo-swagger.php', true));
    }

    /**
     * @param string $path
     * @return string
     */
    private static function canonicalize(string $path): string
    {
        $path = self::normalizePath($path);
        $isAbsolute = self::isAbsolutePath($path);

        $result = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..' && $result !== [] && end($result) !== '..') {
                array_pop($result);

                continue;
            }

            $result[] = $segment;
        }

        return ($isAbsolute ? '/' : '') . implode('/', $result);
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
