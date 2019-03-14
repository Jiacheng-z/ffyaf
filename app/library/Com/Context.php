<?php

/**
 * Controller的上下文。用来存储一些在请求中共用的数据，以及提供GET/POST参数的简便封装。
 */
class Com_Context
{
    private static $isInit = false;

    /**
     * 保存自定上下文内容
     * @var array
     */
    private static $contextData = [];

    /**
     * @var $_SERVER
     */
    private static $server = [];

    /**
     * @var Yaf_Request_Abstract
     */
    private static $request = null;


    public static function init()
    {
        self::$isInit = true;
        self::$server = $_SERVER;
        self::$request = Yaf_Dispatcher::getInstance()->getRequest();
    }

    /**
     * 根据指定的上下文键名获取一个已经设置过的上下文键值
     * @param string|int|float $key 键名
     * @param null $ifNotExist
     * @return mixed|null
     */
    public static function get($key, $ifNotExist = null)
    {
        if (!isset(self::$contextData[$key])) {
            return $ifNotExist;
        }
        return self::$contextData[$key];
    }

    /**
     * 往一个指定的上下文键名中设置键值。
     * @param string|int|float $key
     * @param $value
     * @param $rewrite bool
     */
    public static function set($key, $value, $rewrite = true)
    {
        if (array_key_exists($key, self::$contextData) and $rewrite === false) {
            return;
        }

        self::$contextData[$key] = $value;
    }

    /**
     * 获取所有上下文
     * @return array
     */
    public static function getContext()
    {
        return self::$contextData;
    }

    public static function unsetKey($key)
    {
        self::$contextData[$key] = null;
        unset(self::$contextData[$key]);
    }

    /**
     * 清除context中的所有内容
     */
    public static function clear()
    {
        //为了防止引用计数产生的内存泄漏，此处显式的unset掉所有set进来的值
        foreach (self::$contextData as $key => $value) {
            self::$contextData[$key] = null;
            $value = null;
        }
        self::$contextData = [];
    }


    /**
     * @return Yaf_Request_Abstract
     */
    public static function getRequest()
    {
        return self::$request;
    }


    /**
     * 获取访问参数中的值 支持GET|POST方法
     * @param $key
     * @param null $ifNotExist
     * @return null|string
     */
    public static function getParam($key, $ifNotExist = null)
    {
        $value = self::$request->getParam($key);
        if (isset($value) AND $value !== '') {
            if (is_string($value)) {
                $value = trim($value);
            }
            return $value;
        }
        $value = self::$request->getQuery($key);
        if (isset($value) AND $value !== '') {
            if (is_string($value)) {
                $value = trim($value);
            }
            return $value;
        }
        $value = self::$request->getPost($key);
        if (isset($value) AND $value !== '') {
            if (is_string($value)) {
                $value = trim($value);
            }
            return $value;
        }

        return $ifNotExist;
    }

    public static function postParam($key, $ifNotExist = null)
    {
        $value = self::$request->getPost($key);
        if (isset($value) AND $value !== '') {
            if (is_string($value)) {
                $value = trim($value);
            }
            return $value;
        }

        return $ifNotExist;
    }

    /**
     * 获取全部参数
     * @return array
     */
    public static function getParams()
    {
        $params = self::$request->getParams() + self::$request->getQuery() + self::$request->getPost();
        ksort($params);
        foreach ($params as $k => $v) {
            if ($v === '') {
                unset($params[$k]);
                continue;
            }
            if (is_string($params[$k])) {
                $params[$k] = trim($v);
            }
        }
        return $params;
    }

    public static function postParams()
    {
        $params = self::$request->getPost();
        ksort($params);
        foreach ($params as $k => $v) {
            if ($v === '') {
                unset($params[$k]);
                continue;
            }
            if (!is_array($v)) {
                $params[$k] = trim($v);
            }
        }
        return $params;
    }


}

?>
