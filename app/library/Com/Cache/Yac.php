<?php

/**
 * Yac 基础类
 * Class Com_Cache_Yac
 */
class Com_Cache_Yac extends Yac implements Com_Cache_Interface
{
    /**
     * @var string  缓存池名称 cache_pool.php key值
     */
    protected $poolName = '';

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
     * 连接Yac
     * @param $source
     * @return $this
     */
    public function connection($source)
    {
        return $this;
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
        return parent::get($key);
    }

    public function setValue($key, $value, $expire = 60)
    {
        if (empty($key)) {
            return false;
        }

        if (!is_numeric($expire) || $expire < 0) {
            return false;
        }

        //yac 缓存要减少(不能跟redis一样长)
        $expire = intval(floor($expire / 2));

        if ($expire == 0) {
            $result = parent::set($key, $value);
        } else {
            $result = parent::set($key, $value, $expire);
        }
        if ($result === false) {
            $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
            $err->setLog('[CACHE][YAC][SET]: message=>return false;key=>' . $key);
        }
        return $result;
    }

    /**
     * @param array|string $key
     * @return bool|int
     */
    public function del($key)
    {
        if (empty($key)) {
            return false;
        }
        return parent::delete($key);
    }

    public function mget(array $keys)
    {
        if (empty($keys)) {
            return false;
        }
        return $this->getValue($keys);
    }


    public function mset(array $values, $expire = 60)
    {
        if (empty($values)) {
            return false;
        }

        $expire = intval(floor($expire / 2));

        if ($expire == 0) {
            $result = parent::set($values);
        } else {
            $result = parent::set($values, $expire);
        }
        return $result;
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
        return $this->del($keys);
    }
}
