<?php

declare(strict_types=1);

/**
 * Конфиг evo-swagger.
 *
 * Автокопия при первом boot: core/custom/config/evo-swagger.php
 * Повторно: php artisan vendor:publish --provider="EvolutionCMS\EvoSwagger\EvoSwaggerServiceProvider" --tag=evo-swagger-config
 */
return [
    /** Версия OpenAPI для генератора. */
    'openapi' => '3.0.0',

    /** Абсолютный путь к каталогу с #[OA*] атрибутами. */
    'controllers_path' => '',

    /**
     * Путь записи openapi.json|yaml (apidocs:generate).
     * null → {MODX_BASE_PATH}/assets/modules/ApiDocs/docs/openapi.json
     */
    'output' => null,

    /** Имя модуля в менеджере CMS. */
    'module_name' => 'API документация',
];
