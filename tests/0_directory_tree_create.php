<?php

// For directory_clean.php and directory_remove.php tests.

ini_set('max_execution_time', '15');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', 'off');
error_reporting(E_ALL);

include dirname(__DIR__) . '/vendor/autoload.php';

use Balpom\Files\File;
use Balpom\Files\Directory;

$fileName = 'new_writed_file.txt';
$zeroSizeFileName = 'file_with_zero_size.txt';

$content = 'It is a test content.' . PHP_EOL;

$rootDir = __DIR__ . '/subdir_for_delete_true';
for ($i = 1; $i <= 5; $i++) {
    $filePath = $rootDir . '/subsubdir' . $i . '/subsubsubdir' . rand(1, 2) . '/' . $fileName;
    (new File($filePath))->write($content);
}

// Only empty directories.
$rootDir = __DIR__ . '/subdir_for_delete';
$filePath = $rootDir . '/subsubdir/' . $fileName;
(new File($filePath))->write('');
(new File($filePath))->delete();

$rootDir = __DIR__ . '/subdir_for_clean_true';
for ($i = 1; $i <= 10; $i++) {
    $filePath = $rootDir . '/subsubdir' . rand(1, 3) . '/subsubsubdir' . rand(1, 3) . '/' . $fileName;
    (new File($filePath))->write($content);
    $zeroSizeFilePath = $rootDir . '/subsubdir' . rand(1, 3) . '/subsubsubdir' . rand(1, 3) . '/' . $zeroSizeFileName;
    (new File($zeroSizeFilePath))->write('');
    $path = $rootDir . '/empty_subsubdir' . $i . '/empty_subsubsubdir' . rand(1, 3);
    (new Directory($path))->create();
}

$rootDir = __DIR__ . '/subdir_for_clean';
for ($i = 1; $i <= 10; $i++) {
    $path = $rootDir . '/empty_subsubdir' . $i . '/empty_subsubsubdir' . rand(1, 9);
    (new Directory($path))->create();
    $path = $rootDir . '/empty_subsubdir' . $i . '/empty_subsubsubdir' . rand(1, 9);
    (new Directory($path))->create();
    $path = $rootDir . '/empty_subsubdir' . $i . '/empty_subsubsubdir' . rand(1, 9);
    (new Directory($path))->create();
}