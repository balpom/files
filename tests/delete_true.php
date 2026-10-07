<?php

ini_set('max_execution_time', '15');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', 'off');
error_reporting(E_ALL);

include dirname(__DIR__) . '/vendor/autoload.php';

use Balpom\Files\File;

$fileName = 'new_writed_file.txt';

$filePath = __DIR__ . '/subdir/subsubdir/subsubsubdir/' . $fileName;
$handler = new File($filePath);
$content = 'It is a test content.';
$handler->write($content);

//die;

$filePath = __DIR__ . '/subdir/subsubdir/subsubsubdir/' . $fileName;
$file = new File($filePath);
$file->delete(true);

if (!file_exists($filePath)) {
    echo 'File deleted sucseccfully!' . PHP_EOL;
} else {
    echo 'Something went wrong...' . PHP_EOL;
}
