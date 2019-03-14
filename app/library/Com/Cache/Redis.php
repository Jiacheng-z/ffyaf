<?php

class Com_Cache_Redis extends Redis implements Com_Cache_Interface
{

    /**
     * @var string  缓存池名称 cache_pool.php key值
     */
    protected $poolName = '';
    protected $_addr = '';
    protected $_port = '';

    /**
     * @var array 主从库连接标识
     *
     * static数据成员独立于该类的任意对象而存在 ,因为需要按$poolName来区别redis是否已经连接
     *
     * $connect_flag 最终格式为:
     * $connect_flag = [
     *           '$poolName' =>['master' => false,'slave' => false, 'master_obj' => object, 'slave_obj' => object],
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

    /**
     * 连接redis
     * @param $source
     * @param bool $pconnect true 表示长连接 false 表示短连接
     * @return Com_Cache_Interface
     * @throws Exception_Cache
     * @throws Yaf_Exception
     */
    public function connection($source, $pconnect = false)
    {
        if (isset(self::$connect_flag[$this->getPoolName()][$source]) && self::$connect_flag[$this->getPoolName()][$source] == true) {
            return self::$connect_flag[$this->getPoolName()][$source . '_obj'];
        }

        $config = $this->getPoolConfig($source);

        $redis = new self();
        $redis->setPoolName($this->getPoolName());

        foreach ($config as $server) {
            list($addr, $port) = explode(':', $server, 2);
            $addr = isset($_SERVER[$addr]) ? $_SERVER[$addr] : $addr;
            $port = isset($_SERVER[$port]) ? $_SERVER[$port] : $port;

            if ($pconnect === true) {
                $redis->pconnect($addr, $port);   //连接不会主动关闭
            } else {
                $redis->connect($addr, $port);
            }
        }
        self::$connect_flag[$this->getPoolName()][$source . '_obj'] = $redis;
        return self::$connect_flag[$this->getPoolName()][$source . '_obj'];
    }

