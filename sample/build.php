<?php

use Takuya\SearchFiles\FindDbBuilder;

require __DIR__.'/../vendor/autoload.php';

$db = './sample.db';
file_exists($db) && unlink($db);
$dir = $argv[1] ?? '/etc';
$builder = new FindDbBuilder( "sqlite:{$db}", $dir );
$builder->findSize('+10M');
$builder->build();

