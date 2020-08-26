<?php


namespace Zyimm\dbStructSync\builder;

use PDO;
use PDOStatement;
use Zyimm\dbStructSync\builder\traits\BuildTrait;
use \Zyimm\dbStructSync\connector\Mysql as Connector;
use Zyimm\dbStructSync\constants\Advance;

class Mysql
{
    use BuildTrait;

    /**
     * @var Connector
     */
    private $connector;

    //localStruct
    public $localStruct = [];

    //devStruct
    public $devStruct = [];

    /**
     * $patterns
     *
     * @var string[]
     */
    public static $patterns = [
        'primary'    => '(^[^`]\s*PRIMARY KEY .*[,]?$)',
        'key'        => '(^[^`]\s*KEY\s+(`.*`) .*[,]?$)',
        'constraint' => '(^[^`]\s*CONSTRAINT\s+(`.*`) .*[,]?$)',
    ];

    /**
     * @var bool removeAutoIncrement
     */
    private $removeAutoIncrement = false;

    /**
     * config
     *
     * @var array[]
     */
    public static $advance = [
        'VIEW'      => [
            Advance::VIEW, 'Create View'
        ],
        'TRIGGER'   => [
            Advance::TRIGGER,
            'SQL Original Statement'
        ],
        'EVENT'     => [
            Advance::EVENT, 'Create Event'
        ],
        'FUNCTION'  => [Advance::_FUNCTION_, 'Create Function'],
        'PROCEDURE' => [Advance::PROCEDURE, 'Create Procedure']
    ];

    /**
     * removeAutoIncrement
     *
     * @param  bool  $status
     * @return $this
     */
    public function removeAutoIncrement($status = true)
    {
        $this->removeAutoIncrement = $status;
        return $this;
    }

    /**
     * Mysql constructor.
     *
     * @param  Connector  $connector
     */
    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * getConnector
     *
     * @return Connector
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * getStructure
     *
     * @param  PDO  $db
     * @return array[]
     */
    private function getStructure(PDO $db)
    {
        $stmt1         = $db->query("SHOW TABLE STATUS WHERE Comment!='VIEW'");
        $alert_columns = $constraints = [];
        $show_create   = $tables = [];
        $pattern       = '/'.implode('|', self::$patterns).'/m';
        foreach ($stmt1->fetchAll() as $row) {
            //show table desc
            $alert_columns_conn = $db->query('SHOW CREATE TABLE '.$row['Name']);
            $sql                = $alert_columns_conn->fetch();
            //preg
            preg_match_all('/^\s+[`]([^`]*)`.*?$/m', $sql['Create Table'], $key_value);
            $alert_columns[$row['Name']] = array_combine($key_value[1], array_map(function ($item) {
                return trim(rtrim($item, ','));
            }, $key_value[0]));
            //get primary key
            preg_match_all($pattern, $sql['Create Table'], $matches);
            //$str
            $str = array_map(function ($item) {
                return trim(rtrim($item, ','));
            }, $matches[0]);
            //$row['Name']
            $constraints[$row['Name']] = $str;
            $show_create[$row['Name']] = $this->removeAutoIncrement ?
                preg_replace('/AUTO_INCREMENT=[^\s]*/', '',
                    $sql['Create Table']) : $sql['Create Table'];

            $tables[] = $row['Name'];

        }
        //sort
        ksort($alert_columns);
        ksort($constraints);
        ksort($show_create);
        ksort($tables);
        return [
            'tables'      => $tables,
            'columns'     => $alert_columns,
            'show_create' => $show_create,
            'constraints' => $constraints
        ];
    }

    /**
     * basicDiff
     *
     * @return bool
     */
    public function basicDiff()
    {
        $this->localStruct = $this->getStructure($this->connector->getLocalConnection());
        $this->devStruct   = $this->getStructure($this->connector->getDevConnection());
        //$result
        $result               = [];
        $result['ADD_TABLE']  = array_diff($this->devStruct['tables'], $this->localStruct['tables']);
        $result['DROP_TABLE'] = array_diff($this->localStruct['tables'], $this->devStruct['tables']);
        $dev_columns          = $this->devStruct['columns'];
        $local_columns        = $this->localStruct['columns'];

        if (!empty($dev_columns)) {
            foreach ($dev_columns as $table => $columns) {
                foreach ($columns as $field => $sql) {
                    //add
                    if (!isset($local_columns[$table][$field])) {
                        $result['ADD_FIELD'][$table][$field] = $sql;
                        //modify
                    } elseif ($local_columns[$table][$field] !== $sql) {
                        $result['MODIFY_FIELD'][$table][$field] = $sql;
                        unset($local_columns[$table][$field]);
                    } else {
                        unset($local_columns[$table][$field]);
                    }
                }
            }
        }
        //DROP_FIELD
        $result['DROP_FIELD'] = array_filter($local_columns);
        //DROP_CONSTRAINT
        $result['DROP_CONSTRAINT'] = self::arrayDiffAssocRecursive($this->localStruct['constraints'],
            $this->devStruct['constraints'], $result['DROP_TABLE']);
        $result['ADD_CONSTRAINT']  = self::arrayDiffAssocRecursive($this->devStruct['constraints'],
            $this->localStruct['constraints'], $result['ADD_TABLE']);

        foreach (array_filter($result) as $type => $data) {
            $this->getExecuteSql($data, $type);
        }
        return true;
    }

