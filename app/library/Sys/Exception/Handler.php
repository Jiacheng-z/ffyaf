<?php

class Sys_Exception_Handler
{
    public static $error = array(
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
    );

    public static function initHandler()
    {
        if (YAF_ENABLE_EXCEPTION_HANDLER) {
            set_exception_handler(array('Sys_Exception_Handler', 'handleException'));
        }
        if (YAF_ENABLE_ERROR_HANDLER) {
            set_error_handler(array('Sys_Exception_Handler', 'handleError'), error_reporting());
            register_shutdown_function(array('Sys_Exception_Handler', 'handleFatalError'));
        }
    }

    /**
     * @param $e Sys_Exception
     */
    public static function handleException($e)
    {
        // disable error capturing to avoid recursive errors
        switch (get_class($e)) {

            case "Sys_Exception": // 项目异常类
                restore_error_handler();
                restore_exception_handler();

                $intErrno = $e->getCode();
                $strErrmsg = $e->getMessage();
                $strErrContent = $e->getContent();

                $arrRes = array(
                    'code' => $intErrno,
                    'desc' => $strErrmsg,
                );
                if ($strErrContent !== null) {
                    $arrRes['content'] = $strErrContent;
                }
                $ret = self::_output($arrRes);
                break;
            default:
                self::displayException($e);
                if (is_a($e, 'Yaf_Exception')) {
                    $ret = self::_output(array('code' => 404));
                } else {
                    $ret = self::_output(array('code' => 503));
                }
                $message = $e->__toString();
                if (isset($_SERVER['REQUEST_URI'])) {
                    $message .= "\nREQUEST_URI=" . $_SERVER['REQUEST_URI'];
                }
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $message .= "\nHTTP_REFERER=" . $_SERVER['HTTP_REFERER'];
                }
                $message .= "\n---";

                //code 516 找不到对应的Controller
                //code 517 找不到对应的action
                if (!in_array($e->getCode(), [516, 517])) {
                    $config = Yaf_Registry::get('config');
                    $logger = new Log($config->runtimePath, 'exception.log');
                    $logger->setLog($message);
                }

                break;
        }
        return;
    }

    public static function handleFatalError()
    {
        $e = error_get_last();
        if (empty($e)) {
            exit;
        }
        if (isset($e['code'])) {
            $code = $e['code'];
        } else {
            $code = null;
        }
        $message = $e['message'];
        $file = $e['file'];
        $line = $e['line'];

        restore_error_handler();
        restore_exception_handler();
        $log = "$message ($file:$line)\nStack trace:\n";
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
        $config = Yaf_Application::app()->getConfig();
        $logger = new Log($config->runtimePath, 'error.log');
        $logger->setLog($message);
//        self::displayFatalError($code, $message, $file, $line);
    }

    public static function handleError($code, $message, $file, $line)
    {
        if ($code & error_reporting()) {
            // disable error capturing to avoid recursive errors
//            restore_error_handler();
//            restore_exception_handler();

            $log = "$message ($file:$line)\nStack trace:\n";
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
            $config = Yaf_Application::app()->getConfig();
            $logger = new Log($config->runtimePath, 'error.log');
            $logger->setLog($log);
            self::displayError($code, $message, $file, $line);
        }
    }

    public static function displayFatalError($code, $message, $file, $line)
    {
        self::displayError($code, $message, $file, $line);
        $errors = Yaf_Registry::get('error');
        foreach ($errors as $e) {
            echo $e;
        }
    }

    public static function displayError($code, $message, $file, $line)
    {
//        if (!(defined('YAF_DEBUG') && YAF_DEBUG)) {
//            return;
//        }
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
        $error = Yaf_Registry::get('error');
        $error[] = $errMsg;
        Yaf_Registry::set('error', $error);
    }

    /**
     * Displays the uncaught PHP exception.
     * This method displays the exception in HTML when there is
     * no active error handler.
     * @param Exception $exception the uncaught exception
     */
    public static function displayException($exception)
    {
        if (defined('YAF_DEBUG') && YAF_DEBUG) {
            echo '<h1>' . get_class($exception) . "</h1>\n";
            echo '<p>' . $exception->getMessage() . ' (' . $exception->getFile() . ':' . $exception->getLine() . ')</p>';
            echo '<pre>' . $exception->getTraceAsString() . '</pre>';
        }
    }

    private static function _output($data)
    {
        $viewType = Yaf_Registry::get("viewType");
        switch ($viewType) {
            case 'smarty':
                self::_output_smarty($data);
                break;
            case 'callback':
                self::_output_callback($data);
                break;
            case 'json':
                self::_output_json($data);
                break;
            default:
                break;
        }
    }

    private static function _output_smarty($data)
    {
        switch ($data['code']) {
            case '2':
            case '3':
                header('HTTP/1.1 403 Forbidden');
                break;
            case '10':
                $r = urlencode($_SERVER['REQUEST_URI']);
                header('Location: /user/login?r=' . $r);
                break;
            case '200':
                $smarty = Yaf_Registry::get('view');
                $smarty->display('help/redirect.tpl');
                break;
            case '301':
                //sitemap

                header('HTTP/1.1 301 Moved Permanently');
                if (isset($data['content'])) {
                    header('Location: ' . $data['content']);
                } else {
                    header('Location: /');
                }
                break;
            case '302':
                if (isset($data['content'])) {
                    header('Location: ' . $data['content']);
                } else {
                    header('Location: /');
                }
                break;
            case '404':
                //sitemap
                $action = Yaf_Application::app()->getDispatcher()->getRequest()->action;
                //如果方法是团但是站点不存在那么跳转首页
                if ($action == "tuan") {
                    header('Location: /');
                } else {

                    header('HTTP/1.1 404 Not Found');
                    header("status: 404 Not Found");
                    $smarty = Yaf_Registry::get('view');
                    $smarty->assign('meta_md5', null);
                    $smarty->display('help/err404.tpl');
                }
                break;
            case '503':
                header('HTTP/1.1 503 Service Temporarily Unavailable');
                header('Status: 503 Service Temporarily Unavailable');
                $smarty = Yaf_Registry::get('view');
                $smarty->assign('meta_md5', null);
                $smarty->display('help/err503.tpl');
                break;
            default:
                //sitemap

                header('HTTP/1.1 404 Not Found');
                header("status: 404 Not Found");
                Yaf_Registry::get('view')->display('help/err404.tpl');
                break;
        }
    }

    private static function _output_callback($data)
    {
        $request = Yaf_Dispatcher::getInstance()->getRequest();
        $method = $request->getMethod();
        $view = Yaf_Registry::get('view');
        $callback = Yaf_Registry::get('callback');
        $view->assign('callback', $callback);
        $view->assign('code', $data['code']);
        if (isset($data['desc'])) {
            $view->assign('desc', $data['desc']);
        }
        if (isset($data['content'])) {
            $view->assign('content', $data['content']);
        }

        if (isset($data['card'])) {
            $view->assign('card', $data['card']);
        }
        if (isset($data['stat_url'])) {
            $view->assign('stat_url', $data['stat_url']);
        }
        if (isset($data['show_url'])) {
            $view->assign('show_url', $data['show_url']);
        }
        $view->display(null);
    }

    private static function _output_json($data)
    {
        $request = Yaf_Dispatcher::getInstance()->getRequest();
        $method = $request->getMethod();
        $view = Yaf_Registry::get('view');
        $callback = Yaf_Registry::get('callback');
        $view->assign('callback', $callback);
        $view->assign('code', $data['code']);
        if (isset($data['desc'])) {
            $view->assign('desc', $data['desc']);
        }
        if (isset($data['content'])) {
            $view->assign('content', $data['content']);
        }
        $view->display('help/err.php');
    }

}
