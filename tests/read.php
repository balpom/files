<?php

ini_set('max_execution_time', '0');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', 'off');
error_reporting(E_ALL);

include dirname(__DIR__) . '/vendor/autoload.php';

use Balpom\Files\Reader;

$fileName = 'sample.txt';

$filePath = __DIR__ . '/' . $fileName;
$reader = new Reader($filePath);
$content = $reader->read();
echo $content;
