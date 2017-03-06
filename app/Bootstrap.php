<?php

/**
 * 所有在Bootstrap类中, 以_init开头的方法, 都会被Ap调用,
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf_Bootstrap_Abstract
{
    /**
     * 必要的引入
     */
    public function _initLoader()
    {
        Yaf_Loader::import(APPLICATION_PATH . "library/Com/Const.php");
        Yaf_Loader::import(APPLICATION_PATH . "library/Const.php");
        Yaf_Loader::import(APPLICATION_PATH . "library/Com/Abstract/Controller.php"); /* 导入Controller基础类 */
        Yaf_Loader::import(APPLICATION_PATH . "library/Com/Abstract/Action.php"); /* 导入Action基础类 */
        Yaf_Loader::import(APPLICATION_PATH . "library/Com/Abstract/View.php"); /* 导入View基础类 */
    }

    /**
     * Enter description here ...
     *
     * @param Yaf_Dispatcher $dispatcher dispatcher
     *
     * @return void
     */
    public function _initConfig(Yaf_Dispatcher $dispatcher)
    {
        Com_Cache_Pool::init();
        Com_Context::init();
    }

    /**
     * 初始化异常相关
     */
    public function _initError()
    {
        Com_Exception_Handler::initHandler();
    }

    public function _initLogger()
    {
        if (Com_Tool::isDebug()) {

            define("LOG_RUNTIME_PATH", Com_Config::get()->runtimePath);
            define("LOG_FILE_APP", "application.log");
            define("LOG_FILE_EXC", "exception.log");
            define("LOG_FILE_ERR", "error.log");
        }
    }

    /**
     * xhprof
     */
    public function _initXhprof()
    {
        if (Com_Tool::isDebug() and Com_Config::get()->enableXhprof == true) {
            Ext_Xhprof::start();
        }
    }

    public function _initSession()
    {
        $config = Com_Config::get();

        ini_set("session.name", $config->session->name);
        ini_set("session.save_handler", $config->session->save_handler);
        ini_set("session.save_path", $config->session->save_path);
        ini_set("session.cookie_domain", $config->session->cookie_domain);
        ini_set("session.gc_maxlifetime", $config->session->gc_maxlifetime);
        ini_set("session.gc_probability", $config->session->gc_probability);
    }

    public function _initRouter()
    {
        if (Com_Config::get()->urlRewrite == true) {
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
