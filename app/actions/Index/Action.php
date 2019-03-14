<?php

class Action_nameAction extends Com_Abstract_Action
{
    public function execute()
    {
        parent::execute();
        $this->_view->assign('message', "This is from actions/Index/Action.php");
        $this->_view->display('index/message.tpl');
    }

}