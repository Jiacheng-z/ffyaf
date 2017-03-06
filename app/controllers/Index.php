<?php


/**
 * 用户提问相关
 * Class QaController
 */
class IndexController extends Com_Abstract_Controller
{
    public $actions = [
        "world" => "actions/Index/World.php",
    ];

    public function indexAction()
    {
        $a = Com_Context::getParam("param", "default");

        $c = new Cache_Test("Test");
        var_dump($c->test());

        $str = "Index Action";
        $this->_view->assign("str", $str);
    }

    public function helloAction()
    {
        $str = "Hello Action";
        $this->_view->assign("str", $str);
    }


}
