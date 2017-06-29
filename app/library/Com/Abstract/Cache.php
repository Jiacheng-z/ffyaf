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
     * @param string $pool
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
     */
    protected function setArray($keyName)
    {
        $args = func_get_args();
        $params = array_slice($args, 1, count($args) - 2);
        $value = end($args);

        $key = $this->keyArr($keyName, $params);
        $live = $this->livetime($keyName);

        if (!is_string($value)) {
            $value = json_encode($value);
        }
        $this->cacheObj()->connection(self::CACHE_M)->setValue($key, $value, $live);
    }

    /**
     * 通用获取缓存逻辑
     * @param $keyName
     * @return mixed
     */
    protected function getArray($keyName)
    {
        $args = func_get_args();
        $params = array_slice($args, 1, count($args) - 1);

        $key = $this->keyArr($keyName, $params);
        $ret = $this->cacheObj()->connection(self::CACHE_S)->getValue($key);
        if (!empty($ret)) {
            $ret = json_decode($ret, true);
        }

        return $ret;
    }

    /**
     * cache的存储过程
     * @param $contextKey
     * @param callable $getCache
     * @param callable $setCache
     * @param callable $data
     * @param array $default
     * @return array
     */
    public static function cacheProcess(
        $contextKey,
        callable &$getCache,
        callable &$setCache,
        callable &$data,
        $default = []
    ) {
        $ret = $default;
        do {
            $ret = Com_Context::get($contextKey);
            if (!empty($ret)) {
                break;
            }

            $ret = $getCache();
            if (!empty($ret)) {
                Com_Context::set($contextKey, $ret);
                break;
            }

            $ret = $data();
            if (empty($ret)) {
                return $default;
            }
            $setCache($ret);
            Com_Context::set($contextKey, $ret);

        } while (false);

        return $ret;
    }
}
