<?php


class Com_Tool
{
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
                    if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
                        $ip = $_SERVER['REMOTE_ADDR'];
                    } else {
                        $ip = "127.0.0.1";
                    }
                }
            }
        }
        return ($ip);
    }

    static public function redirect($uri = '', $method = 'location', $http_response_code = 302)
    {
        switch ($method) {
            case 'refresh'    :
                header("Refresh:0;url=" . $uri);
                break;
            default            :
                header("Location: " . $uri, true, $http_response_code);
                break;
        }
        exit;
    }

}