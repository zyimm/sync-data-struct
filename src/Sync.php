<?php

namespace Zyimm\dbStructSync;

use Zyimm\dbStructSync\builder\Mysql;
use \Zyimm\dbStructSync\connector\Mysql as Connector;

class Sync
{
    private $fetch = true;
    /**
     * @var array
     */
    private $statistics = [
        'success' => [],
        'error'   => []
    ];

    /**
     * @var \PDO
     */
    public $localConnector;

    /**
     * @var Mysql
     */
    public $handle;

    /**
     * Sync constructor.
     * @param  array  $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $this->handle         = (new Mysql(new Connector($config)))->removeAutoIncrement();
        $this->localConnector = $this->handle->getConnector()
            ->getLocalConnection();
        $this->handle->basicDiff();
        $this->handle->advanceDiff();
    }

    /**
     * fetchSql
     *
     * @param  bool  $status
     * @return $this
     */
    public function fetchSql($status = true)
    {
        $this->fetch = $status;
        return $this;
    }

    /**
     * executeSync
     *
     * @return $this
     */
    public function executeSync()
    {
        $this->statistics = [];
        $diff_sql_collect = array_filter($this->handle->getDiffSql());
        if ($diff_sql_collect) {
            $add_tables = isset($diff_sql_collect['ADD_TABLE']) ? $diff_sql_collect['ADD_TABLE'] : null;
            if ($add_tables) {
                unset($diff_sql_collect['ADD_TABLE']);
                $this->executeAddTables($add_tables);
            }
            foreach ($diff_sql_collect as $type => $sql_list) {
                foreach ($sql_list as $sql) {
                    if ($this->localConnector->query($sql)) {
                        $this->statistics['success'][$type][] = $sql;
                    } else {
                        $this->statistics['error'][$type][] = $sql;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * toHtml
     */
    public function toHtml()
    {
        header("Content-type:text/html;charset=utf-8");
        $tpl  = dirname(__FILE__).'/template/html.tpl';
        $diff = array_filter($this->handle->getDiffSql());
        $html = include $tpl;
        var_export($html);
    }

    public function toSqlFile()
    {
        header("Content-type:text/html;charset=utf-8");
        //todo
    }


    /**
     * executeAddTables
     *
     * @param $add_table_sql_collect
     */
    private function executeAddTables($add_table_sql_collect)
    {
        if(!$this->fetch){
            $this->localConnector->query('SET FOREIGN_KEY_CHECKS=0');
            foreach ($add_table_sql_collect as $key => $sql) {
                if ($this->localConnector->exec($sql)) {
                    $this->statistics['success']['ADD_TABLE'][] = $sql;
                    unset($add_table_sql_collect[$key]);
                } else {
                    $this->statistics['error']['ADD_TABLE'][] = $sql;
                }
            }
            $this->localConnector->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
