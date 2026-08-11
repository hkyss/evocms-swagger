<?php

declare(strict_types=1);

use EvolutionCMS\EvoSwagger\Module;
use EvolutionCMS\EvoSwagger\OpenApiSpecGenerator;

$moduleDir = __DIR__;

$openApiJson = null;
$openApiError = null;
$moduleFrontendUrl = '';

try {
    $moduleFrontendUrl = Module::frontendUrl();

    if (!class_exists(OpenApiSpecGenerator::class)) {
        throw new RuntimeException(
            'Класс EvolutionCMS\\EvoSwagger\\OpenApiSpecGenerator не найден. '
            . 'Проверьте установку пакета и composer dump-autoload.'
        );
    }

    $openApiJson = OpenApiSpecGenerator::make()->toJson(
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    $openApiError = $e->getMessage();
}

include $moduleDir . '/frontend/index.tpl';
