<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $environment = $context['APP_ENV'] ?? 'prod';
    if (!is_string($environment)) {
        throw new RuntimeException('APP_ENV must be a string');
    }

    return new Kernel($environment, (bool) $context['APP_DEBUG']);
};
