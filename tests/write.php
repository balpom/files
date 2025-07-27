<?php

ini_set('max_execution_time', '0');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', 'off');
error_reporting(E_ALL);

include dirname(__DIR__) . '/vendor/autoload.php';

use Balpom\Files\Writer;

$fileName = 'new_writed_file.txt';

$filePath = __DIR__ . '/subdir/subsubdir/' . $fileName;
$writer = new Writer($filePath);
$content = 'It is a test content.' . PHP_EOL . 'File writing work!';
$len = strlen($content);
$writer->write($content);

if (file_exists($filePath) && $len === filesize($filePath)) {
    echo 'File writed sucseccfully!';
} else {
    echo 'Something went wrong...';
}

