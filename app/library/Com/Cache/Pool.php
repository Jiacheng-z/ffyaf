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


    public static function init()
    {
        self::$configs = Com_Tool::getConfig("cache_pool");
    }

    /**
     * 根据名称建立缓存连接
     * @param $name
     * @return mixed
     * @throws Exception_Program
     */
    public static function connect($name)
    {
        if (isset(self::$pools[$name])) {
            return self::$pools[$name];
        }

        if (!isset(self::$configs[$name])) {
            throw new Exception_Program(CACHE_ERR_CONFIG, "pool $name not defined");
        }

        $backend = self::$configs[$name]['backend'];
        $config = self::$configs[$name]["config"];
        if (is_object($config)) {
            $config = $config->toArray();
        }

        $class = 'Com_Cache_' . $backend;
        if (!class_exists($class) || !in_array('Com_Cache_Interface', class_implements($class))) {
            throw new Exception_Program(CACHE_ERR_PARAMS,
                'Cache type must be a valid backend type and implements Comm_Cache_Interface');
        }

        $cache = new $class;
        $cache->configure($config);

        self::bind($name, $cache);

        return $cache;
    }


    /**
     * 将缓存绑定到一个名字。当缓存名字已经占用的时候，会抛出一个异常。
     *
     * @param string $name
     * @param Com_Cache_Interface $cacheObj
     * @throws Exception_Program
     */
    protected static function bind($name, Com_Cache_Interface $cacheObj)
    {
        if (isset(self::$pools[$name])) {
            throw new Exception_Program(CACHE_ERR_PARAMS, "Cache $name already defined");
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