<?php

class Com_Exception_Handler
{
    /**
     * Error错误码
     * @var array
     */
    private static $error = [
        0 => 'ERROR',
        1 => 'E_ERROR',
        2 => 'E_WARNING',
        4 => 'E_PARSE',
        8 => 'E_NOTICE',
        16 => 'E_CORE_ERROR',
        32 => 'E_CORE_WARNING',
        64 => 'E_COMPILE_ERROR',
        128 => 'E_COMPILE_WARNING',
        256 => 'E_USER_ERROR',
        512 => 'E_USER_WARNING',
        1024 => 'E_USER_NOTICE',
        2048 => 'E_STRICT',
        4096 => 'E_RECOVERABLE_ERROR',
        8192 => 'E_DEPRECATED',
        16384 => 'E_USER_DEPRECATED',
    ];

    public static function initHandler()
    {
        set_exception_handler(["Com_Exception_Handler", "handle_exception"]);
        set_error_handler(["Com_Exception_Handler", "handle_error"], E_ALL);
        register_shutdown_function(["Com_Exception_Handler", "handle_shutdown"]);
    }

    /**
     * 异常信息
     * @param Exception $exception
     * @return string
     */
    private static function exceptionContent($exception)
    {
        if (isset($exception->xdebug_message)) {
            $xdebugMessage = '<table class="xdebug-error xe-fatal-error" dir="ltr" border="1" cellspacing="0" cellpadding="1">
<tbody>';
            $xdebugMessage .= $exception->xdebug_message;
            $xdebugMessage .= '</tbody></table>';
            return $xdebugMessage;
        }

        $content = '<h1>' . get_class($exception) . "</h1>\n";
        $content .= '<p>' . $exception->getMessage() . ' (' . $exception->getFile() . ':' . $exception->getLine() . ')</p>';
        $content .= '<pre>' . $exception->getTraceAsString() . '</pre>';
        return $content;
    }

    /**
     * PHP 错误信息
     * @param $code
     * @param $message
     * @param $file
     * @param $line
     * @return string
     */
    private static function errorContent($code, $message, $file, $line)
    {
        $errMsg = "<h1>PHP " . self::$error[$code] . " [" . $code . "]</h1>\n";
        $errMsg .= "<p>$message ($file:$line)</p>\n";
        $errMsg .= '<pre>';

        $trace = debug_backtrace();
        // skip the first 3 stacks as they do not tell the error position
        if (count($trace) > 3) {
            $trace = array_slice($trace, 3);
        }
        foreach ($trace as $i => $t) {
            if (!isset($t['file'])) {
                $t['file'] = 'unknown';
            }
            if (!isset($t['line'])) {
                $t['line'] = 0;
            }
            if (!isset($t['function'])) {
                $t['function'] = 'unknown';
            }
            $errMsg .= "#$i {$t['file']}({$t['line']}): ";
            if (isset($t['object']) && is_object($t['object'])) {
                $errMsg .= get_class($t['object']) . '->';
            }
            $errMsg .= "{$t['function']}()\n";
        }

        $errMsg .= '</pre>';

        return $errMsg;
    }

    /**
     * 接受没有catch的异常|错误(中断性错误)
     * 之后的代码会不再被执行
     *
     * 展示:
     *  只展示异常或错误
     *
     * 记录:
     *  记录此次操作中的所有错误日志
     *
     * @param $ex Error | Exception
     *
     * void handler (Throwable $ex)
     */
    public static function handle_exception($ex)
    {
        //如果此函数内又有异常|致命错误发生, 让它走默认的异常处理策略
        restore_exception_handler();

        $content = '';
        if (Com_Util::isDebug()) {
            $content = ob_get_clean();
        }

        $isChild = $ex instanceof Com_Abstract_Exception;
        switch ($isChild) {
            case true: //项目异常 (输出给对方: 正常中断, 错误码等信息)

                self::displayException($ex);    //debug: smarty情况下打印异常|致命错误信息
                $arr = [
                    "code" => $ex->getCode(),
                    "desc" => $ex->getMessage(),
                ];
                self::_output($arr);//输出给对方

                break;

            default:    //yaf|其他异常|致命错误

                self::displayException($ex);    //debug: smarty情况下打印异常|致命错误信息
                if (is_a($ex, 'Yaf_Exception')) {
                    self::_output(['code' => 404], true);
                } else {
                    self::_output(['code' => 503], true);
                }

                $errno = $ex->getCode();
                $errstr = $ex->getMessage();
                $errfile = $ex->getFile();
                $errline = $ex->getLine();

                $log = $errno . ' | ' . ((isset(self::$error[$errno])) ? self::$error[$errno] : 'NULL') . ' | ' . $errstr;
                $log .= " | $errfile:$errline\nStack trace:\n";
                $log .= $ex->getTraceAsString() . "\n";
                if (isset($_SERVER['REQUEST_URI'])) {
                    $log .= 'REQUEST_URI=' . $_SERVER['REQUEST_URI'];
                }

                if (isset($_SERVER['HTTP_REFERER'])) {
                    $log .= "\nHTTP_REFERER=" . $_SERVER['HTTP_REFERER'];
                }
                $log .= "\n---";


                //记录LOG
                if ($ex instanceof Exception) {
                    //code 516 找不到对应的Controller
                    //code 517 找不到对应的action
                    if (!in_array($ex->getCode(), [516, 517])) {
                        $exc = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_EXC);
                        $exc->setLog($log);
                    }
                } else { //PHP7 Error
                    $exc = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_EXC);
                    $exc->setLog($log);
                }

