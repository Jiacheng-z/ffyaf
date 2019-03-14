<?php

/**
 * cache连接池
 * Class Com_Cache_Pool
 */
class Com_Cache_Pool
{
    /**
     * 连接的配置
     * @var array
     */
    private static $configs = [];
    private static $pools = [];

    const YAC = 'yac';

    const IDC_NORTH = 1;
    const IDC_SOUTH = 2;

    const SOUTH_IDC = []; //南方IDC开头

    public static function init()
    {

        self::$configs = Com_Config::get("cache_pool_" . Util_Tool::idc())->toArray();

        //把 Yac 添加进池子
        self::$configs[self::YAC] = [
            'backend' => 'Yac',
            'config' => [
                'master' => [
                    '127.0.0.1',
                ],
                'slave' => [
                    '127.0.0.1',
                ]
            ]
        ];
    }

    /**
     * 根据名称获取缓存对象
     * @param $name
     * @return mixed
     * @throws Exception_Cache
     * @throws Yaf_Exception
     */
    public static function initCacheObj($name)
    {
        if (isset(self::$pools[$name])) {
            return self::$pools[$name];
        }

        if (!isset(self::$configs[$name])) {
            throw new Exception_Cache(CACHE_ERR_CONFIG, "pool " . $name . " not defined");
        }

        $backend = self::$configs[$name]['backend'];
        $class = 'Com_Cache_' . $backend;
        if (!class_exists($class) || !in_array('Com_Cache_Interface', class_implements($class))) {
            throw new Exception_Cache(CACHE_ERR_PARAMS,
                'Cache type must be a valid backend type and implements Comm_Cache_Interface');
        }

        $cache = new $class;
        $cache->setPoolName($name);

        self::bind($name, $cache);

        return $cache;
    }

    /**
     * 将缓存绑定到一个名字。当缓存名字已经占用的时候，会抛出一个异常
     * @param $name
     * @param Com_Cache_Interface $cacheObj
     * @throws Exception_Cache
     * @throws Yaf_Exception
     */
    protected static function bind($name, Com_Cache_Interface $cacheObj)
    {
        if (isset(self::$pools[$name])) {
            throw new Exception_Cache(CACHE_ERR_PARAMS, "Cache $name already defined");
        }

        self::$pools[$name] = $cacheObj;
    }

    /**
     * 将指定的缓存名字与其实例解绑。并返回被解绑的实例。
     * @param string $name
     * @return Com_Cache_Interface
     */
    public static function unbind($name)
    {
        if (isset(self::$pools[$name])) {
            $instance = self::$pools[$name];
            unset(self::$pools[$name]);
        } else {
            $instance = null;
        }
        return $instance;
    }

    /**
     * 清除所有缓存名字与其实例的绑定。并返回被解绑的实例数组。
     * @return array of Com_Cache_Interface
     */
    public static function unbindAll()
    {
        $pools = self::$pools;
        self::$pools = [];
        return $pools;
    }

}