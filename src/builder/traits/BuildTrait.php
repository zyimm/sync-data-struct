<?php

namespace Zyimm\dbStructSync\builder\traits;

trait BuildTrait
{
    private $diffSql = [];

    /**
     * arrayDiffAssocRecursive
     *
     * @param $array1
     * @param $array2
     * @param  array  $exclude
     * @return array
     */
    public static function arrayDiffAssocRecursive($array1, $array2, $exclude = [])
    {
        $data = [];
        foreach ($array1 as $k => $v) {
            if ($exclude && in_array($k, $exclude)) {
                continue;
            }
            if (!isset($array2[$k])) {
                $data[$k] = $v;
            } else {
                if (is_array($v) && is_array($array2[$k])) {
                    $data[$k] = self::arrayDiffAssocRecursive($v, $array2[$k]);
                } else {
                    if ($v != $array2[$k]) {
                        $data[$k] = $v;
                    } else {
                        unset($array1[$k]);
                    }
                }
            }
        }
        return array_filter($data);
    }

    /**
     * getDiffSql
     *
     * @return array
     */
    public function getDiffSql()
    {
        return $this->diffSql;
    }
}