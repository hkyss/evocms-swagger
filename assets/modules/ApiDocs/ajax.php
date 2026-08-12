<?php

declare(strict_types=1);

use EvolutionCMS\EvoSwagger\ConfigStore;
use EvolutionCMS\EvoSwagger\OpenApiSpecGenerator;

if (!defined('MODX_API_MODE')) {
    define('MODX_API_MODE', true);
}
if (!defined('IN_MANAGER_MODE')) {
    define('IN_MANAGER_MODE', true);
}
if (!defined('IN_INSTALL_MODE')) {
    define('IN_INSTALL_MODE', false);
}

$documentRoot = null;
$directory = __DIR__;
while (true) {
    if (is_file($directory . '/index.php') && is_file($directory . '/core/bootstrap.php')) {
        $documentRoot = $directory;
        break;
    }

    $parent = dirname($directory);
    if ($parent === $directory) {
        break;
    }

    $directory = $parent;
}

header('Content-Type: application/json; charset=UTF-8');

/**
 * @param bool $success
 * @param string $message
 * @param array<string, mixed> $payload
 * @return never
 */
$respond = static function (bool $success, string $message, array $payload = [], int $status = 200): never {
    http_response_code($status);
    echo json_encode(
        ['success' => $success, 'message' => $message] + $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
};

if ($documentRoot === null) {
    $respond(false, 'evo-swagger: не найден веб-рут Evolution CMS относительно ' . __DIR__, [], 500);
}

require_once $documentRoot . '/index.php';

$modx = evolutionCMS();
if (empty($modx->config)) {
    $modx->getSettings();
}

if (empty($_SESSION['mgrValidated'])) {
    $respond(false, 'Требуется авторизация в менеджере.', [], 403);
}

if (!$modx->hasPermission('exec_module')) {
    $respond(false, 'Недостаточно прав для запуска модуля.', [], 403);
}

$mode = isset($_REQUEST['mode']) && is_scalar($_REQUEST['mode']) ? (string) $_REQUEST['mode'] : '';
if (!in_array($mode, ['generate', 'save'], true)) {
    $respond(false, 'Неизвестный режим: ' . ($mode === '' ? '(пусто)' : $mode), [], 400);
}

if ($mode === 'save') {
    try {
        $saved = ConfigStore::save([
            'openapi' => $_POST['openapi'] ?? '',
            'controllers_path' => $_POST['controllers_path'] ?? '',
            'output' => $_POST['output'] ?? '',
            'module_name' => $_POST['module_name'] ?? '',
        ]);
    } catch (Throwable $e) {
        $respond(false, $e->getMessage(), [], 200);
    }

    $message = 'Настройки сохранены.';
    if ($saved['warnings'] !== []) {
        $message .= ' ' . implode(' ', $saved['warnings']);
    }

    $respond(true, $message, [
        'warnings' => $saved['warnings'],
        'stored' => $saved['stored'],
        'scanPaths' => (new OpenApiSpecGenerator($saved['config']))->scanPaths(),
        'output' => $saved['config']['output'],
    ]);
}

if (!class_exists(OpenApiSpecGenerator::class)) {
    $respond(false, 'Класс OpenApiSpecGenerator не найден — проверьте установку пакета.', [], 500);
}

try {
    $generator = OpenApiSpecGenerator::make();
    $output = $generator->defaultOutputPath();
    $openapi = $generator->generate();
} catch (Throwable $e) {
    $respond(false, $e->getMessage(), [], 200);
}

$directory = dirname($output);
if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
    $respond(false, 'Не удалось создать каталог: ' . $directory, [], 200);
}

if (str_ends_with(strtolower($output), '.yaml') || str_ends_with(strtolower($output), '.yml')) {
    $written = file_put_contents($output, $openapi->toYaml()) !== false;
} else {
    $openapi->saveAs($output);
    $written = is_file($output);
}

if (!$written) {
    $respond(false, 'Не удалось записать файл: ' . $output, [], 200);
}

$respond(true, 'Спецификация записана: ' . $output, [
    'path' => $output,
    'spec' => json_decode($openapi->toJson(), true),
]);
