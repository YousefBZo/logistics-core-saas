#!/usr/bin/env php
<?php

declare(strict_types=1);

$roots = [
    __DIR__.'/../app',
    __DIR__.'/../tests',
    __DIR__.'/../routes',
    __DIR__.'/../bootstrap',
    __DIR__.'/../database/factories',
    __DIR__.'/../database/seeders',
];

foreach ($roots as $root) {
    if (! is_dir($root)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $path = $file->getPathname();
        $contents = file_get_contents($path);

        if ($contents === false || str_contains($contents, 'declare(strict_types=1);')) {
            continue;
        }

        if (! str_starts_with($contents, "<?php\n")) {
            continue;
        }

        $updated = "<?php\n\ndeclare(strict_types=1);\n".substr($contents, 5);
        file_put_contents($path, $updated);
        echo "Updated: {$path}\n";
    }
}
