<?php

class IndexController extends Com_Abstract_Controller
{
    public $actions = [];

    public function indexAction()
    {
        $this->_view->assign('str', 'hello, world');
    }
}
