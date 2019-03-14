<?php
/**
 * Created by PhpStorm.
 * User: jiaoyuan
 * Date: 2017/3/9
 * Time: 下午7:24
 */

class Com_Http_RequestPool
{
    public static $curl_state;
    public static $curl_pool;

    private static function get_curl_create($host_id) {
        $ch = curl_init();
        $curl_id = Com_Http_Request::fetch_curl_id($ch);
        self::$curl_state[$host_id][$curl_id] = false;
        self::$curl_pool[$curl_id] = $ch;
        return $ch;
    }

    private static function get_curl_from_pool($host_id) {
        foreach (self::$curl_state[$host_id] as $curl_id => $state) {
            if ($state) {
                self::$curl_state[$host_id][$curl_id] = false;
                return self::$curl_pool[$curl_id];
            }
        }

        return false;
    }


    public static function get_curl($host_id, $need_new = false) {
        if($need_new) {
            $ch = self::get_curl_create($host_id);
        }elseif (isset(self::$curl_state[$host_id])) {
            $ch = self::get_curl_from_pool($host_id);
            if ($ch === false) {
                $ch = self::get_curl_create($host_id);
            }
        } else {
            $ch = self::get_curl_create($host_id);
        }
        return $ch;
    }

    public static function reset_curl_state($host_id, $curl_id) {
        if (isset(self::$curl_state[$host_id][$curl_id])) {
            self::$curl_state[$host_id][$curl_id] = true;
        }
    }
}