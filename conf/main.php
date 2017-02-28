<?php

/*return array(

    'homeUrl' => 'www.testmiaoche.com',
    'cookieDomain' => '.testmiaoche.com',
    'yaf' => array(
        'directory' => APPLICATION_PATH,
        'dispatcher' => array(
            'catchException' => 0,
        ),
    ),
    'urlRewrite' => true,
    'enableCache' => true,
    'enableXhprof' => false,
    'cache_prefix' => 'debug_',
    'runtimePath' => __DIR__ . '/../tmp/_runtime',
    'out_trade_no' => array(
        'prefix' => 't_'
    ),
    'cache' => array(
        'miaoche' => array(
            'host' => 'localhost',
            'port' => '11211',
        ),
    ),
    'redis' => array(
        'miaoche' => array(
            'host' => 'localhost', //redis
            'port' => '6379', //redis
            'auth' => 'RnYMeKNtOWfVfKM4oQBkf4iaWopQg5',
        ),
    ),
);*/

return [

    /* 项目域名 */
    "host" => "yaf.my",

    /* yaf框架配置 */
    "yaf" => [
        "directory" => APPLICATION_PATH,
        "dispatcher" => [
            "catchException" => 0,
        ],
    ],

    /* url映射 */
    "urlRewrite" => true,

    /* 是否启用缓存 */
    "enableCache" => true,

    /* 是否启用xhprof */
    "enableXhprof" => false,

    /* 缓存 */
    "cache" => [
        "prefix" => "cache_prefix", /* 项目缓存前缀 */
        "cluster" => [ /* 多主机配置(可换成代理) */
            "c_1" => [
                "host" => "localhost",
                "port" => "11211",
            ],
        ],
    ],

    /* session */
    "session" => [
        "name" => "PHPSESSID",
        "save_handler" => "files",
//        "save_handler" => "redis",
        "save_path" => "",
//        "save_path" => "tcp://localhost:6379?auth=RnYMeKNtOWfVfKM4oQBkf4iaWopQg5&prefix=SESSION:WWW:&timeout=1",
        "cookie_domain" => ".test.com", /* cookie主域 */

        "gc_maxlifetime" => 1440,
        "gc_probability" => 1,
    ],

    /* 日志路径 */
    "runtimePath" => APPLICATION_PATH . "../tmp/_runtime/",

    /* 异常模板路径 */
    "exception_tpl" => [
        "err" => APPLICATION_PATH . "help/err.html",
        "err_404" => APPLICATION_PATH . "help/err404.html",
        "err_500" => APPLICATION_PATH . "help/err500.html",
    ],
];
