<?php

ini_set('max_execution_time', '15');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', 'off');
error_reporting(E_ALL);

include dirname(__DIR__) . '/vendor/autoload.php';

use Balpom\Files\Directory;

$path = __DIR__ . '/subdir_for_delete/subsubdir';
$directory = new Directory($path);
$directory->delete();

//die;

$path = __DIR__ . '/subdir_for_delete/';
$directory = new Directory($path);
$directory->delete();
