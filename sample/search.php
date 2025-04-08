<?php

use Takuya\Utils\PdoTable\Exceptions\Utils\PdoTable\PdoTableRepository;

require __DIR__.'/../vendor/autoload.php';

$db = './sample.db';
$table = new PdoTableRepository(new PDO("sqlite:{$db}"),'locates_fts');
$ret = $table->select('filename','match',$argv[1]??'ssh');
dd($ret);

