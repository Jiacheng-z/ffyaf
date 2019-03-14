<?php

/**
 * 缓存相关访问方法
 * Class Com_Abstract_Cache
 */
abstract class Com_Abstract_Cache
{
    /**
     * cache key的配置
     * @var array
     */
    protected $configs = [];

    /**
     * cache_key_prefix.php key值
     */
    protected $keyPrefix = '';

    /**
     * @var Com_Cache_Interface
     */
    private $cacheObj = null;

    /**
     * 连接主库常量
     */
    const CACHE_M = 'master';

    /**
     * 连接从库常量
     */
    const CACHE_S = 'slave';

    public function __construct($pool = null)
    {
        if (empty($pool)) {
            throw new Exception_Cache(CACHE_ERR_CONFIG, get_class($this) . ' property cache_pool must be assigned');
        }
        $this->setPool($pool);
    }

    /**
     * 动态设置缓存池
     * @param $pool
     * @throws Exception_Cache
     * @throws Yaf_Exception
     */
    private function setPool($pool)
    {
        $this->cacheObj = Com_Cache_Pool::initCacheObj($pool);
    }

    /**
     * @return Com_Cache_Interface
     */
    protected function cacheObj()
    {
        return $this->cacheObj;
    }

    protected static $cache;

    protected function key($name)
    {
        $args = func_get_args();
        return $this->keyName($name, $args);
    }

    private function keyArr($name, $arr)
    {
        $args = array_merge([$name], $arr);
        return $this->keyName($name, $args);
    }

    private function keyName($name, $args)
    {
        $id = join('_', $args);
        if (isset(self::$cache['key'][$this->keyPrefix][$id])) {
            return self::$cache['key'][$this->keyPrefix][$id];
        }

        if (empty($name)) {
            throw new Exception_Cache(CACHE_ERR_CONFIG, 'key name does not empty');
        }
        if (!isset($this->configs[$name][0])) {
            throw new Exception_Cache(CACHE_ERR_CONFIG, 'Key name ' . $name . ' illegal');
        }

        $prefix = Com_Config::get()->cachePrefix . '_';
        if (isset(self::$cache['key_prefix'][$this->keyPrefix])) {
            $args[0] = self::$cache['key_prefix'][$this->keyPrefix];
        } else {//默认的cache前缀
            $args[0] = $prefix . Com_Config::get("cache_key_prefix")->{$this->keyPrefix};
            self::$cache['key_prefix'][$this->keyPrefix] = $args[0];
        }

        return self::$cache['key'][$this->keyPrefix][$id] = vsprintf($this->configs[$name][0], $args);
    }

    /**
     * 获取缓存单元缓存时间，未指定，默认缓存时间为60秒
     *
     * @param $name
     * @return int
     * @throws Exception_Cache
     * @throws Yaf_Exception
     */
    protected function livetime($name)
    {
        if (empty($name)) {
            throw new Exception_Cache(CACHE_ERR_CONFIG, 'key name does not empty');
        }
        if (isset($this->configs[$name][1]) && !is_integer($this->configs[$name][1])) {
            throw new Exception_Cache(CACHE_ERR_CONFIG, 'live time must be is valid integer');
        }
        return isset($this->configs[$name][1]) ? $this->configs[$name][1] : 60;
    }

    /**
     * 通用设置缓存逻辑
     * @param $keyName
     * @throws Exception_Cache
     * @throws Yaf_Exception
     */
    protected function setArray($keyName)
    {
        $args = func_get_args();
        $params = array_slice($args, 1, count($args) - 2);
        $value = end($args);

        $key = $this->keyArr($keyName, $params);
        $live = $this->livetime($keyName);
        $this->cacheObj()->connection(self::CACHE_M)->setValue($key, json_encode($value), $live);
    }

    /**
     * 通用获取缓存逻辑
     * @param $keyName
     * @return array|null
     */
    protected function getArray($keyName)
    {
        $args = func_get_args();
        $params = array_slice($args, 1, count($args) - 1);

        $key = $this->keyArr($keyName, $params);
        $ret = $this->cacheObj()->connection(self::CACHE_S)->getValue($key);
        return (empty($ret)) ? null : json_decode($ret, true);
    }

    protected function delCache($keyName)
    {
        $args = func_get_args();
        $params = array_slice($args, 1, count($args) - 1);

        $key = $this->keyArr($keyName, $params);
        $this->cacheObj()->connection(self::CACHE_M)->del($key);
    }

    /**
     * 通用设置缓存逻辑
     * @param $keyName
     * @throws Exception_Cache
     * @throws Yaf_Exception
     */
    protected function setNotArray($keyName)
    {
        $args = func_get_args();
        $params = array_slice($args, 1, count($args) - 2);
        $value = end($args);

        $key = $this->keyArr($keyName, $params);
        $live = $this->livetime($keyName);
        $this->cacheObj()->connection(self::CACHE_M)->setValue($key, $value, $live);
    }

    /**
     * 通用获取缓存逻辑
     * @param $keyName
     * @return null|string|integer
     */
    protected function getNotArray($keyName)
    {
        $args = func_get_args();
        $params = array_slice($args, 1, count($args) - 1);

        $key = $this->keyArr($keyName, $params);
        $ret = $this->cacheObj()->connection(self::CACHE_S)->getValue($key);
        return (empty($ret)) ? null : $ret;
    }
}
