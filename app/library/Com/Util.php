<?php

class Com_Util
{
    /**
     * 是否是debug模式
     */
    static public function isDebug()
    {
        return YAF_DEBUG;
    }

    /**
     * 判断php宿主环境是否是64bit
     *
     * ps: 在64bit下，php有诸多行为与32bit不一致，诸如mod、integer、json_encode/decode等，具体请自行google。
     *
     * @return bool
     */
    public static function is_64bit()
    {
        return PHP_INT_SIZE == 8;
    }

    /**
     * 修正过的ip2long
     *
     * 可去除ip地址中的前导0。32位php兼容，若超出127.255.255.255，则会返回一个float
     *
     * for example: 02.168.010.010 => 2.168.10.10
     *
     * 处理方法有很多种，目前先采用这种分段取绝对值取整的方法吧……
     * @param string $ip
     * @return float 使用unsigned int表示的ip。如果ip地址转换失败，则会返回0
     */
    public static function ip2long($ip)
    {
        $ip_chunks = explode('.', $ip, 4);
        foreach ($ip_chunks as $i => $v) {
            $ip_chunks[$i] = abs(intval($v));
        }
        return sprintf('%u', ip2long(implode('.', $ip_chunks)));
    }

    /**
     * 判断是否是内网ip
     * @param string $ip
     * @return boolean
     */
    public static function is_private_ip($ip)
    {
        $ip_value = self::ip2long($ip);
        return ($ip_value & 0xFF000000) === 0x0A000000 //10.0.0.0-10.255.255.255
            || ($ip_value & 0xFFF00000) === 0xAC100000 //172.16.0.0-172.31.255.255
            || ($ip_value & 0xFFFF0000) === 0xC0A80000 //192.168.0.0-192.168.255.255
            ;
    }

    /**
     * IP地址与地址段匹配
     * @param $ip
     * @param $range
     * @return bool
     */
    public static function cidr_match($ip, $range)
    {
        list ($subnet, $bits) = explode('/', $range);
        $ip = self::ip2long($ip);
        $subnet = self::ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) == $subnet;
    }

    /**
     * 使json_decode能处理32bit机器上溢出的数值类型
     *
     * @param $value
     * @param bool $assoc
     * @return mixed
     */
    public static function json_decode($value, $assoc = true)
    {
        //PHP5.3以下版本不支持
        if (version_compare(PHP_VERSION, '5.3.0', '>') && defined('JSON_BIGINT_AS_STRING')) {
            return json_decode($value, $assoc, 512, JSON_BIGINT_AS_STRING);
        } else {
            $value = preg_replace("/\"(\w+)\":(\d+[\.\d+[e\+\d+]*]*)/", "\"\$1\":\"\$2\"", $value);
            return json_decode($value, $assoc);
        }
    }

    public static function array_diff($aArray1, $aArray2)
    {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = self::array_diff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
        return $aReturn;
    }

    /**
     * 数组内某个键值非空
     * @param array $arr
     * @param $key
     * @return bool
     */
    public static function isset_key_nonempty(array $arr, $key)
    {
        return (!empty($arr) and isset($arr[$key]) and !empty($arr[$key]));
    }

    /**
     * 检测是否有debug码
     * @return bool
     */
    public static function isset_debug_code()
    {
        return (isset($_GET['debug']) and $_GET['debug'] == DEBUG_GET_PW);
    }

    /**
     * 初始化|重置全局错误变量
     */
    public static function reset_global_errors()
    {
        Yaf_Registry::set('show_errors', []);
    }

    /**
     * 设置全局错误变量
     * @param $v
     */
    public static function add_global_errors($v)
    {
        $list = Yaf_Registry::get('show_errors');
        $list[] = $v;
        Yaf_Registry::set('show_errors', $list);
    }

    /**
     * 获取全局错误变量
     * @return mixed
     */
    public static function get_global_errors()
    {
        return Yaf_Registry::get('show_errors');
    }

    /**
     * 输出全局错误
     */
    public static function print_global_errors()
    {
        $list = Yaf_Registry::get('show_errors');
        foreach ($list as $item) {
            echo $item;
        }
    }

    /**
     * 初始化|重置全局头信息错误变量
     */
    public static function reset_global_header_errors()
    {
        Yaf_Registry::set('header_errors', []);
    }

    /**
     * 设置全局头信息错误变量
     * @param $v
     */
    public static function add_global_header_errors($v)
    {
        $list = Yaf_Registry::get('header_errors');
        $list[] = $v;
        Yaf_Registry::set('header_errors', $list);
    }

    /**
     * 获取全局头信息错误变量
     * @return mixed
     */
    public static function get_global_header_errors()
    {
        return Yaf_Registry::get('header_errors');
    }


    public static function add_clean_func(callable $func, array $param_arr = [])
    {
        $list = Yaf_Registry::get('yaf_business_clean_func');
        if (empty($list)) {
            $list = [];
        }
        $list[] = ['func' => $func, 'param' => $param_arr];
        Yaf_Registry::set('yaf_business_clean_func', $list);
    }

    public static function execute_clean_func()
    {
        $list = Yaf_Registry::get('yaf_business_clean_func');
        foreach ((array)$list as $funcArr) {
            call_user_func_array($funcArr['func'], $funcArr['param']);
        }
    }

    static public function sortByKey($sortArr, $key, $asc = 'asc')
    {
        usort($sortArr, function ($a, $b) use ($key, $asc) {
            if ($a[$key] == $b[$key]) {
                return 0;
            }
            if ($asc == 'asc') {
                return ($a[$key] < $b[$key]) ? -1 : 1;
            } else {
                return ($a[$key] > $b[$key]) ? -1 : 1;
            }
        });

        return $sortArr;
    }

}
