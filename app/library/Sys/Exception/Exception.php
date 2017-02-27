<?php

class Sys_Exception extends Exception
{
    protected $content;

    public function __construct($errcode, $errmsg = null, $content = null)
    {
        $this->content = $content;
        if (empty($errmsg)) {
            $errmsg = Sys_Exception_Descs::$errors[$errcode];
        }
        parent::__construct($errmsg, $errcode);
    }

    public function getContent()
    {
        return $this->content;
    }
}
