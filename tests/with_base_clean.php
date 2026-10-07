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

(new Directory($baseDirectory . 'subdir1/subsubdir1'))->create();
(new Directory($baseDirectory . 'subdir1/subsubdir2'))->create();
(new Directory($baseDirectory . 'subdir2/subsubdir1'))->create();
(new Directory($baseDirectory . 'subdir2/subsubdir2'))->create();

//die;

$directory = new Directory();
try {
    $directory->base($subDirectory);
    $directory->set($baseDirectory);
    $directory->clean();
} catch (HandlerException $e) {
    echo 'EXCEPTION: ' . $e->getMessage() . PHP_EOL;
}

$directory->base($baseDirectory);
$directory->set($subDirectory);
$directory->clean();

if (!file_exists($subDirectory)) {
    echo 'Directory "' . $subDirectory . '" was cleaned.' . PHP_EOL;
} else {
    echo 'Something went wrong...' . PHP_EOL;
}

