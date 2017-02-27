<?php

class Sys_Abstract_View implements Yaf_View_Interface
{
    public $path;
    public $values;

    public function assign($name, $value = null)
    {
        $this->values[$name] = $value;
    }

    public function display($tpl, $var_array = array())
    {
        echo $this->render($tpl);
    }

    public function render($tpl, $var_array = array())
    {

    }

    public function setScriptPath($tpl_dir)
    {
        $this->path = $tpl_dir;
    }


    public function getScriptPath()
    {
        return $this->path;
    }
}
