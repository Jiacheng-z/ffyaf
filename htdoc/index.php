<?php


define("BASE_PATH", dirname(__FILE__) . "/../");
define("APPLICATION_PATH", BASE_PATH . "app/");
define("CONFIG_PATH", BASE_PATH . "conf/");

/* 检测是否是DEBUG模式 */
define("DEBUG_GET_PW", "debug_code");
if (file_exists(CONFIG_PATH . "debug")) {
    define("YAF_DEBUG", true);
    ini_set('display_errors', 1);
    error_reporting(32767);
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
$benchmark = new Com_Benchmark();

$response = $application
    ->bootstrap()/*bootstrap是可选的调用*/
    ->run();

/* DEBUG输出 */
$errors = Yaf_Registry::get('error');
if (YAF_DEBUG == true) {

    $content = ob_get_contents();
    ob_end_clean();
    if (isset($errors)) {
        foreach ($errors as $e) {
            echo $e;
        }
    }
    echo $content;

} else {

    if (isset($errors)) {
        if (isset($_GET['debug']) and $_GET['debug'] == DEBUG_GET_PW) {
            header('project-error:' . json_encode($errors));
        }
    }
}


?>
