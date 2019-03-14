<?php


/**
 * 优品首页
 * Class IndexController
 */
class IndexController extends Com_Abstract_Controller
{
    public $actions = [
        'action_name' => 'actions/Index/Action.php', //action扩展
    ];

    public function indexAction()
    {
    }

    public function disableViewAction()
    {
        echo "Disable View";
        Yaf_Dispatcher::getInstance()->disableView();
    }

    public function setViewAction()
    {
        $this->_view->assign("message", "This message from assign.");
    }

    public function rewriteAction()
    {
        $this->_view->assign("message", "This is rewrite action.");
        $this->_view->display('index/message.tpl');
    }

    public function regexAction()
    {
        $this->_view->assign("message", "This is regex action. id = " . Com_Context::getParam("id"));
        $this->_view->display('index/message.tpl');
    }
}
