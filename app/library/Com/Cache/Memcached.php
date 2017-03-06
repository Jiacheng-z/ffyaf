<?php

class Com_Cache_Memcached extends Memcached implements Com_Cache_Interface
{
    /* (non-PHPdoc)
     * @see Comm_Cache_Interface::configure()
     */
    public function configure($config)
    {

        if (is_string($config) && isset($_SERVER[$config])) {
            $config = explode(' ', $_SERVER[$config]);
        } elseif (is_array($config)) {//no need to do
        } else {
            throw new Exception_Program(CACHE_ERR_CONFIG,
                'Config should be an array of "addr:port"s or a name of $_SERVER param');
        }

        foreach ($config as $server) {
            list($addr, $port) = explode(':', $server, 2);
            $this->addServer($addr, $port);
        }

        $this->setOption(Memcached::OPT_NO_BLOCK, true);
        $this->setOption(Memcached::OPT_CONNECT_TIMEOUT, 200);
        $this->setOption(Memcached::OPT_POLL_TIMEOUT, 50);
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

                if (defined(LOG_RUNTIME_PATH) and defined(LOG_FILE_ERR)) {
                    $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
                    $err->setLog('[CACHE][MEMCACHED][GET]: message=>' . $this->getResultMessage() . ';key=>' . $key);
                    unset($err);
                }

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

                if (defined(LOG_RUNTIME_PATH) and defined(LOG_FILE_ERR)) {
                    $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
                    $err->setLog('[CACHE][MEMCACHED][SET]: message=>' . $this->getResultMessage() . ';key=>' . $key);
                    unset($err);
                }

                return false;
            }
        }
        return true;
    }

//    /* (non-PHPdoc)
//     * @see Comm_Cache_Interface::set()
//     */
//    public function increment($key, $offset = 1)
//    {
//        if (empty($key)) {
//            return false;
//        }
//        $ret = parent::increment($key, $offset);
//        if ($ret === false) {
//            $ret = parent::increment($key, $offset);
//            if ($ret === false) {
//
//                if (defined(LOG_RUNTIME_PATH) and defined(LOG_FILE_ERR)) {
//                    $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
//                    $err->setLog('[CACHE][MEMCACHED][INCREMENT]: message=>' . $this->getResultMessage() . ';key=>' . $key);
//                    unset($err);
//                }
//
//                return false;
//            }
//        }
//        return $ret;
//    }

//    /* (non-PHPdoc)
//     * @see Comm_Cache_Interface::set()
//     */
//    public function decrement($key, $offset = 1)
//    {
//        if (empty($key)) {
//            return false;
//        }
//        $ret = parent::decrement($key, $offset);
//        if ($ret === false) {
//            $ret = parent::decrement($key, $offset);
//            if ($ret === false) {
//
//                if (defined(LOG_RUNTIME_PATH) and defined(LOG_FILE_ERR)) {
//                    $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
//                    $err->setLog('[CACHE][MEMCACHED][DECREMENT]: message=>' . $this->getResultMessage() . ';key=>' . $key);
//                    unset($err);
//                }
//
//                return false;
//            }
//        }
//        return $ret;
//    }

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

                        if (defined(LOG_RUNTIME_PATH) and defined(LOG_FILE_ERR)) {
                            $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
                            $err->setLog('[CACHE][MEMCACHED][DEL]: message=>' . $this->getResultMessage() . ';key=>' . $key);
                            unset($err);
                        }

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
