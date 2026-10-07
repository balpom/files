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
$zeroSizeFileName = 'file_with_zero_size.txt';

$content = 'It is a test content.' . PHP_EOL;

$path = __DIR__ . '/test_directory/';
$baseDirectory = $path . '/base_directory/';

(new Directory($baseDirectory . 'subdir1/subsubdir0'))->create(); // Empty directory.
(new File($baseDirectory . 'subdir1/subsubdir1/' . $zeroSizeFileName))->write(''); // Empty file.
(new File($baseDirectory . 'subdir1/subsubdir2/' . $fileName))->write($content);
(new File($baseDirectory . 'subdir1/subsubdir2/' . $zeroSizeFileName))->write(''); // Empty file.
(new Directory($baseDirectory . 'subdir2/subsubdir1'))->create();
(new Directory($baseDirectory . 'subdir2/subsubdir2'))->create();

//die;

$directory = new Directory($baseDirectory, $baseDirectory . '/subdir1/');
try {
    $directory->clean(true);
} catch (HandlerException $e) {
    echo 'EXCEPTION: ' . $e->getMessage() . PHP_EOL;
}

$directory = new Directory($baseDirectory . '/subdir1/', $baseDirectory);
$directory->clean(true);

if (!file_exists($baseDirectory . '/subdir1/subsubdir2/' . $zeroSizeFileName)) {
    echo 'Directory "' . $baseDirectory . '/subdir1/' . '" was cleaned.' . PHP_EOL;
} else {
    echo 'Something went wrong...' . PHP_EOL;
}

