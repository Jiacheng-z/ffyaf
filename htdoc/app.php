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

/* DEBUG模式将错误输出在前面 */
if (YAF_DEBUG == true) {
    ob_start();
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

/* 初始化全局errors */
Com_Util::reset_global_errors();
Com_Util::reset_global_header_errors();

$response = $application
    ->bootstrap()/*bootstrap是可选的调用*/
    ->run();


/* 优先输出错误 再输出正文内容 */
if (Com_Util::isDebug()) {
    $content = ob_get_clean();
    Com_Util::print_global_errors();
    Com_Util::reset_global_errors();
    echo $content;
}

/* 头部输出debug信息 */
if (Com_Util::isset_debug_code() and !empty(Com_Util::get_global_header_errors())) {
    header('Project-Error-json:' . json_encode(Com_Util::get_global_header_errors()));
    Com_Util::reset_global_header_errors();
}


?>
