<?php

if (!defined("APPMODE")) {
    define("APPMODE", "local");
}

define("BASE_PATH", dirname(__FILE__) . "/../");
define("APPLICATION_PATH", BASE_PATH . "app/");
define("CONFIG_PATH", BASE_PATH . "conf/" . APPMODE . '/');

define("DEBUG_LEVEL", E_ALL);
define("DEBUG_GET_PW", "debug_code");

define("ERR_HANDLER_LEVEL_ONLINE", DEBUG_LEVEL & ~E_DEPRECATED);
define("ERR_HANDLER_LEVEL_DEBUG", DEBUG_LEVEL);

$mainobj = new Yaf_Config_Simple(include(CONFIG_PATH . "main.php"));
$mainArr = $mainobj->toArray();

//DEBUG模式
if (isset($mainArr['enableDebug']) && $mainArr['enableDebug'] == true) {

    define("YAF_DEBUG", true);
    error_reporting(DEBUG_LEVEL);
    ini_set('display_errors', 1);   //针对默认error处理的

} else {

    define("YAF_DEBUG", false);

}
/* INI配置文件支持常量替换 */

/**
 * 默认的, Yaf_Application将会读取配置文件中在php.ini中设置的ap.environ的配置节
 * 另外在配置文件中, 可以替换PHP的常量, 比如此处的APPLICATION_PATH
 */
$main = include(CONFIG_PATH . "main.php");
$application = new Yaf_Application($main);

/* 做简单的统计 */
Com_Benchmark::start();
Com_Benchmark::set_memory(true);

/* 做引入 */
Yaf_Loader::import(APPLICATION_PATH . "library/Com/Const.php");
Yaf_Loader::import(APPLICATION_PATH . "library/Const.php");
Yaf_Loader::import(APPLICATION_PATH . "library/Com/Abstract/Controller.php"); /* 导入Controller基础类 */
Yaf_Loader::import(APPLICATION_PATH . "library/Com/Abstract/Action.php"); /* 导入Action基础类 */
Yaf_Loader::import(APPLICATION_PATH . "library/Com/Abstract/View.php"); /* 导入View基础类 */
Yaf_Loader::import(APPLICATION_PATH . "library/Com/Abstract/Model.php"); /* 导入Model基础类 */

Com_Cache_Pool::init();
Com_Context::init();

$suffix = ".log_" . date("Ymd");
define("LOG_RUNTIME_PATH", Com_Config::get()->runtimePath);
define("LOG_FILE_APP", "script_application" . $suffix);
define("LOG_FILE_EXC", "script_exception" . $suffix);
define("LOG_FILE_ERR", "script_error" . $suffix);
define("LOG_FILE_TIME", "script_time" . $suffix);

//脚本结束时的函数
function shutdown_handler()
{
    $err = error_get_last();
    do {
        if (empty($err)) {
            break;
        }

        $errno = $err['type'];
        $errstr = $err['message'];
        $errfile = $err['file'];
        $errline = $err['line'];

        //记录日志
        $level = (Com_Util::isDebug()) ? error_reporting() : (error_reporting() & ~E_DEPRECATED);//正式环境中忽略E_DEPRECATED错误
        if (($errno & $level) and in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
            $log = "$errstr ($errfile:$errline)\nStack trace:\n";
            $trace = debug_backtrace();
            // skip the first 3 stacks as they do not tell the error position
            if (count($trace) > 3) {
                $trace = array_slice($trace, 3);
            }
            foreach ($trace as $i => $t) {
                if (!isset($t['file'])) {
                    $t['file'] = 'unknown';
                }
                if (!isset($t['line'])) {
                    $t['line'] = 0;
                }
                if (!isset($t['function'])) {
                    $t['function'] = 'unknown';
                }
                $log .= "#$i {$t['file']}({$t['line']}): ";
                if (isset($t['object']) && is_object($t['object'])) {
                    $log .= get_class($t['object']) . '->';
                }
                $log .= "{$t['function']}()\n";
            }

            $clog = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
            $clog->setLog($log);
        }

    } while (false);
    Com_Benchmark::end();
}

register_shutdown_function("shutdown_handler");


?>
