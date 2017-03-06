<?php


class Cache_Test extends Com_Abstract_Cache
{

    /**
     * cache key的配置
     * @var array
     */
    protected $configs = [
        "t1" => ["%s_jc_%s", 61],
    ];

    /**
     * 定义key前缀
     */
    protected $keyPrefix = 't1';

    public function test()
    {
        $key = $this->key("t1", 123);
//        $this->cacheObj()->setValue($key, "values");
        $ret = $this->cacheObj()->getValue($key);
        return $ret;
    }

}