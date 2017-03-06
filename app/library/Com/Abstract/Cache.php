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
     * 缓存池名称(配置)，必须指定 cache_pool.php key值
     * @var string
     */
    protected $cachePoolName = '';

    /**
     * @var Com_Cache_Interface
     */
    private $cacheObj = null;

    public function __construct($pool = null)
    {
        $pool = $pool ? $pool : $this->cachePoolName;
        if (empty($pool)) {
            throw new Exception_Cache(CACHE_ERR_CONFIG, get_class($this) . ' property cache_pool must be assigned');
        }
        $this->setPool($pool);
    }

    /**
     * 动态设置缓存池
     * @param string $pool
     */
    private function setPool($pool)
    {
        $this->cacheObj = Com_Cache_Pool::connect($pool);
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
        $id = join('_', $args);
        if (isset(self::$cache['key'][$id])) {
            return self::$cache['key'][$id];
        }

        if (empty($name)) {
            throw new Exception_Cache(CACHE_ERR_CONFIG, 'key name does not empty');
        }
        if (!isset($this->configs[$name][0])) {
            throw new Exception_Cache(CACHE_ERR_CONFIG, 'Key name ' . $name . ' illegal');
        }


        if (isset(self::$cache['key_prefix'][$this->keyPrefix])) {
            $args[0] = self::$cache['key_prefix'][$this->keyPrefix];
        } else {//默认的cache前缀
            $args[0] = Com_Tool::getConfig("cache_key_prefix")->{$this->keyPrefix};
            self::$cache['key_prefix'][$this->keyPrefix] = $args[0];
        }

        return self::$cache['key'][$id] = vsprintf($this->configs[$name][0], $args);
    }

}
