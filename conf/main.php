<?php

return [

    /* 项目域名 */
    "host" => "www.example.com",

    /* yaf框架配置 */
    "yaf" => [
        "directory" => APPLICATION_PATH,
        "dispatcher" => [
            "catchException" => 0,
        ],
    ],

    /* 是否启用Debug */
    "enableDebug" => true,

    /* url映射 */
    "urlRewrite" => true,

    /* 是否启用缓存 */
    "enableCache" => true,

    /* 缓存项目前缀 */
    "cachePrefix" => "example",

    /* 是否启用xhprof */
    "enableXhprof" => false,

    /* session */
    "session" => [
        "name" => "PHPSESSID",
        "save_handler" => "files",
        "save_path" => "",
        "cookie_domain" => ".example.com", /* cookie主域 */
        "gc_maxlifetime" => 1440,
        "gc_probability" => 1,
    ],

    /* 日志路径 */
    "runtimePath" => APPLICATION_PATH . "../tmp/_runtime/",

    /* 异常模板路径 */
    "exception_tpl" => [
        "err" => APPLICATION_PATH . "views/help/err.html",
        "err_404" => APPLICATION_PATH . "views/help/err404.html",
        "err_500" => APPLICATION_PATH . "views/help/err500.html",
    ],

    /* 当前IP设置 - 用于测试IP定位, 获取等 */
    "test_ip" => "47.153.128.17",
];
