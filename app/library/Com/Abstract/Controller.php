<?php


abstract class Com_Abstract_Controller extends Yaf_Controller_Abstract
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
        $request = parent::getRequest();
        $this->controller = $request->getControllerName();
        $this->action = $request->getActionName();
        $this->method = $request->getMethod();


        /* 攻击检测 */
        Com_Attack::checkCSRF();
    }

    /**
     * 屏蔽父类getRequest方法
     * 用户请使用Com_Context来取Request
     */
    public function getRequest()
    {
    }

}
