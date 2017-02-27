<?php


/**
 * 用户提问相关
 * Class QaController
 */
class IndexController extends Sys_Abstract_Controller
{
    public $actions = [
        "world" => "actions/Index/World.php",
    ];

    public function indexAction()
    {
        $str = "Index Action";
        $this->_view->assign("str", $str);
    }

    public function helloAction()
    {
        $str = "Hello Action";
        $this->_view->assign("str", $str);
    }

}
