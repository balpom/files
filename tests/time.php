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
$file = new File($filePath);
$content = 'It is a test content';
$file->write($content);

echo ($file->getTime()) . PHP_EOL; // File creation time.
$file->setTime(7777777777);
echo ($file->getTime()) . PHP_EOL; // 7777777777
//
// For Linux:
// 15032385535 is the maximum value for a timestamp that EXT4 file system can store.
try {
    $file->setTime(222222222222);
} catch (Exception $e) {
    echo 'EXCEPTION: ' . $e . PHP_EOL;
}
echo ($file->getTime()) . PHP_EOL;
