<?php

/**
 * 常用工具类
 * Class Util_Tool
 */
class Util_Tool
{

    const IDC_NORTH = 1;
    const IDC_SOUTH = 2;
    const SOUTH_IDC = []; //南方IDC开头

    public static function checkMobile($mobile)
    {
        $tel = preg_replace("/\s/", "", $mobile);
        if (empty($tel)) {
            return false;
        }
        $ret = preg_match('/^1[0-9]{10}$/', $tel);
        if ($ret) {
            return true;
        }
        return false;
    }

    /**
     * 操作用户的IP
     * @return array|false|string
     */
    public static function getIp()
    {
        $main = Com_Config::get();
        if (Com_Util::isDebug() and isset($main->test_ip) and !empty($main->test_ip)) {
            return $main->test_ip;
        }

        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
                $ip = getenv("HTTP_X_FORWARDED_FOR");
            } else {
                if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
                    $ip = getenv("REMOTE_ADDR");
                } else {
                    if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'],
                            "unknown")
                    ) {
                        $ip = $_SERVER['REMOTE_ADDR'];
                    } else {
                        $ip = "unknown";
                    }
                }
            }
        }
        return ($ip);
    }


    /**
     * cache的存储过程
     * @param $contextKey
     * @param callable $getCache
     * @param callable $setCache
     * @param callable $data
     * @param array|string $default
     * @return array|mixed|null|string
     *
     * @code
     * $gc_cb = function () use ($parentId) {
     *      $cache = new Cache_Area(self::$cahcePool);
     *      return $cache->getByParentId($parentId);
     *  };
     *
     * $sc_cb = function ($data) use ($parentId) {
     *      $cache = new Cache_Area(self::$cahcePool);
     *      $cache->setByParentId($parentId, $data);
     * };
     *
     * $gd_cb = function () use ($parentId) {
     *      $model = new AreaModel();
     *      return $model->getByParentId($parentId);
     * };
     * $key = "YP_AREA_getByParentId";
     * return Util_Tool::cacheProcess($key, $gc_cb, $sc_cb, $gd_cb);
     *
     */
    public static function cacheProcess(
        $contextKey,
        callable $getCache,
        callable $setCache,
        callable $data,
        $default = []
    ) {
        do {
            $ret = Com_Context::get($contextKey);
            if (!empty($ret)) {
                break;
            }

            $ret = $getCache();
            if (!empty($ret)) {
                Com_Context::set($contextKey, $ret);
                break;
            }

            $ret = $data();

            if (is_null($ret)) {
                return $default;
            }
            if (is_array($ret) and count($ret) == 0) {
                return (empty($default)) ? [] : $default;
            }
            if (is_string($ret) and strlen($ret) == 0) {
                return (empty($default)) ? '' : $default;
            }

            $setCache($ret);
            Com_Context::set($contextKey, $ret);

        } while (false);

        return $ret;
    }

    public static function cacheProcess2(
        $ckey,
        callable $gc1,
        callable $sc1,
        callable $gc2,
        callable $sc2,
        callable $data,
        $default = []
    ) {
        do {

            //程序缓存
            $ret = Com_Context::get($ckey);
            if (!empty($ret)) {
                break;
            }

            //1级本地缓存
            $ret = $gc1();
            if (!empty($ret)) {
                Com_Context::set($ckey, $ret);
                break;
            }

            //2级cache缓存
            $ret = $gc2();
            if (!empty($ret)) {
                Com_Context::set($ckey, $ret);
                $sc1($ret);
                break;
            }

            //从数据库取数据
            $ret = $data();

            if (is_null($ret)) {
                return $default;
            }
            if (is_array($ret) and count($ret) == 0) {
                return (empty($default)) ? [] : $default;
            }
            if (is_string($ret) and strlen($ret) == 0) {
                return (empty($default)) ? '' : $default;
            }

            $sc1($ret);
            $sc2($ret);
            Com_Context::set($ckey, $ret);

        } while (false);

        return $ret;
    }

    /**
     * 去零格式化
     * @param $n
     * @param $n_decimals
     * @return string
     */
    public static function number_format_drop_zero_decimals($n, $n_decimals)
    {
        return ((floor($n) == round($n, $n_decimals)) ? number_format($n) : number_format($n, $n_decimals));
    }


    public static function reset_global_var($name)
    {
        Yaf_Registry::set($name, []);
    }

    public static function add_global_var($name, $key, $value)
    {
        $list = Yaf_Registry::get($name);
        $list[$key] = $value;
        Yaf_Registry::set($name, $list);
    }

    /**
     * 获取全局错误变量
     * @return mixed
     */
    public static function get_global_var($name)
    {
        return Yaf_Registry::get($name);
    }

    public static function toArray($data)
    {
        return (empty($data)) ? [] : $data;
    }

    /**
     * 设置机房
     * @return string
     */
    public static function idc()
    {
        if (defined("IDC")) {
            if (IDC == self::IDC_SOUTH) {
                return 'south';
            }
        } else {
            if (isset($_SERVER) and isset($_SERVER['IDC'])) {
                foreach (self::SOUTH_IDC as $idc) {
                    if (mb_strpos($_SERVER['IDC'], $idc) === 0) {//匹配到南方
                        return 'south';
                    }
                }
            }
        }

        return 'north';
    }

}