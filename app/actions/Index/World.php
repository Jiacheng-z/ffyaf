<?php

class WorldAction extends Com_Abstract_Action
{
    function execute()
    {
        $str = "World Action";
        $this->_view->assign("str", $str);
    }
}
