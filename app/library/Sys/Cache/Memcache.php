<?php

class Sys_Cache_Memcache extends Singleton
{
    private $cache;
    static private $_instance = array();

    public function __construct($obj = 'miaoche')
    {
        $config = Yaf_Registry::get("config");
        $this->conf = $config->cache->$obj;
    }

    static public function getInstance($obj = 'miaoche')
    {
        if (!isset(self::$_instance[$obj])) {
            $instance = new CMemcache($obj);
            self::$_instance[$obj] = $instance;
        } else {
            $instance = self::$_instance[$obj];
        }
        return $instance;
    }

    private function connect($conf)
    {
        static $cache = null;
        if ($cache === null) {
            if (file_exists(CONFIG_PATH . '/memcache.php')) {
                if (!($this->_config = Yaf_Registry::get("memcache_config"))) {
                    $appConfig = Yaf_Application::app()->getConfig();
                    $this->_config = new Yaf_Config_Simple(include(CONFIG_PATH . '/memcache.php'));
                    Yaf_Registry::set("memcache_config", $this->_config);
                }
                $cache = new Memcache();
                ini_set("memcache.allow_failover", 1);
                ini_set("memcache.max_failover_attempts", 2);
                ini_set('memcache.hash_function', 'crc32');
                ini_set('memcache.hash_strategy', 'consistent');
                foreach ($this->_config as $v) {
                    $cache->addServer($v->host, $v->port);
                }
            } else {
                $cache = new Memcache();
                $cache->connect($conf->host, $conf->port);
            }
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
        throw new Exception(get_class($this) . "::{$strMethod} not defined");
    }
}

?>
