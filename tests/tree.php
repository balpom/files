<?php

ini_set('max_execution_time', '15');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', 'off');
error_reporting(E_ALL);

include dirname(__DIR__) . '/vendor/autoload.php';

use Balpom\Files\Directory;

$path = dirname(__DIR__);
$directory = new Directory($path);

//$result = $directory->directories();
//$result = $directory->files();
$result = $directory->objects();

foreach ($result as $item) {
    echo $item->path() . ' - ' . get_class($item) . PHP_EOL;
}
echo 'TOTAL: ' . count($result) . PHP_EOL . PHP_EOL;

//$result = $directory->directories(2);
//$result = $directory->files(2);
$result = $directory->objects(2);

foreach ($result as $item) {
    echo $item->path() . ' - ' . get_class($item) . PHP_EOL;
}
echo 'TOTAL: ' . count($result) . PHP_EOL . PHP_EOL;
