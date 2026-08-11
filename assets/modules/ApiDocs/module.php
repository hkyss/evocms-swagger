<?php

declare(strict_types=1);

$index = __DIR__ . '/index.php';

if (!is_file($index)) {
    echo '<div class="api-docs-error" role="alert"><strong>evo-swagger</strong>: не найден index.php модуля.</div>';

    return;
}

include $index;
