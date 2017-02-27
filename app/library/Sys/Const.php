<?php
/**
 * 用于异常终端时的错误码
 */

define("SYS_SUCCESS", 0);    /* 正常 */

/* ------------------ START 固定错误码 ---------------------- */

define("SYS_FAILED", 1);    /* 失败 */

define("SYS_FORBIDDEN", 2);  /* 无权限 */

define("SYS_METHOD_INVALID", 3); /* 访问方法错误 */
define("SYS_PARAMS_INVALID", 4); /* 访问参数错误 */

define("SYS_REDIRECT_PERMANENTLY", 301); /* 永久重定向 */
define("SYS_REDIRECT", 302); /* 临时重定向 */

define("SYS_NOT_FOUND", 404); /* 无页面 */

define("SYS_SERVER_ERROR", 500); /* 服务内部错误 */

/* ------------------ END   固定错误码 ---------------------- */

/* ------------------ START 用户自定义 ---------------------- */

define("CUST_ERROR_PASSWORD", 1000);