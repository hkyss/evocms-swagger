<?php

declare(strict_types=1);

$index = MODX_BASE_PATH . 'core/vendor/vvvladv/evo-swagger/assets/modules/ApiDocs/index.php';

if (!is_file($index)) {
    echo '<div class="api-docs-error" role="alert"><strong>evo-swagger</strong>: не найден index.php модуля.</div>';

    return;
}

include $index;
