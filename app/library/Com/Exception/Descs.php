<?php

class Com_Exception_Descs
{
    public static $errors = [
        SYS_SUCCESS => "成功",
        SYS_ERR_FAILED => "失败",

        SYS_ERR_FORBIDDEN => "访问被拒绝",

        SYS_ERR_METHOD_INVALID => "非法请求",
        SYS_ERR_PARAMS_INVALID => "参数错误",

        SYS_REDIRECT_PERMANENTLY => "永久重定向",
        SYS_REDIRECT => "重定向",

        SYS_NOT_FOUND => "未找到页面",
        SYS_ERR_SERVER => "暂时无法服务，请稍后再试",
    ];
}
