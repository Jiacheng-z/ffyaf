<?php

class Sys_Cache_Yac extends Singleton
{
    private $cache;
    private $_prefix;
    static private $_instance = array();

    public function __construct($obj = '')
    {
        $this->_prefix = $obj;
    }

    static public function getInstance($obj = 'miaoche')
    {
        if (!isset(self::$_instance[$obj])) {
            $instance = new CYac($obj);
            self::$_instance[$obj] = $instance;
        } else {
            $instance = self::$_instance[$obj];
        }
        return $instance;
    }

    public function __call($strMethod, $arrParam)
    {
        $mainConfig = Yaf_Application::app()->getConfig();
        if (!$mainConfig->enableCache) {
            return;
        } elseif (!isset($this->cache)) {
            $this->cache = new Yac($this->_prefix);
        }

        if (method_exists($this->cache, $strMethod)) {
            return call_user_func_array(array($this->cache, $strMethod), $arrParam);
        }
        throw new Exception(get_class($this) . "::{$strMethod} not defined");
    }
}
