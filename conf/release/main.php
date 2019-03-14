<?php

return [

    /* 项目域名 */
    "host" => "local.tp.com", //homeUrl勿删注释

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
    "cachePrefix" => "tp",

    /* 是否启用xhprof */
    "enableXhprof" => false,

    /* session */
    "session" => [
        "name" => "PHPSESSID",
        "save_handler" => "files",
        "save_path" => "",
        "cookie_domain" => ".tp.com", /* cookie主域 */
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

    /* 用于本地测试, 替换本地IP */
    "test_ip" => "",

    /* 需要记录日志的IP地址列表 */
    "time_log" => [
        "enable" => false,
        "ips" => [
            //需要记录的IP地址或IP段, 空为匹配全部IP, 内网IP也会全部匹配 x.x.x.x/32 x.x.x.0/24 x.x.0.0/16 x.0.0.0/8
            "0.0.0.0/0",//全部
        ],
    ],
];
