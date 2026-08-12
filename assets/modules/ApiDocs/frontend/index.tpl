<?php

declare(strict_types=1);

/**
 * @var string $moduleUrl
 * @var string $moduleFrontendUrl
 * @var string|null $openApiJson
 * @var string|null $openApiError
 * @var array{openapi: string, controllers_path: list<string>, output: string, module_name: string} $config
 * @var list<array{path: string, found: bool}> $pathRows
 * @var list<string> $openApiVersions
 * @var string $formOutput
 * @var string $configFile
 * @var string $basePath
 * @var bool $hasOperations
 */

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$swaggerUiBase = rtrim($moduleFrontendUrl, '/') . '/swagger-ui';
$title = $config['module_name'] !== '' ? $config['module_name'] : 'API документация';
$showSwagger = $openApiError === null && $hasOperations;

include MODX_MANAGER_PATH . 'includes/header.inc.php';
?>
<?php if ($moduleFrontendUrl !== ''): ?>
    <?php if ($showSwagger): ?>
        <link rel="stylesheet" href="<?= $e($swaggerUiBase . '/swagger-ui.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $e($moduleFrontendUrl . '/css/app.css?v=28') ?>">
<?php endif; ?>

<h1>
    <i class="fa fa-file-code-o"></i><?= $e($title) ?>
</h1>

<?php if ($openApiError !== null): ?>
    <div class="container">
        <div class="alert alert-danger">
            <strong>Не удалось сгенерировать спецификацию</strong>
            <pre class="api-docs-trace"><?= $e($openApiError) ?></pre>
        </div>
    </div>
<?php endif; ?>

<div class="container" id="apidocsMessageBox" hidden>
    <div class="alert" id="apidocsMessage" role="status"></div>
</div>

<div id="actions">
    <div class="btn-group">
        <a id="apidocsGenerate" class="btn btn-primary" href="javascript:;" title="Пересобрать openapi.json">
            <i class="fa fa-refresh"></i>
            <span>Сгенерировать</span>
        </a>

        <a class="btn btn-danger" href="index.php?a=2" title="Закрыть">
            <i class="fa fa-sign-out"></i>
            <span>Закрыть</span>
        </a>
    </div>
</div>

