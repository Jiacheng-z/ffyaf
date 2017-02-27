<?php

class Sys_View_Callback extends Sys_View_Json
{
    public function render($name, $value = null)
    {
        $ret = '';
        if (isset($this->values["callback"])) {
            $ret = $this->values["callback"] . '(';
        }
        $ret .= parent::render($name);
        if (isset($this->values["callback"])) {
            $ret .= ')';
        }
        return $ret;
    }
}

?>