    /**
     * 获取连接池的配置信息
     * @param $source
     * @return array
     * @throws Exception_Cache
     * @throws Yaf_Exception
     */
    protected function getPoolConfig($source)
    {
        $configs = Com_Config::get("cache_pool_" . Util_Tool::idc())->toArray();

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

    /**
     * 获取value值
     * @param $key
     * @return bool|string
     */
    public function getValue($key)
    {
        $main = Com_Config::get();
        if (Com_Util::isDebug() and $main->enableCache == false) {
            return false;
        }

        if (empty($key)) {
            return false;
        }
        $ttl = parent::ttl($key);
        $exists = parent::exists($key);
        if (empty($exists) OR empty($ttl)) {
            return false;
        }
        if ($ttl <= 0 and $ttl != -1) {
            return false;
        }

        $result = parent::get($key);
        return $result;
    }

    /**
     * 写入一条数据
     * @param $key
     * @param $value
     * @param int $expire 过期时间 0:永久存储
     * @return bool
     */
    public function setValue($key, $value, $expire = 60)
    {
        if (empty($key)) {
            return false;
        }

        if (!is_numeric($expire) || $expire < 0) {
            return false;
        }

        if ($expire == 0) {
            $result = parent::set($key, $value);
        } else {
            $result = parent::setex($key, $expire, $value);
        }
        if ($result === false) {
            $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
            $err->setLog('[CACHE][REDIS][SET]: message=>return false;key=>' . $key);
            unset($err);
        }
        return $result;
    }

    /**
     * 获取value值(集合)
     * @param $key
     * @return bool|string
     */
    public function sMembers($key)
    {
        if (empty($key)) {
            return false;
        }
        $result = parent::sMembers($key);
        return $result;
    }

    /**
     * 写入一条数据(集合)
     * @param $key
     * @param $value
     * @param int $expire 过期时间 0:永久存储
     * @return bool
     */
    public function sAdd($key, $value, $expire = 60)
    {
        if (empty($key)) {
            return false;
        }

        if (!is_numeric($expire) || $expire < 0) {
            return false;
        }

        $result = true;
        $result &= parent::sAdd($key, $value);

        if ($expire != 0) {
            $result &= parent::expire($key, $expire);
        }
        if ($result === false) {
            $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
            $err->setLog('[CACHE][REDIS][SET]: message=>return false;key=>' . $key);
            unset($err);
        }
        return $result;
    }

    /**
     * @param array|int $key $redis->delete('key')
     * @return bool|int
     */
    public function del($key)
    {
        if (empty($key)) {
            return false;
        }
        return parent::del($key);
    }

    public function mget(array $keys)
    {
        if (empty($keys)) {
            return false;
        }
        $result = parent::mget($keys);
        $result = is_array($result) ? @array_combine($keys, $result) : false;
        return $result;
    }

    /**
     * 批量插入数据 array('key'=>'value')
     *
     * $redis->mset(array('key0' => 'value0', 'key1' => 'value1'));
     *
     * @param array $values
     * @param int $expire
     * @return bool
     */
    public function mset(array $values, $expire = 60)
    {
        if (empty($values)) {
            return false;
        }
        return parent::mset($values);
    }

    /**
     * @param array $keys $redis->delete(array('key3', 'key4'));
     * @return bool|int
     */
    public function mdel(array $keys)
    {
        if (empty($keys)) {
            return false;
        }
        return parent::del($keys);
    }

    /**
     * 哈希set
     * @param string $key
     * @param string $filed
     * @param string $value
     * @return int
     */
    public function hSet($key, $filed, $value)
    {
        if (empty($key) || empty($filed)) {
            return false;
        }
        return parent::hSet($key, $filed, $value);
    }

    /**
     * 哈希get指定key, field的value
     * @param string $key
     * @param string $field
     * @return bool|string
     */
    public function hGet($key, $field)
    {
        if (empty($key) || empty($field)) {
            return false;
        }

        return parent::hGet($key, $field);
    }

    /**
     * 哈希获取指定key的所有字段和值
     * @param string $key
     * @return array|bool
     */
    public function hGetAll($key)
    {
        if (empty($key)) {
            return false;
        }

        return parent::hGetAll($key);
    }

    /**
     * 哈希删除指定key的指定字段
     * @param string $key
     * @param string $field
     * @return bool|int
     */
    public function hDel($key, $field)
    {
        if (empty($key) || empty($field)) {
            return false;
        }

        return parent::hDel($key, $field);
    }

    /**
     * sorted sets
     * 加入或更新一个成员
     * @param string $key
     * @param float $score
     * @param string $value
     * @param int $expire
     * @return bool|int
     */
    public function zAdd($key, $score, $value, $expire = 60)
    {
        if (empty($key)) {
            return false;
        }

        if (!is_numeric($expire) || $expire < 0) {
            return false;
        }

        $result = true;
        $result &= parent::zAdd($key, $score, $value);

        if ($expire != 0) {
            $result &= parent::expire($key, $expire);
        }
        if ($result == false) {
            $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
            $err->setLog('[CACHE][REDIS][SET]: message=>return false;key=>' . $key);
            unset($err);
        }
        return $result;
    }

    /**
     * sorted sets
     * 通过索引获得区间内成员
     * @param string $key
     * @param int $start
     * @param int $end
     * @param null $withscores
     * @return array|bool
     */
    public function zRange($key, $start, $end, $withscores = null)
    {
        if (empty($key)) {
            return false;
        }

        return parent::zRange($key, $start, $end, $withscores);
    }

    /**
     * sorted sets
     * 删除指定分数区间的成员
     * @param string $key
     * @param float|string $start
     * @param float|string $end
     * @return bool|int
     */
    public function zRemRangeByScore($key, $start, $end)
    {
        if (empty($key)) {
            return false;
        }

        return parent::zRemRangeByScore($key, $start, $end);
    }

    /**
     *
     * 关闭连接
     */
    public function close()
    {
        parent::close();
    }
}
