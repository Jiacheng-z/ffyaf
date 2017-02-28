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
     * 引入一些无法自动加载的类文件
     */
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
        if (Tool::getConfig()->enableXhprof == true) {
            Ext_Xhprof::start();
        }
    }

    public function _initLogger()
    {
        $logger = new Sys_Log(Tool::getConfig()->runtimePath, "application.log");
        Yaf_Registry::set("logger", $logger);
    }

    public function _initSession()
    {
        $config = Tool::getConfig();

        ini_set("session.name", $config->session->name);
        ini_set("session.save_handler", $config->session->save_handler);
        ini_set("session.save_path", $config->session->save_path);
        ini_set("session.cookie_domain", $config->session->cookie_domain);
        ini_set("session.gc_maxlifetime", $config->session->gc_maxlifetime);
        ini_set("session.gc_probability", $config->session->gc_probability);
    }

    public function _initRouter()
    {
        if (Tool::getConfig()->urlRewrite == true) {
            $router = Yaf_Dispatcher::getInstance()->getRouter();
            $config = new Yaf_Config_Simple(include(CONFIG_PATH . 'router.php'));
            $router->addConfig($config);
        }
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
