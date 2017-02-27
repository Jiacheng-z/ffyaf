<?php


class Sys_Abstract_Controller extends Yaf_Controller_Abstract
{

    /**
     * @var string 当前控制器
     */
    protected $controller;

    /**
     * @var string 当前action
     */
    protected $action;

    /**
     * @var string 访问方式
     */
    protected $method; // GET | POST

    /**
     * @var Yaf_View_Interface
     */
    //protected $_view;

    protected function init()
    {
        $request = $this->getRequest();
        $this->controller = $request->getControllerName();
        $this->action = $request->getActionName();
        $this->method = $request->getMethod();

        /* 攻击检测 */
        Sys_Attack::checkCSRF();
    }

    public function getParam($name, $default = null)
    {
        $request = Yaf_Dispatcher::getInstance()->getRequest();
        $value = $request->getParam($name);
        if (isset($value) AND $value !== '') {
            return trim($value);
        }
        $value = $request->getQuery($name);
        if (isset($value) AND $value !== '') {
            return trim($value);
        }
        $value = $request->getPost($name);
        if (isset($value) AND $value !== '') {
            return trim($value);
        }

        return $default;
    }

    public function getParams()
    {
        $request = Yaf_Dispatcher::getInstance()->getRequest();
        $params = $request->getParams() + $request->getQuery() + $request->getPost();
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