                break;
        }

        //设置头(!危险!)
        if (Com_Util::isset_debug_code()) {
            header('Project-Exception-C:' . $ex->getCode());
            header('Project-Exception-D:' . get_class($ex) . ':' . $ex->getMessage());
            if (!empty(Com_Util::get_global_header_errors())) {
                header('Project-Error-json:' . json_encode(Com_Util::get_global_header_errors())); //输出error
                Com_Util::reset_global_header_errors();
            }
        }


        if (Com_Util::isDebug()) {
            Com_Util::print_global_errors();
            Com_Util::reset_global_errors();
            echo $content;
        }

        return;
    }

    /**
     * 错误的接受
     * 不会中断
     * 不能被用户处理 E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING
     *
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @return bool
     */
    public static function handle_error($errno, $errstr, $errfile, $errline)
    {
        //debug模式中, 同error_reporting()
        //error_reporting() === 0 使用了@错误控制符
        //线上模式, 同debug模式
        $in = (!Com_Util::isDebug()) ? ($errno & ERR_HANDLER_LEVEL_ONLINE) : ($errno & ERR_HANDLER_LEVEL_DEBUG);
        if (!$in or error_reporting() === 0) {
            return true;
        }

        //需要记录Error
        $log = $errno . ' | ' . ((isset(self::$error[$errno])) ? self::$error[$errno] : 'NULL') . ' | ' . $errstr;
        $log .= " | $errfile:$errline\nStack trace:\n";

        $trace = debug_backtrace();
        // skip the first 3 stacks as they do not tell the error position
        if (count($trace) > 3) {
            $trace = array_slice($trace, 3);
        }
        foreach ($trace as $i => $t) {
            if (!isset($t['file'])) {
                $t['file'] = 'unknown';
            }
            if (!isset($t['line'])) {
                $t['line'] = 0;
            }
            if (!isset($t['function'])) {
                $t['function'] = 'unknown';
            }
            $log .= "#$i {$t['file']}({$t['line']}): ";
            if (isset($t['object']) && is_object($t['object'])) {
                $log .= get_class($t['object']) . '->';
            }
            $log .= "{$t['function']}()\n";
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $log .= 'REQUEST_URI=' . $_SERVER['REQUEST_URI'];
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            $log .= "\nHTTP_REFERER=" . $_SERVER['HTTP_REFERER'];
        }
        $log .= "\n---";

        $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
        $err->setLog($log);

        //设置错误信息 等走到index.php中统一 不需要判断DEBUG: 会在index.php中判断是否要展示
        self::setErrorArr($errno, $errstr, $errfile, $errline);

        return true;
    }

    /**
     * php中止时执行的函数
     * 它会在脚本执行完成或者 exit() 后被调用
     * 主要用户记录Fatal Error
     *
     * 任务: Fatal error等错误会直接中断脚本, 用次函数记录日志, 并把之前设置的error级别的错误打印
     * 注意: error_get_last()可以获取到被屏蔽的错误
     *  例如:
     *      ini_set("error_reporting", E_ALL & ~E_DEPRECATED);
     *      E_DEPRECATED 依然能被捕捉到
     */
    public static function handle_shutdown()
    {
        restore_error_handler();
        restore_exception_handler();

        $content = '';
        if (Com_Util::isDebug()) {
            $content = ob_get_clean();
        }

        $err = error_get_last();
        do {
            if (empty($err)) {
                break;
            }

            $errno = $err['type'];
            $errstr = $err['message'];
            $errfile = $err['file'];
            $errline = $err['line'];

            //记录日志
            $level = (Com_Util::isDebug()) ? error_reporting() : (error_reporting() & ~E_DEPRECATED);//正式环境中忽略E_DEPRECATED错误
            if (($errno & $level) and in_array($errno,
                    [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])
            ) {
                $log = "$errstr ($errfile:$errline)\nStack trace:\n";
                $trace = debug_backtrace();
                // skip the first 3 stacks as they do not tell the error position
                if (count($trace) > 3) {
                    $trace = array_slice($trace, 3);
                }
                foreach ($trace as $i => $t) {
                    if (!isset($t['file'])) {
                        $t['file'] = 'unknown';
                    }
                    if (!isset($t['line'])) {
                        $t['line'] = 0;
                    }
                    if (!isset($t['function'])) {
                        $t['function'] = 'unknown';
                    }
                    $log .= "#$i {$t['file']}({$t['line']}): ";
                    if (isset($t['object']) && is_object($t['object'])) {
                        $log .= get_class($t['object']) . '->';
                    }
                    $log .= "{$t['function']}()\n";
                }
                if (isset($_SERVER['REQUEST_URI'])) {
                    $log .= 'REQUEST_URI=' . $_SERVER['REQUEST_URI'];
                }
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $log .= "\nHTTP_REFERER=" . $_SERVER['HTTP_REFERER'];
                }
                $log .= "\n---";

                $clog = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
                $clog->setLog($log);

                self::setErrorArr($errno, $errstr, $errfile, $errline);

            }

        } while (false);

        Com_Benchmark::end();

        if (Com_Util::isset_debug_code() and !empty(Com_Util::get_global_header_errors())) {
            header('Project-Error-json:' . json_encode(Com_Util::get_global_header_errors())); //输出error
            Com_Util::reset_global_header_errors();
        }

        if (Com_Util::isDebug()) {
            Com_Util::print_global_errors();
            Com_Util::reset_global_errors();
            echo $content;
        }
    }


    /**
     * Displays the uncaught PHP exception.
     * This method displays the exception in HTML when there is
     * no active error handler.
     * @param Exception $exception the uncaught exception
     */
    private static function displayException($exception)
    {
        $viewType = Yaf_Registry::get("viewType");
        if (Com_Util::isDebug() and $viewType == "smarty") {//TODO::不存在错误模板的情况下直接输出, 如果有模板, 走模板输出
            echo self::exceptionContent($exception);
        }
    }

    /**
     * 设置错误信息至error数组
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     */
    private static function setErrorArr($errno, $errstr, $errfile, $errline)
    {
        $errMsg = self::errorContent($errno, $errstr, $errfile, $errline);
        Com_Util::add_global_errors($errMsg);

        $log = $errno . ' | ' . ((isset(self::$error[$errno])) ? self::$error[$errno] : 'NULL') . ' | ' . $errstr;
        Com_Util::add_global_header_errors($log);
    }


    /**
     * 输出错误的入口
     * @param array $data
     * @param bool $sysException 系统异常?
     */
    private static function _output($data, $sysException = false)
    {
        $viewType = Yaf_Registry::get("viewType");
        switch ($viewType) {
            case 'smarty':
                self::_output_smarty($data, $sysException);
                break;
            case 'callback':
                self::_output_callback($data, $sysException);
                break;
            case 'json':
                self::_output_json($data, $sysException);
                break;
            default:
                break;
        }
    }

    private static function _output_smarty($data, $sysException = false)
    {
        switch ($data['code']) {
            case SYS_ERR_FAILED:
            case SYS_ERR_FORBIDDEN:
                header('HTTP/1.1 403 Forbidden');
                header('Project-Output: true');
                break;
            case SYS_REDIRECT_PERMANENTLY:
                header('HTTP/1.1 301 Moved Permanently');
                header('Project-Output: true');

                if (isset($data['desc'])) {
                    header('Location: ' . $data['desc']);
                } else {
                    header('Location: /');
                }
                break;
            case SYS_REDIRECT:
                header('Project-Output: true');

                if (isset($data['desc'])) {
                    header('Location: ' . $data['desc']);
                } else {
                    header('Location: /');
                }
                break;
            case SYS_ERR_SERVER:
                header('HTTP/1.1 503 Service Temporarily Unavailable');
                header('Status: 503 Service Temporarily Unavailable');
                header('Project-Output: true');

                $tpl = Com_Config::get()->exception_tpl->err_500;
                if (isset($tpl) and file_exists($tpl)) {
                    $smarty = Yaf_Registry::get('view');
                    $smarty->display($tpl);
                }
                break;
            case SYS_NOT_FOUND:
            default://项目异常中断
                header('HTTP/1.1 404 Not Found');
                header("status: 404 Not Found");
                header('Project-Output: true');

                $tpl = Com_Config::get()->exception_tpl->err_404;
                if (isset($tpl) and file_exists($tpl)) {
                    $smarty = Yaf_Registry::get('view');
                    $smarty->display($tpl);
                }
                break;
        }
    }

    private static function _output_callback($data, $sysException = false)
    {
        $view = Yaf_Registry::get('view');
        $callback = Yaf_Registry::get('callback');
        $view->assign('callback', $callback);
        $view->assign('code', $data['code']);

        if (isset($data['desc'])) {
            $view->assign('desc', $data['desc']);
        } elseif ($sysException == true) {
            $view->assign('desc', 'failed');
        }

        if (isset($data['content'])) {
            $view->assign('content', $data['content']);
        }
        $view->display(null);
    }

    private static function _output_json($data, $sysException = false)
    {
        $view = Yaf_Registry::get('view');
        $view->assign('code', $data['code']);

        if (isset($data['desc'])) {
            $view->assign('desc', $data['desc']);
        } elseif ($sysException == true) {
            $view->assign('desc', 'failed');
        }

        if (isset($data['content'])) {
            $view->assign('content', $data['content']);
        }
        $view->display(null);
    }

}
