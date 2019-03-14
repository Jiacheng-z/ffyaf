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

            if (isset(Com_Exception_Descs::$errors[$code]) and !empty(Com_Exception_Descs::$errors[$code])) {
                $message = Com_Exception_Descs::$errors[$code];
            }

            if (isset(Descs::$constValue[$code]) and !empty(Descs::$constValue[$code])) {
                $message = Descs::$constValue[$code];
            }

            if (empty($message)) {
                throw new Yaf_Exception("Exception has no " . $code . " massage in descs.");
            }
        }
        parent::__construct($message, $code, $previous);
    }
}
