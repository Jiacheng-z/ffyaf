<?php

/**
 * Ap定义了如下的7个Hook,
 * 插件之间的执行顺序是先进先Call
 */
class SystemPlugin extends Yaf_Plugin_Abstract
{
    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        /* 根据controller选择适当的View做为模板输出 */
        $arr = explode('_', $request->getControllerName(), 2);
        switch ($arr[0]) {
            case 'Aj':
                $view = new Com_View_Json();
                $view->setScriptPath(APPLICATION_PATH . "views/");
                Yaf_Dispatcher::getInstance()->setView($view);
                Yaf_Registry::set("view", $view);
                Yaf_Registry::set("viewType", 'json');
                header('Content-type: application/json; charset=utf-8');
                break;
            case 'Api':
                $view = new Com_View_Callback();
                $view->setScriptPath(APPLICATION_PATH . "views/");
                Yaf_Dispatcher::getInstance()->setView($view);
                Yaf_Registry::set("view", $view);
                Yaf_Registry::set("viewType", 'callback');
                header('Content-type: text/html; charset=utf-8');
//                header('Content-type: application/json; charset=utf-8');
                break;

            default:
                $config = array(
                    "left_delimiter" => "{{",
                    "right_delimiter" => "}}",
                    "template_dir" => APPLICATION_PATH . "views/",
                    "compile_dir" => BASE_PATH . "tmp/_templates_c/",
                    "caching" => false,
                    "error_reporting" => 32767,
                    "escape_html" => true,  //默认对所有变量{$variable|escape:"html"}，{$variable nofilter}可有选择的关闭
                );
                $smarty = new Ext_Smarty(null, $config);
                Yaf_Dispatcher::getInstance()->setView($smarty);
                Yaf_Registry::set("view", $smarty);
                Yaf_Registry::set("viewType", 'smarty');
                header('Content-type: text/html;charset=utf-8');
                break;
        }
    }

    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        Com_Log::endAccess();

        if (Com_Util::isDebug() and Com_Config::get()->enableXhprof) {
            Ext_Xhprof::end();
        }
    }
}
