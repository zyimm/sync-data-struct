<?php

//error
error_reporting(E_ALL);
ini_set('display_errors', true);
include '../vendor/autoload.php';
//db
$config = [
    'local' => [
        'host'     => 'mysql',
        'username' => 'root',
        'passwd'   => '123456',
        'dbname'   => 'crmeb_supplier_o'
    ],
    'dev'   => [
        'host'     => 'mysql',
        'username' => 'root',
        'passwd'   => '123456',
        'dbname'   => 'crmeb_supplier'
    ]
];
$handle = new \Zyimm\dbStructSync\Sync($config);
echo $handle->executeSync()->toHtml();
