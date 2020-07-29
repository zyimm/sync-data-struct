<?php


namespace Zyimm\dbStructSync\connector;


class Mysql
{
    /**
     * @var \PDO
     */
    private $localConnection;

    /**
     * @var \PDO
     */
    private $devConnection;

    public $localDb = [];

    public $devDb = [];

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
     *
     * @param  array  $config
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $config = empty($config) ? $this->getConfig() : $config;
        foreach (static::connectionMap() as $key => $connect) {
            $this->{$key.'Db'} = $config[$key];
            $dsn               = $this->getDsn($config[$key]['host'], $config[$key]['dbname']);
            $user              = $config[$key]['username'];
            $password          = $config[$key]['passwd'];
            $this->{$connect}  = new \PDO($dsn, $user, $password);
        }
    }

    /**
     * getConfig
     *
     * @return false|string
     * @throws \Exception
     */
    public function getConfig()
    {
        $file = dirname(__DIR__).'/config.php';
        if (is_file($file)) {
            $config = file_get_contents($file);
        }
        if (empty($config)) {
            throw new \Exception('db config exception');
        }
        return $config;
    }

    public function getDsn($host = '127.0.0.1', $db_name)
    {
        return "mysql:host={$host};dbname={$db_name}";
    }

    public function getLocalConnection()
    {
        return $this->localConnection;
    }

    public function getDevConnection()
    {
        return $this->devConnection;
    }
}