<?php

class Api_IndexController extends Com_Abstract_Controller
{
    public function indexAction()
    {
        $this->_view->assign("content", "This is Api_Index result.");
    }
}