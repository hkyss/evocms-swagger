<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoSwagger;

use RuntimeException;

final class ConfigStore
{
    private const OPENAPI_VERSIONS = ['3.0.0', '3.1.0'];

    /**
     * @param array<string, mixed> $input
     * @return array{
     *     config: array{openapi: string, controllers_path: list<string>, output: string, module_name: string},
     *     stored: array<string, mixed>,
     *     warnings: list<string>
     * }
     * @throws RuntimeException
     */
    public static function save(array $input): array
    {
        $warnings = [];

        $openapi = trim((string) ($input['openapi'] ?? ''));
        if (!in_array($openapi, self::OPENAPI_VERSIONS, true)) {
            throw new RuntimeException(
                'Версия OpenAPI должна быть одной из: ' . implode(', ', self::OPENAPI_VERSIONS) . '.'
            );
        }

        $moduleName = trim((string) ($input['module_name'] ?? ''));
        if ($moduleName === '') {
            throw new RuntimeException('Название модуля не может быть пустым.');
        }
        if (mb_strlen($moduleName) > 100) {
            throw new RuntimeException('Название модуля длиннее 100 символов.');
        }

        $paths = self::parsePaths($input['controllers_path'] ?? []);
        if ($paths === []) {
            throw new RuntimeException('Укажите хотя бы один каталог сканирования.');
        }

        foreach ($paths as $path) {
            if (!is_dir(Module::resolvePath($path))) {
                $warnings[] = 'Каталог не найден: ' . $path;
            }
        }

        $output = trim((string) ($input['output'] ?? ''));
        $output = $output === '' ? null : Module::relativizePath($output);

        if ($output !== null) {
            if (str_ends_with($output, '/') || is_dir(Module::resolvePath($output))) {
                throw new RuntimeException('Путь файла спецификации должен указывать на файл, а не на каталог.');
            }

            if (!preg_match('/\.(json|ya?ml)$/i', $output)) {
                throw new RuntimeException('Файл спецификации должен иметь расширение .json, .yaml или .yml.');
            }
        }

        $stored = [
            'openapi' => $openapi,
            'controllers_path' => $paths,
            'output' => $output,
            'module_name' => $moduleName,
        ];

        self::write($stored);

        if (function_exists('config')) {
            config(['evo-swagger' => $stored]);
        }

        return ['config' => Module::config(), 'stored' => $stored, 'warnings' => $warnings];
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    private static function parsePaths(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/\R/', $raw) ?: [];
        }

        if (!is_array($raw)) {
            return [];
        }

        $paths = [];
        foreach ($raw as $line) {
            if (!is_string($line)) {
                continue;
            }

            $line = Module::relativizePath(trim($line));
            if ($line !== '' && !in_array($line, $paths, true)) {
                $paths[] = $line;
            }
        }

        return $paths;
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     * @throws RuntimeException
     */
    private static function write(array $data): void
    {
        $file = Module::configFile();
        $directory = dirname($file);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Не удалось создать каталог конфигурации: ' . $directory);
        }

        if (is_file($file) && !is_writable($file)) {
            throw new RuntimeException('Файл конфигурации недоступен для записи: ' . $file);
        }

        $body = "<?php\n\n"
            . "declare(strict_types=1);\n\n"
            . 'return ' . self::export($data) . ";\n";

        $temp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';

        if (file_put_contents($temp, $body, LOCK_EX) === false) {
            @unlink($temp);
            throw new RuntimeException('Не удалось записать конфигурацию: ' . $file);
        }

        if (!rename($temp, $file)) {
            @unlink($temp);
            throw new RuntimeException('Не удалось заменить файл конфигурации: ' . $file);
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return string
     */
    private static function export(array $data): string
    {
        $lines = ['['];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $lines[] = '    ' . var_export($key, true) . ' => [';
                foreach ($value as $item) {
                    $lines[] = '        ' . var_export($item, true) . ',';
                }
                $lines[] = '    ],';

                continue;
            }

            $lines[] = '    ' . var_export($key, true) . ' => ' . var_export($value, true) . ',';
        }

        $lines[] = ']';

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    public static function openApiVersions(): array
    {
        return self::OPENAPI_VERSIONS;
    }
}
