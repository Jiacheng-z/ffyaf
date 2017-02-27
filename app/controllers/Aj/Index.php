<?php

class Aj_IndexController extends Sys_Abstract_Controller
{
    public function addAction()
    {
        $this->_view->assign("content", "Index Ajax!");
    }
}
