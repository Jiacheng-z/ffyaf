<?php

/**
 * 单例模式
 * Class Singleton
 */
abstract class Singleton
{
    private function __construct()
    {
    }

    static public function getInstance()
    {
        $class = get_called_class();
        if (!($class::$_instance instanceof $class)) {
            $class::$_instance = new $class;
        }
        return $class::$_instance;
    }
}
