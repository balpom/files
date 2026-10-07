<?php

ini_set('max_execution_time', '15');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', 'off');
error_reporting(E_ALL);

include dirname(__DIR__) . '/vendor/autoload.php';

use Balpom\Files\Directory;
use Balpom\Files\HandlerException;

$path = __DIR__ . '/test_directory/';
$baseDirectory = $path . '/base_directory/';
$subDirectory = $baseDirectory . 'subdir1/';

(new Directory($subDirectory))->create();

//die;

$directory = new Directory();
try {
    $directory->base($subDirectory);
    $directory->set($baseDirectory);
    $directory->delete();
} catch (HandlerException $e) {
    echo 'EXCEPTION: ' . $e->getMessage() . PHP_EOL;
}

$directory->base($baseDirectory);
$directory->set($subDirectory);
$directory->delete();

if (!file_exists($subDirectory)) {
    echo 'Directory "' . $subDirectory . '" was deleted.' . PHP_EOL;
} else {
    echo 'Something went wrong...' . PHP_EOL;
}

