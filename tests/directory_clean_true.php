<?php

ini_set('max_execution_time', '15');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', 'off');
error_reporting(E_ALL);

include dirname(__DIR__) . '/vendor/autoload.php';

use Balpom\Files\Directory;

$filePath = __DIR__ . '/subdir_for_clean_true';
$deleter = new Directory($filePath);
$deleter->clean(true);