    /**
     * advanceDiff
     *
     * @return bool
     */
    public function advanceDiff()
    {
        $arr = $diff = [];
        foreach (static::$advance as $type => $list_sql) {
            foreach (['dev', 'local'] as $key) {
                $con = 'get'.ucfirst($key).'Connection';
                $db  = $key.'Db';
                $sql = str_replace('#', $this->connector->$db['dbname'], $list_sql[0]);
                /**
                 * @var $connect PDOStatement
                 */
                $connect = $this->connector->{$con}()->query($sql);
                $result  = $connect->fetchAll();
                foreach ($result as $row) {
                    $show_create_conn               = $this->connector->{$con}()->query('SHOW CREATE '.$type.' '.$row['Name']);
                    $arr[$type][$key][$row['Name']] = preg_replace('/DEFINER=[^\s]*/', '',
                        $show_create_conn->fetch_assoc()[$list_sql[1]]);
                }
                if (isset($arr[$type]['dev'])) {
                    $diff['ADD_'.$type] = self::arrayDiffAssocRecursive($arr[$type]['dev'], $arr[$type]['local']);
                }
                if (isset($arr[$type]['local'])) {
                    $diff['DROP_'.$type] = self::arrayDiffAssocRecursive($arr[$type]['local'], $arr[$type]['dev']);

                }
            }
        }
        //getExecuteSql
        foreach (array_filter($diff) as $type => $data) {
            $this->getExecuteSql($data, $type);
        }
        return true;
    }

    /**
     * getExecuteSql
     *
     * @param $arr
     * @param $type
     */
    public function getExecuteSql($arr, $type)
    {
        foreach ($arr as $table => $rows) {
            $sql = '';
            if (in_array($type, ['ADD_TABLE', 'DROP_TABLE'])) {
                $sql                    = $type == 'ADD_TABLE' ? $this->devStruct['show_create'][$rows] : "DROP TABLE IF EXISTS {$rows}";
                $this->diffSql[$type][] = rtrim($sql, ',');
                continue;
            }
            if (in_array($type, Advance::$allow)) {
                $sql                    = strpos($type, 'ADD') !== false ? $rows : str_replace('_', '',
                        $rows).' '.$table;
                $this->diffSql[$type][] = $sql;
                continue;
            }
            foreach ($rows as $key => $val) {
                if ($this->getExecuteSqlPipeline($type)) {
                    $sql = call_user_func($this->getExecuteSqlPipeline($type),
                        $key, $val, $key
                    );
                }
                $this->diffSql[$type][] = trim($sql, ',');
            }
        }
    }

    /**
     * getExecuteSqlPipeline
     *
     * @param $type
     * @return \Closure|null
     */
    public function getExecuteSqlPipeline($type)
    {
        $pipe = [
            'MODIFY_FIELD'    => function ($table, $val) {
                return "ALTER TABLE `{$table}` MODIFY {$val}";
            },
            'DROP_FIELD'      => function ($table, $val, $key = null) {
                return "ALTER TABLE `{$table}` DROP {$key}";
            },
            'ADD_FIELD'       => function ($table, $val) {
                return "ALTER TABLE `{$table}` ADD {$val}";
            },
            'ADD_CONSTRAINT'  => function ($table, $val) {
                return self::getConstraintQuery($val, $table)['add'];
            },
            'DROP_CONSTRAINT' => function ($table, $val) {
                return self::getConstraintQuery($val, $table)['drop'];
            }
        ];
        return isset($pipe[$type]) ? $pipe[$type] : null;
    }

    /**
     * getConstraintQuery
     *
     * @param $constraint
     * @param $table
     * @return string[]
     */
    public static function getConstraintQuery($constraint, $table)
    {
        foreach (static::$patterns as $key => $pattern) {
            if (preg_match("/".str_replace('^[^`]', '', $pattern)."$/m", $constraint, $matches)) {
                switch ($key) {
                    case 'primary':
                        return [
                            'drop' => 'ALTER TABLE `'.$table.'` DROP PRIMARY KEY;',
                            'add'  => 'ALTER TABLE `'.$table.'` ADD '.rtrim($constraint, ',')
                        ];
                    case 'key':
                        return [
                            'drop' => "ALTER TABLE `{$table}` DROP KEY $matches[2];",
                            'add'  => 'ALTER TABLE `'.$table.'` ADD '.rtrim($constraint, ',')
                        ];
                    case 'constraint':
                        return [
                            'drop' => "ALTER TABLE `{$table}` DROP CONSTRAINT $matches[2];",
                            'add'  => 'ALTER TABLE `'.$table.'` CONSTRAINT '.rtrim($constraint, ',')
                        ];
                }
                break;
            }
        }
    }
}