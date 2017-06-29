<?php


class Cache_Test extends Com_Abstract_Cache
{

    /**
     * cache key的配置
     * @var array
     */
    protected $configs = [
        "ex1" => ["%s_ex1_%s", T_MIN],
        "ex2" => ["%s_ex2_%s", T_HOUR],
        "ex3" => ["%s_ex3_%s", T_DAY],
    ];

    /**
     * 定义key前缀
     * cache_key_prefix中定义的
     */
    protected $keyPrefix = 'example';


    public function setExample($id, $value)
    {
        $this->setArray('ex1', $id, $value);
    }

    public function getExample($id)
    {
        return $this->getArray('ex1', $id);
    }

}