<?php
/**
 *
 * 提供断言类
 *
 * @since v0.2.1
 * @copyright weibo.com php team, 2011.
 * @author Rodin <luodan@staff.sina.com.cn>
 */

/**
 *
 * 断言
 *
 * 断言用于验证程序中不可能出现的情况。可以通过 Comm_Assert::as_* 系列方法调整断言的行为。
 * 默认行为为不输出任何内容。
 *
 */
class Com_Assert
{
    static protected $assert_type = 0;

    /**
     * 设置assert行为为不输出任何内容
     */
    static public function as_dumb()
    {
        self::$assert_type = 0;
    }

    /**
     *
     * 设置assert行为为触发warning
     */
    static public function as_warning()
    {
        self::$assert_type = 1;
    }

    /**
     * 设置assert行为为抛出exception
     */
    static public function as_exception()
    {
        self::$assert_type = 3;
    }

    /**
     *
     * 设置assert行为为触发error
     */
    static public function as_error()
    {
        self::$assert_type = 2;
    }

    /**
     * 验证条件是否为成立，如果不成立，则提示指定的message
     *
     * @param bool $condition
     * @param string $message
     */
    static public function true($condition, $message = null)
    {
        if (!$condition) {
            self::act($message);
        }
    }

    /**
     * 验证条件是否不成立，如果为成立，则提示指定的message
     * @param bool $condition
     * @param string $message
     */
    static public function false($condition, $message = null)
    {
        if ($condition) {
            self::act($message);
        }
    }

    /**
     *
     * @param string $message
     * @throws Exception_Program
     */
    static protected function act($message)
    {
        switch (self::$assert_type) {
            case 2:
                trigger_error($message, E_USER_ERROR);
                break;
            case 3:
                throw new Exception_Program($message);
                break;
            case 1:
                trigger_error($message, E_USER_WARNING);
                break;
            default:
        }
    }
}