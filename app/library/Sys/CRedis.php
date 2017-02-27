<?php

class CRedis extends Singleton
{
    private $cache;
    static private $_instance = array();

    public function __construct($obj = 'miaoche')
    {
        $config = Yaf_Registry::get("config");
        $this->conf = $config->redis->$obj;
    }

    static public function getInstance($obj = 'miaoche')
    {
        if (!isset(self::$_instance[$obj])) {
            $instance = new CRedis($obj);
            self::$_instance[$obj] = $instance;
        } else {
            $instance = self::$_instance[$obj];
        }
        return $instance;
    }

    private function connect($conf)
    {
        static $cache = null;
        if($cache === null){
            $cache = new Redis();
            $cache->connect($conf->host, $conf->port);
        }
        return $cache;
    }

    public function __call($strMethod, $arrParam)
    {
        $mainConfig = Yaf_Application::app()->getConfig();
        if (!$mainConfig->enableCache) {
            return;
        } elseif (!isset($this->cache)) {
            $this->cache = $this->connect($this->conf);
        }

        if (method_exists($this->cache, $strMethod)) {
            return call_user_func_array(array($this->cache, $strMethod), $arrParam);
        }
        throw new Exception(get_class($this)."::{$strMethod} not defined");
    }
}
