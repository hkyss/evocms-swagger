<?php

declare(strict_types=1);

use EvolutionCMS\EvoSwagger\ConfigStore;
use EvolutionCMS\EvoSwagger\Module;
use EvolutionCMS\EvoSwagger\OpenApiSpecGenerator;

$moduleDir = __DIR__;

$openApiJson = null;
$openApiError = null;
$moduleUrl = '';
$moduleFrontendUrl = '';

$config = Module::config();
$openApiVersions = ConfigStore::openApiVersions();

$formOutput = Module::relativizePath($config['output']);
$configFile = Module::relativizePath(Module::configFile());
$basePath = rtrim((string) MODX_BASE_PATH, '/');
$pathRows = [];

$hasOperations = false;

try {
    $moduleUrl = Module::moduleUrl();
    $moduleFrontendUrl = Module::frontendUrl();

    if (!class_exists(OpenApiSpecGenerator::class)) {
        throw new RuntimeException(
            'Класс EvolutionCMS\\EvoSwagger\\OpenApiSpecGenerator не найден. '
            . 'Проверьте установку пакета и composer dump-autoload.'
        );
    }

    $generator = OpenApiSpecGenerator::make();
    $scanPaths = $generator->scanPaths();

    foreach ($generator->configuredPaths() as $path) {
        $pathRows[] = [
            'path' => Module::relativizePath($path),
            'found' => in_array($path, $scanPaths, true),
        ];
    }

    $openApiJson = $generator->toJson(
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $decoded = json_decode($openApiJson, true);
    $hasOperations = is_array($decoded) && !empty($decoded['paths']);
} catch (Throwable $e) {
    $openApiError = $e->getMessage();
}

include $moduleDir . '/frontend/index.tpl';
