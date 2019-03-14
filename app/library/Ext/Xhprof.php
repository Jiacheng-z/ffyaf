<?php

class Ext_Xhprof
{
    static public function start()
    {
        if (YAF_DEBUG) {
            xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY, [
                "ignored_functions" => [
                    "call_user_func",
                    "call_user_func_array",
                ],
            ]);
        }
    }

    static public function end()
    {
        if (YAF_DEBUG) {
            $xhprof_data = xhprof_disable();
            Yaf_Loader::import(APPLICATION_PATH . "/extensions/xhprof/utils/xhprof_lib.php");
            Yaf_Loader::import(APPLICATION_PATH . "/extensions/xhprof/utils/xhprof_runs.php");

            $xhprof_runs = new XHprofRuns_Default();
            $request = Yaf_Dispatcher::getInstance()->getRequest();
            $controller = $request->getControllerName();
            $action = $request->getActionName();
            $source = 'yaf_' . $controller . '_' . $action;
            $run_id = $xhprof_runs->save_run($xhprof_data, $source);
        }
    }

    static public function startByParam()
    {
        if (Com_Context::getParam('xhprof', 0) == 1) {
            xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY, [
                "ignored_functions" => [
                    "call_user_func",
                    "call_user_func_array",
                ],
            ]);
        }
    }

    static public function endByParam()
    {
        if (Com_Context::getParam('xhprof', 0) == 1) {
            $xhprof_data = xhprof_disable();
            Yaf_Loader::import(APPLICATION_PATH . "/extensions/xhprof/utils/xhprof_lib.php");
            Yaf_Loader::import(APPLICATION_PATH . "/extensions/xhprof/utils/xhprof_runs.php");

            $xhprof_runs = new XHprofRuns_Default();
            $request = Yaf_Dispatcher::getInstance()->getRequest();
            $controller = $request->getControllerName();
            $action = $request->getActionName();
            $source = 'yaf_' . $controller . '_' . $action;
            $run_id = $xhprof_runs->save_run($xhprof_data, $source);
        }
    }
}
