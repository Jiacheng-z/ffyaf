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
     * @throws Exception_Program
     */
    public static function get($key, $ifNotExist = null)
    {
        if (!isset(self::$contextData[$key])) {
            return $ifNotExist;
        }
        return self::$contextData[$key];
    }

    /**
     * 往一个指定的上下文键名中设置键值。如果该键值已经被设置，则会抛出异常。
     * @param string|int|float $key
     * @param $value
     * @throws Exception_Program
     */
    public static function set($key, $value)
    {
        if (array_key_exists($key, self::$contextData)) {
            throw new Exception_Program(CONTEXT_ERR_PARAMS, 'context has been already setted');
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
            return trim($value);
        }
        $value = self::$request->getQuery($key);
        if (isset($value) AND $value !== '') {
            return trim($value);
        }
        $value = self::$request->getPost($key);
        if (isset($value) AND $value !== '') {
            return trim($value);
        }

        return $ifNotExist;
    }

    public static function postParam($key, $ifNotExist = null)
    {
        $value = self::$request->getPost($key);
        if (isset($value) AND $value !== '') {
            return trim($value);
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
            $params[$k] = trim($v);
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
            $params[$k] = trim($v);
        }
        return $params;
    }


}

?>
