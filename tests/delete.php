<?php

ini_set('max_execution_time', '15');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', 'off');
error_reporting(E_ALL);

include dirname(__DIR__) . '/vendor/autoload.php';

use Balpom\Files\FileDeleter;

$fileName = 'new_writed_file.txt';

$filePath = __DIR__ . '/subdir/subsubdir/subsubsubdir/' . $fileName;
$deleter = new FileDeleter($filePath);
//$deleter->unlink(true);
$deleter->unlink();

if (!file_exists($filePath)) {
    echo 'File deleted sucseccfully!';
} else {
    echo 'Something went wrong...';
}

