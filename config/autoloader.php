<?php
spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/../src/';

    $relative_class = str_replace('PharmaFEFOV2\\', '', $class);

    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});