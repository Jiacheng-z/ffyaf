<?php

/**
 * cache实现类应该实现的方法
 * Interface Com_Cache_Interface
 */
interface Com_Cache_Interface
{
    public function getPoolName();

    public function setPoolName($name);

    /**
     * @param $source
     * @return $this
     */
    public function connection($source);

    public function getValue($key);

    public function setValue($key, $value, $expire = 60);

//    public function increment($key, $offset = 1);

//    public function decrement($key, $offset = 1);

    public function del($key);

    public function mget(array $keys);

    public function mset(array $values, $expire = 60);

    public function mdel(array $keys);
}