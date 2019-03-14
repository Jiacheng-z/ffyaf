<?php

/**********************************************************
 * File name: LogsClass.class.php
 * Class name: 日志记录类
 * Create date: 2008/05/14
 * Update date: 2008/09/28
 * Author: blue
 * Description: 日志记录类
 * Example: //设定路径和文件名
 *             $dir="a/b/".date("Y/m",time());
 *             $filename=date("d",time()).".log";
 *             $logs=new Logs($dir,$filename);
 *             $logs->setLog("test".time());
 *             //使用默认
 *             $logs=new Logs();
 *             $logs->setLog("test".time());
 *             //记录信息数组
 *             $logs=new Logs();
 *             $arr=array(
 *               'type'=>'info',
 *               'info'=>'test',
 *               'time'=>date("Y-m-d H:i:s",time())
 *             );
 *             $logs->setLog($arr);
 **********************************************************/
class Com_Log
{
    private $_filepath;   //文件路径
    private $_filename;   //日志文件名
    private $_filehandle; //文件句柄


    const ERROR_API = 'ERROR';      //API返回异常
    const SYSERR = 'SYSERR';        //接口异常
    const INFO = 'INFO';            //数据异常
    const ERROR_CURL = 'CURLERR';   //CURL错误

    /**
     * Sys_Log constructor.
     * @param null $dir
     * @param null $filename
     *
     * 作用:初始化记录类
     * 输入:文件的路径,要写入的文件名
     * 输出:无
     */
    public function __construct($dir = null, $filename = null)
    {
        //默认路径为当前路径
        $this->_filepath = empty($dir) ? '' : $dir;

        //默认为以时间＋.log的文件文件
        $this->_filename = empty($filename) ? date('Y-m-d', time()) . '.log' : $filename;

        //生成路径字串
        $path = $this->_createPath($this->_filepath, $this->_filename);
        //判断是否存在该文件
        if (!file_exists($path)) {//不存在
            //没有路径的话，默认为当前目录
            if (!empty($this->_filepath)) {
                //创建目录
                if (!$this->_createDir($this->_filepath)) {//创建目录不成功的处理
                    die("创建目录失败!");
                }
            }
            //创建文件
            if (!$this->_createLogFile($path)) {//创建文件不成功的处理
                die("创建文件失败!");
            }
        }

        //生成路径字串
        $path = $this->_createPath($this->_filepath, $this->_filename);
        //打开文件
        $this->_filehandle = fopen($path, "a+");
    }

    /**
     * 作用:写入记录
     * 输入:要写入的记录
     * 输出:无
     * @param string|array $log
     */
    public function setLog($log)
    {
        //传入的数组记录
        $str = date('Y-m-d H:i:s', time()) . "\t";

        $str .= $this->format_logs($log) . "\n";

        //写日志
        if (!fwrite($this->_filehandle, $str)) {//写日志失败
            die("写入日志失败");
        }
    }

    public function setLogRaw($log)
    {
        //传入的数组记录
        $str = $this->format_logs($log) . "\n";
        fwrite($this->_filehandle, $str);
    }

    public function format_logs($log = [])
    {
        if (empty($log)) {
            return "";
        }

        if (is_string($log)) {
            return $log;
        }

        if (!is_array($log)) {
            return $log;
        }

        $str = "";
        if (is_array($log)) {
            $str = json_encode($log, JSON_UNESCAPED_UNICODE);
        }
        return $str;
    }


    /**
     * api异常日志写入
     * @param Com_Http_Request $http_request
     * @param $log_type
     * @param $ext_info
     */
    public function write_api_log(Com_Http_Request $http_request, $log_type, $ext_info = array())
    {
        $response_info = $http_request->get_response_info();
        $url = $response_info['url'];
        if (self::ERROR_CURL == $log_type) {
            $http_code = $http_request->get_error_no();
        } else {
            $http_code = $response_info['http_code'];
        }
        $logs = array();
        $logs['log_type'] = $log_type;
        $logs['time'] = date('Y-m-d H:i:s');
        $logs['http_code'] = '[' . $http_code . ']';
        $logs['namelookup_time'] = sprintf("%.5f", $response_info['namelookup_time']);
        $logs['connect_time'] = sprintf("%.5f", $response_info['connect_time']);
        $logs['pretransfer_time'] = sprintf("%.5f", $response_info['pretransfer_time']);
        $logs['starttransfer_time'] = sprintf("%.5f", $response_info['starttransfer_time']);
        $logs['total_time'] = sprintf("%.5f", $response_info['total_time']);
        $logs['request_method'] = $http_request->method;
        $logs['request_url'] = $url;
        if (strtolower($http_request->method) == 'get') {
            $logs['request_fields'] = self::format_api_logs($http_request->query_fields);
        } else {
            $logs['request_fields'] = self::format_api_logs($http_request->post_fields);
        }
        $logs['ext'] = self::format_api_logs($ext_info);
        self::setLog($logs);
    }

    public function format_api_logs($log = array())
    {
        if (empty($log)) {
            return "";
        }

        if (is_string($log)) {
            return $log;
        }

        if (!is_array($log)) {
            return $log;
        }

        $str = "";
        if (is_array($log)) {
            foreach ($log as $k => $v) {
                $str .= $k . ":" . $v . " ";
            }
        }
        return $str;
    }

    private function _createDir($dir)
    {
        return is_dir($dir) or ($this->_createDir(dirname($dir)) and mkdir($dir, 0777));
    }

    /**
     *作用:创建日志文件
     *输入:要创建的目录
     *输出:true | false
     */
    private function _createLogFile($path)
    {
        $fd = fopen($path, "w");
        fclose($fd);
        chmod($path, 0777);
        return file_exists($path);
    }

    /**
     *作用:构建路径
     *输入:文件的路径,要写入的文件名
     *输出:构建好的路径字串
     */
    private function _createPath($dir, $filename)
    {
        if (empty($dir)) {
            return $filename;
        } else {
            return $dir . $filename;
        }
    }

    /**
     *功能: 析构函数，释放文件句柄
     *输入: 无
     *输出: 无
     */
    function __destruct()
    {
        //关闭文件
        fclose($this->_filehandle);
    }

    public static function endAccess($extRet = [])
    {
        $request = Yaf_Dispatcher::getInstance()->getRequest();
        $arr = explode('_', $request->getControllerName(), 2);
        if (!in_array($arr[0], ['Api'])) {
            return;
        }

        $view = Yaf_Registry::get('view');
        if (Com_Util::isDebug()) {
            $log = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_APP);
            $log->setLog([
                'uri' => $request->getRequestUri(),
                'qid' => Com_Context::getParam('qid', 0),
                'p' => Com_Context::getParams(),
                'r' => array_merge($view->values, $extRet)
            ]);
        }
    }
}

?>
