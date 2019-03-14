<?php

class Cache_Example extends Com_Abstract_Cache
{
    /**
     * 定义key前缀
     */
    protected $keyPrefix = 'example_cache_prefix';

    protected $configs = [
        'id' => ['%s:example:id:%s', T_MIN * 5],
    ];


    public function setExample($id, $value)
    {
        $this->setArray('id', $id, $value);
    }

    public function getExample($id)
    {
        return $this->getArray('id', $id);
    }

    public function delExample($id)
    {
        $this->delCache('id', $id);
    }

}