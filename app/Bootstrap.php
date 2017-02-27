<?php

/**
 * 所有在Bootstrap类中, 以_init开头的方法, 都会被Ap调用,
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf_Bootstrap_Abstract
{
    /**
     * 定义项目内使用的常量
     */
    public function _initConst()
    {
        Yaf_Loader::import(APPLICATION_PATH . "library/Sys/Const.php");
    }

    /**
     * 初始化配置
     */
    public function _initConfig()
    {
        Yaf_Registry::set("config", Yaf_Application::app()->getConfig());
    }

    public function _initLoader()
    {
        Yaf_Loader::import(APPLICATION_PATH . "library/Sys/Abstract/Controller.php"); /* 导入Controller基础类 */
        Yaf_Loader::import(APPLICATION_PATH . "library/Sys/Abstract/Action.php"); /* 导入Action基础类 */
        Yaf_Loader::import(APPLICATION_PATH . "library/Sys/Abstract/View.php"); /* 导入View基础类 */

        Yaf_Loader::import(APPLICATION_PATH . "library/Sys/Exception/Exception.php"); /* 导入系统异常类 */
    }

    /**
     * 初始化异常相关
     */
    public function _initError()
    {
        defined('YAF_ENABLE_EXCEPTION_HANDLER') or define('YAF_ENABLE_EXCEPTION_HANDLER', true);
        defined('YAF_ENABLE_ERROR_HANDLER') or define('YAF_ENABLE_ERROR_HANDLER', true);
        defined('YAF_TRACE_LEVEL') or define('YAF_TRACE_LEVEL', 3);
        Sys_Exception_Handler::initHandler();
    }

    /**
     * xhprof
     */
    public function _initXhprof()
    {
        $config = Yaf_Registry::get("config");
        if ($config->enableXhprof == true) {
            Ext_Xhprof::start();
        }
    }

    public function _initLogger()
    {
        $config = Yaf_Application::app()->getConfig();
        $logger = new Sys_Log($config->runtimePath, "application.log");
        Yaf_Registry::set("logger", $logger);
    }

    public function _initSession()
    {
//        $config = Yaf_Registry::get("config");
//        $redis = $config->redis->miaoche;
//        $save_path = "tcp://" . $redis->host . ":" . $redis->port;
//        $save_path .= "?auth=" . $redis->auth . "&prefix=SESSION:WWW:&timeout=1";
//        ini_set("session.save_path", $save_path);
//        ini_set("session.save_handler", 'redis');
//
//        ini_set("session.gc_maxlifetime", 1440);
//        ini_set("session.gc_probability", 0);
    }

    public function _initRouter()
    {
        $dispatcher = Yaf_Application::app()->getDispatcher();

        $router = $dispatcher->getRouter();
        $config = new Yaf_Config_Simple(include(CONFIG_PATH . '/router.php'));
        $router->addConfig($config);
    }


    /**
     * 加载钩子
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initPlugin(Yaf_Dispatcher $dispatcher)
    {
        $system = new SystemPlugin();
        $dispatcher->registerPlugin($system);
    }


}
