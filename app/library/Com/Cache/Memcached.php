<?php

class Com_Cache_Memcached extends Memcached implements Com_Cache_Interface
{

    /**
     * @var string  缓存池名称 cache_pool.php key值
     */
    protected $poolName = '';

    /**
     * @var array 主从库连接标识
     *
     * static数据成员独立于该类的任意对象而存在 ,因为需要按$poolName来区别redis是否已经连接
     *
     * $connect_flag 最终格式为:
     * $connect_flag = [
     *           '$poolName' =>['master' => false,'slave' => false],
     *           '$poolName' =>['master' => false,'slave' => false],
     *            ......
     *             ];
     */
    private static $connect_flag = array();

    /**
     * 获取缓存池
     * @return string
     */
    public function getPoolName()
    {
        return $this->poolName;
    }

    /**
     * 设置缓存池name
     * @param $name
     */
    public function setPoolName($name)
    {
        $this->poolName = $name;
    }

    /*
     * @see Comm_Cache_Interface::configure()
     * 连接memcached
     *
     */
    public function connection($source)
    {
        if (isset(self::$connect_flag[$this->getPoolName()][$source]) && self::$connect_flag[$this->getPoolName()][$source] == true) {
            return $this;
        }

        $config = $this->getPoolConfig($source);

        foreach ($config as $server) {
            list($addr, $port) = explode(':', $server, 2);
            $this->addServer($addr, $port);
        }

        $this->setOption(Memcached::OPT_NO_BLOCK, true);
        $this->setOption(Memcached::OPT_CONNECT_TIMEOUT, 200);
        $this->setOption(Memcached::OPT_POLL_TIMEOUT, 50);


        return $this;
    }

    /**
     * 获取连接池的配置信息
     * @param $source    master or slave
     * @return $this
     * @throws Exception_Cache
     */
    protected function getPoolConfig($source)
    {
        $configs = Com_Config::get("cache_pool_north");

        if (is_object($configs)) {
            $configs = $configs->toArray();
        }

        $cachePoolName = $this->getPoolName();
        if (isset($configs[$cachePoolName]['config'][$source])) {
            $config = $configs[$cachePoolName]['config'][$source];
        } else {
            $config = $configs[$cachePoolName]['config'];
        }

        if (is_string($config) && isset($_SERVER[$config])) {
            $config = explode(' ', $_SERVER[$config]);
        } elseif (is_array($config)) {//no need to do
        } else {
            throw new Exception_Cache(CACHE_ERR_CONFIG,
                'Config should be an array of "addr:port"s or a name of $_SERVER param');
        }

        if (isset($configs[$cachePoolName]['config'][$source])) {
            self::$connect_flag[$this->getPoolName()][$source] = true;
        } else {
            self::$connect_flag[$this->getPoolName()]['master'] = true;
            self::$connect_flag[$this->getPoolName()]['slave'] = true;
        }

        return $config;
    }


    /* (non-PHPdoc)
	 * @see Comm_Cache_Interface::get()
	 */
    public function getValue($key)
    {
        if (empty($key)) {
            return false;
        }

        $result = parent::get($key);
        if ($result === false) {
            $result_code = $this->getResultCode();
            if ($result_code != Memcached::RES_SUCCESS && $result_code != Memcached::RES_NOTFOUND) {

                $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
                $err->setLog('[CACHE][MEMCACHED][GET]: message=>' . $this->getResultMessage() . ';key=>' . $key);
                unset($err);

                return false;
            }
        }
        return $result;
    }

    /* (non-PHPdoc)
     * @see Comm_Cache_Interface::set()
     */
    public function setValue($key, $value, $expire = 60)
    {
        if (empty($key)) {
            return false;
        }

        $ret = parent::set($key, $value, $expire);
        if ($ret === false) {
            $ret = parent::set($key, $value, $expire);
            if ($ret === false) {

                $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
                $err->setLog('[CACHE][MEMCACHED][SET]: message=>' . $this->getResultMessage() . ';key=>' . $key);
                unset($err);

                return false;
            }
        }
        return true;
    }


    /* (non-PHPdoc)
     * @see Comm_Cache_Interface::del()
     */
    public function del($key)
    {
        if (empty($key)) {
            return false;
        }
        $ret = parent::delete($key);
        if (false === $ret) {
            $result_code = $this->getResultCode();
            if ($result_code != Memcached::RES_SUCCESS && $result_code != Memcached::RES_NOTFOUND) {
                $ret = parent::delete($key);
                if ($ret === false) {
                    $result_code = $this->getResultCode();
                    if ($result_code != Memcached::RES_SUCCESS && $result_code != Memcached::RES_NOTFOUND) {

                        $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
                        $err->setLog('[CACHE][MEMCACHED][DEL]: message=>' . $this->getResultMessage() . ';key=>' . $key);
                        unset($err);

                        return false;
                    }
                }
            }
        }
        return true;
    }

    /* (non-PHPdoc)
     * @see Comm_Cache_Interface::mget()
     */
    public function mget(array $keys)
    {
        $ret = parent::getMulti($keys);
        if (false === $ret) {
            $ret = array();
        }

        foreach ($keys as $key) {
            if (!isset($ret[$key])) {
                $ret[$key] = false;
            }
        }
        return $ret;
    }

    /* (non-PHPdoc)
     * @see Comm_Cache_Interface::mset()
     */
    public function mset(array $values, $expire = 60)
    {
        return parent::setMulti($values, $expire);
    }

    /* (non-PHPdoc)
     * @see Comm_Cache_Interface::mdel()
     */
    public function mdel(array $keys)
    {
        foreach ($keys as $key) {
            parent::delete($key);
        }
    }

}
