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
        try {
            throw new Exception_Cache(SYS_ERR_FAILED, "测试错误");
        } catch (Com_Abstract_Exception $e) {
            var_dump($e->getCode());
            var_dump($e->getMessage());
        }
        $str = "Index Action";
        $this->_view->assign("str", $str);
    }

    public function helloAction()
    {
        $str = "Hello Action";
        $this->_view->assign("str", $str);
    }

}
