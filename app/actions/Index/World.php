<?php

class WorldAction extends Sys_Abstract_Action
{
    function execute()
    {
        $str = "World Action";
        $this->_view->assign("str", $str);
    }
}
