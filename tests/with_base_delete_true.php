<?php

ini_set('max_execution_time', '15');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', 'off');
error_reporting(E_ALL);

include dirname(__DIR__) . '/vendor/autoload.php';

use Balpom\Files\File;
use Balpom\Files\Directory;
use Balpom\Files\HandlerException;

$fileName = 'new_writed_file.txt';

$baseDirectory = __DIR__ . '/base_directory/';
$path = $baseDirectory . '/test_directory/';

$subDirectories = 'subdir/subsubdir';
$content = 'It is a test content for a test file.';
for ($i = 1; $i <= 5; $i++) {
    $filePath = $path . $subDirectories . '_' . $i . '/' . $fileName;
    $file = new File($filePath);
    $file->write($content);
}

//die;

$directory = new Directory();
try {
    $directory->base($path)->set($baseDirectory);
    $directory->delete(true);
} catch (HandlerException $e) {
    echo 'EXCEPTION: ' . $e->getMessage() . PHP_EOL;
}
$directory->base($baseDirectory)->set($path);
$directory->delete(true);

if (!file_exists($path) && file_exists($baseDirectory)) {
    echo 'Directory "' . $path . '" and all it subdirectories was deleted.' . PHP_EOL;
} else {
    echo 'Something went wrong...' . PHP_EOL;
}

