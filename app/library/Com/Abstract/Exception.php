<?php


/**
 * 项目异常基类
 * Class Com_Abstract_Exception
 */
abstract class Com_Abstract_Exception extends Yaf_Exception
{
    public function __construct($code = 0, $message = "", Exception $previous = null)
    {
        if (empty($message)) {
            if (!isset(Com_Exception_Descs::$errors[$code]) or empty(Com_Exception_Descs::$errors[$code])) {
                throw new Yaf_Exception("Exception has no " . $code . " massage in descs.");
            }
            $message = Com_Exception_Descs::$errors[$code];
        }
        parent::__construct($message, $code, $previous);
    }
}
