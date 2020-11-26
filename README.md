## sync-data-struct
sync-data-struct是一个用来比较两个数据库之间的数据结构差异,并生成更新DDL的工具包.方便对比出两个数据库之间差异来进行数据结构同步.

## support database
- mysql

## install
```
composer  require zyimm/sync-data-struct
```
## example
```
//set error
error_reporting(E_ALL);
ini_set('display_errors', true);
//autoload
include '../vendor/autoload.php';
//db
$config = [
    'local' => [
        'host'     => 'mysql',
        'username' => 'root',
        'passwd'   => '123456',
        'dbname'   => 'local'
    ],
    'dev'   => [
        'host'     => 'mysql',
        'username' => 'root',
        'passwd'   => '123456',
        'dbname'   => 'dev'
    ]
];
$handle = new \Zyimm\dbStructSync\Sync($config);
echo $handle->toHtml();
```

## issue
能力有限！欢迎提出issue,共同学习进步。
