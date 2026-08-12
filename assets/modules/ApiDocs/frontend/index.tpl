<?php

declare(strict_types=1);

/**
 * @var string $moduleFrontendUrl
 * @var string|null $openApiJson
 * @var string|null $openApiError
 */

$swaggerUiBase = rtrim($moduleFrontendUrl, '/') . '/swagger-ui';
?>
<?php if ($moduleFrontendUrl !== ''): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($swaggerUiBase, ENT_QUOTES, 'UTF-8') ?>/swagger-ui.css">
<link rel="stylesheet" href="<?= htmlspecialchars($moduleFrontendUrl, ENT_QUOTES, 'UTF-8') ?>/css/app.css?v=5">
<?php endif; ?>

<div class="api-docs-app">
    <header class="api-docs-header">
        <div>
            <h1 class="api-docs-title">API документация</h1>
            <p class="api-docs-subtitle">OpenAPI из <code>evo-swagger.controllers_path</code></p>
        </div>
    </header>

    <?php if ($openApiError !== null): ?>
        <div class="api-docs-error" role="alert">
            <strong>Не удалось сгенерировать спецификацию</strong>
            <pre><?= htmlspecialchars($openApiError, ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
    <?php else: ?>
        <div id="swagger-ui" class="api-docs-swagger"></div>
        <script src="<?= htmlspecialchars($swaggerUiBase, ENT_QUOTES, 'UTF-8') ?>/swagger-ui-bundle.js"></script>
        <script>
            // BaseLayout без StandalonePreset: Standalone Topbar включает DarkModeToggle,
            // который при prefers-color-scheme: dark вешает html.dark-mode на весь manager
            // и ломает светлую тему модуля (цвета «инвертированы»).
            window.ui = SwaggerUIBundle({
                spec: <?= $openApiJson ?>,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: 'BaseLayout',
                tryItOutEnabled: true
            });
            document.documentElement.classList.remove('dark-mode');
        </script>
    <?php endif; ?>
</div>
