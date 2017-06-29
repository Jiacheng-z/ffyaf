<?php

/**
 * 攻击检测类
 *
 * 检测各种攻击
 * Class Com_Attack
 */
class Com_Attack
{
    /**
     * 检测是否存在CSRF攻击
     */
    public static function checkCSRF()
    {
        $request = Yaf_Dispatcher::getInstance()->getRequest();
        $arr = explode('_', $request->getControllerName(), 2);

        if ($request->isPost() and static::checkReferer() == false) {
            throw new Exception_Forbidden(SYS_ERR_FORBIDDEN);
        }

        switch ($arr[0]) {
            case "Aj":
                if (static::checkReferer() == false) {
                    throw new Exception_Forbidden(SYS_ERR_FORBIDDEN);
                }
                break;
            case "Api":
                break;
            default:
                break;
        }
    }

    /**
     * 检测referer是否合法
     * @return bool
     */
    public static function checkReferer()
    {
        if (!isset($_SERVER['HTTP_REFERER']) or empty($_SERVER['HTTP_REFERER'])) {
            return false;
        }
        $refer = parse_url($_SERVER['HTTP_REFERER']);
        $host = Com_Config::get()->host;

        if ($refer['host'] != $host and $refer['host'] != $_SERVER['HTTP_HOST']) {
            return false;
        }

        return true;
    }


}