<div class="sectionBody">
    <div class="tab-pane" id="apidocsPane">
        <script type="text/javascript">
            var tpApiDocs = new WebFXTabPane(document.getElementById('apidocsPane'), false);
        </script>

        <div class="tab-page" id="tab_spec">
            <h2 class="tab">Спецификация</h2>

            <script type="text/javascript">
                tpApiDocs.addTabPage(document.getElementById('tab_spec'));
            </script>

            <?php if ($openApiError === null && !$showSwagger): ?>
                <div class="container">
                    <div class="alert alert-info">
                        <strong>В спецификации нет ни одной операции.</strong>
                        <p>
                            Генератор прошёл по заданным каталогам, но не нашёл там атрибутов
                            <code>#[OA\…]</code>. Разметьте контроллеры или укажите другие
                            каталоги на вкладке «Параметры».
                        </p>
                    </div>
                </div>
            <?php elseif ($showSwagger): ?>
                <div id="swagger-ui" class="api-docs-swagger"></div>
                <script src="<?= $e($swaggerUiBase . '/swagger-ui-bundle.js') ?>"></script>
                <script>
                    window.apidocsSwaggerConfig = {
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
                        tryItOutEnabled: true,
                        syntaxHighlight: {
                            activated: true,
                            theme: 'idea'
                        }
                    };

                    window.ui = SwaggerUIBundle(window.apidocsSwaggerConfig);
                </script>
            <?php else: ?>
                <div class="container">
                    <div class="alert alert-warning">
                        Спецификация не построена — смотрите ошибку выше и проверьте
                        каталоги сканирования на вкладке «Параметры».
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-page" id="tab_params">
            <h2 class="tab">Параметры</h2>

            <script type="text/javascript">
                tpApiDocs.addTabPage(document.getElementById('tab_params'));
            </script>

            <form id="apidocsSettings" class="api-docs-settings" autocomplete="off">
                <div class="api-docs-field">
                    <label>Каталоги сканирования</label>

                    <div class="api-docs-repeater" id="apidocsPaths">
                        <?php foreach ($pathRows ?: [['path' => '', 'found' => null]] as $row): ?>
                            <div class="api-docs-row">
                                <input type="text" name="controllers_path[]" spellcheck="false"
                                       value="<?= $e($row['path']) ?>"
                                       placeholder="core/vendor/qmedia-by/soft-core/src/Api">
                                <span class="api-docs-status<?= $row['found'] === null
                                    ? ''
                                    : ($row['found'] ? ' is-found' : ' is-missing') ?>"><?= $row['found'] === null
                                    ? ''
                                    : ($row['found'] ? 'найден' : 'не найден') ?></span>
                                <button type="button" class="api-docs-remove" title="Убрать каталог"
                                        aria-label="Убрать каталог">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="btn btn-secondary api-docs-add" id="apidocsAddPath">
                        <i class="fa fa-plus"></i> Добавить каталог
                    </button>

                    <p class="api-docs-hint">
                        Относительные пути считаются от корня сайта
                        (<code><?= $e($basePath) ?></code>),
                        абсолютные — берутся как есть. Сканируются каталоги с атрибутами
                        <code>#[OA\…]</code>.
                    </p>
                </div>

                <div class="api-docs-field">
                    <label for="apidocsOutput">Файл спецификации</label>
                    <input type="text" id="apidocsOutput" name="output" spellcheck="false"
                           value="<?= $e($formOutput) ?>"
                           placeholder="assets/modules/ApiDocs/docs/openapi.json">
                    <p class="api-docs-hint">
                        Куда пишет кнопка «Сгенерировать». Пусто — путь по умолчанию.
                        Расширение <code>.yaml</code> или <code>.yml</code> переключает формат.
                    </p>
                </div>

                <div class="api-docs-field">
                    <label for="apidocsVersion">Версия OpenAPI</label>
                    <select id="apidocsVersion" name="openapi">
                        <?php foreach ($openApiVersions as $version): ?>
                            <option value="<?= $e($version) ?>"
                                <?= $version === $config['openapi'] ? ' selected' : '' ?>>
                                <?= $e($version) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="api-docs-field">
                    <label for="apidocsModuleName">Название модуля</label>
                    <input type="text" id="apidocsModuleName" name="module_name"
                           value="<?= $e($config['module_name']) ?>" maxlength="100">
                    <p class="api-docs-hint">
                        Подпись в меню менеджера. Применится после переоткрытия модуля.
                    </p>
                </div>

                <div class="api-docs-actions">
                    <button type="submit" class="btn btn-primary" id="apidocsSave">Сохранить</button>
                    <span class="api-docs-config-path">
                        Файл настроек: <code><?= $e($configFile) ?></code>
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        document.documentElement.classList.remove('dark-mode');

        var button = document.getElementById('apidocsGenerate');
        var box = document.getElementById('apidocsMessageBox');
        var message = document.getElementById('apidocsMessage');
        var label = button.querySelector('span');
        var labelIdle = label.textContent;
        var endpoint = <?= json_encode($moduleUrl . '/ajax.php', JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;

        function show(type, text) {
            message.className = 'alert alert-' + type;
            message.textContent = text;
            box.hidden = false;
        }

        function remount(spec) {
            if (!spec || !window.SwaggerUIBundle || !window.apidocsSwaggerConfig) {
                return false;
            }

            window.apidocsSwaggerConfig.spec = spec;
            window.ui = SwaggerUIBundle(window.apidocsSwaggerConfig);

            return true;
        }

        button.addEventListener('click', function () {
            if (button.classList.contains('disabled')) {
                return;
            }

            button.classList.add('disabled');
            label.textContent = 'Генерация…';
            box.hidden = true;

            fetch(endpoint + '?mode=generate', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        throw new Error('Ответ не является JSON (HTTP ' + response.status + ')');
                    });
                })
                .then(function (data) {
                    if (!data.success) {
                        throw new Error(data.message || 'Неизвестная ошибка');
                    }

                    if (remount(data.spec)) {
                        show('success', data.message);
                    } else {
                        show('success', data.message
                            + ' Откройте модуль заново, чтобы увидеть документацию.');
                    }
                })
                .catch(function (error) {
                    show('danger', error.message);
                })
                .then(function () {
                    button.classList.remove('disabled');
                    label.textContent = labelIdle;
                });
        });

        var form = document.getElementById('apidocsSettings');
        var save = document.getElementById('apidocsSave');
        var repeater = document.getElementById('apidocsPaths');
        var addPath = document.getElementById('apidocsAddPath');

        function setStatus(row, found) {
            var status = row.querySelector('.api-docs-status');
            status.className = 'api-docs-status'
                + (found === null ? '' : (found ? ' is-found' : ' is-missing'));
            status.textContent = found === null ? '' : (found ? 'найден' : 'не найден');
        }

        function addRow(value) {
            var row = repeater.firstElementChild.cloneNode(true);
            row.querySelector('input').value = value || '';
            setStatus(row, null);
            repeater.appendChild(row);

            return row;
        }

        function renderPaths(stored, found) {
            var paths = stored && stored.length ? stored : [''];
            var first = repeater.firstElementChild;

            repeater.innerHTML = '';
            repeater.appendChild(first);

            paths.forEach(function (path, index) {
                var row = index === 0 ? repeater.children[0] : addRow('');
                row.querySelector('input').value = path;
                setStatus(row, path === '' ? null : (found || []).some(function (abs) {
                    return abs === path || abs.slice(-path.length - 1) === '/' + path;
                }));
            });
        }

        if (repeater) {
            repeater.addEventListener('click', function (event) {
                var target = event.target.closest('.api-docs-remove');
                if (!target) {
                    return;
                }

                if (repeater.children.length === 1) {
                    repeater.children[0].querySelector('input').value = '';
                    setStatus(repeater.children[0], null);

                    return;
                }

                target.closest('.api-docs-row').remove();
            });

            repeater.addEventListener('input', function (event) {
                if (event.target.tagName === 'INPUT') {
                    setStatus(event.target.closest('.api-docs-row'), null);
                }
            });
        }

        if (addPath) {
            addPath.addEventListener('click', function () {
                addRow('').querySelector('input').focus();
            });
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (save.classList.contains('disabled')) {
                    return;
                }

                save.classList.add('disabled');
                save.textContent = 'Сохранение…';
                box.hidden = true;

                fetch(endpoint + '?mode=save', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    body: new FormData(form)
                })
                    .then(function (response) {
                        return response.json().catch(function () {
                            throw new Error('Ответ не является JSON (HTTP ' + response.status + ')');
                        });
                    })
                    .then(function (data) {
                        if (!data.success) {
                            throw new Error(data.message || 'Неизвестная ошибка');
                        }

                        renderPaths(data.stored && data.stored.controllers_path, data.scanPaths);
                        show(data.warnings && data.warnings.length ? 'warning' : 'success', data.message);
                    })
                    .catch(function (error) {
                        show('danger', error.message);
                    })
                    .then(function () {
                        save.classList.remove('disabled');
                        save.textContent = 'Сохранить';
                    });
            });
        }
    })();
</script>

<?php include MODX_MANAGER_PATH . 'includes/footer.inc.php'; ?>
