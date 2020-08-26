<?php

namespace Zyimm\dbStructSync\connector;

use Exception;
use PDO;

/**
 * Class Mysql
 *
 * @package Zyimm\dbStructSync\connector
 */
class Mysql
{
    /**
     * @var PDO
     */
    private $localConnection;

    /**
     * @var PDO
     */
    private $devConnection;

    //localDb
    public $localDb = [];

    //devDb
    public $devDb = [];

    /**
     * connectionMap
     *
     * @return string[]
     */
    public static function connectionMap()
    {
        return [
            'local' => 'localConnection',
            'dev'   => 'devConnection'
        ];
    }

    /**
     * Mysql constructor.
     *
     * @param  array  $config
     * @throws Exception
     */
    public function __construct(array $config = [])
    {
        $config = empty($config) ? $this->getConfig() : $config;
        foreach (static::connectionMap() as $key => $connect) {
            $this->{$key.'Db'} = $config[$key];
            $dsn               = $this->getDsn($config[$key]['host'], $config[$key]['dbname']);
            $user              = $config[$key]['username'];
            $password          = $config[$key]['passwd'];
            $this->{$connect}  = new PDO($dsn, $user, $password);
        }
    }

    /**
     * getConfig
     *
     * @return false|string
     * @throws Exception
     */
    public function getConfig()
    {
        $file = dirname(__DIR__).'/config.php';
        if (is_file($file)) {
            $config = file_get_contents($file);
        }
        if (empty($config)) {
            throw new Exception('db config exception');
        }
        return $config;
    }

    /**
     * getDsn
     *
     * @param  string  $host
     * @param  string  $db_name
     * @return string
     */
    public function getDsn($host = '', $db_name = '')
    {
        return "mysql:host={$host};dbname={$db_name}";
    }

    /**
     * getDevConnection
     *
     * @return PDO
     */
    public function getLocalConnection()
    {
        return $this->localConnection;
    }

    /**
     * getDevConnection
     *
     * @return PDO
     */
    public function getDevConnection()
    {
        return $this->devConnection;
    }
}
