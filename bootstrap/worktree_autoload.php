<?php

$basePath = dirname(__DIR__);
$prefixes = [
    'App\\' => $basePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR,
    'Database\\Factories\\' => $basePath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'factories'.DIRECTORY_SEPARATOR,
    'Database\\Seeders\\' => $basePath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'seeders'.DIRECTORY_SEPARATOR,
    'Tests\\' => $basePath.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR,
];

$worktreeLoader = static function (string $class) use ($prefixes): void {
    foreach ($prefixes as $prefix => $directory) {
        if (! str_starts_with($class, $prefix)) {
            continue;
        }

        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))).'.php';
        $candidate = $directory.$relativePath;

        if (is_file($candidate)) {
            require_once $candidate;
        }

        return;
    }
};

$loader = require_once $basePath.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

if ($loader instanceof Composer\Autoload\ClassLoader) {
    $loader->setPsr4('App\\', $basePath.DIRECTORY_SEPARATOR.'app');
    $loader->setPsr4('Database\\Factories\\', $basePath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'factories');
    $loader->setPsr4('Database\\Seeders\\', $basePath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'seeders');
    $loader->setPsr4('Tests\\', $basePath.DIRECTORY_SEPARATOR.'tests');
}

spl_autoload_register($worktreeLoader, true, true);
