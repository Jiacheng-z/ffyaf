<?php

class Com_Api_Platform extends Com_Api_Abstract
{

    public function __construct($url, $method = false)
    {
        parent::__construct($url, $method);
    }

    /**
     * @param bool $throw_exp
     * @param array $defaut
     * @return array|mixed
     * @throws Exception_Program
     * @throws Yaf_Exception
     */
    public function get_rst($throw_exp = true, $defaut = array())
    {
        parent::send();
        $content = $this->http_request->get_response_content();

        $result = Com_Util::json_decode($content, true);

        $exp_code = $exp_msg = $logs_type = $logs_ext = false;
        if ($this->http_request->get_response_info('http_code') != '200') {
            if (isset($result['error'])) {
                $exp_msg = $result['error'];
                $exp_code = $result['error_code'];
                $logs_type = Com_Log::ERROR_API;
                $logs_ext = $result;
            } else {
                $exp_msg = "http error:" . $this->http_request->get_response_info('http_code');
                $exp_code = $this->http_request->get_response_info('http_code');
                $logs_type = Com_Log::SYSERR;
                $logs_ext = "http error:" . $this->http_request->get_response_info('http_code');
            }
        } elseif (!is_array($result)) {
            $exp_msg = "api return data can not be json_decode result: [" . $this->http_request->get_response_content() . ']';
            $exp_code = HTTP_ERR_RETURN;
            $logs_type = Com_Log::INFO;
            $logs_ext = "api return data can not be json_decode result: [" . $this->http_request->get_response_content() . ']';
        }

        //记录错误日志
        if (false !== $exp_code && false !== $exp_msg) {
            $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
            $err->write_api_log($this->http_request, $logs_type, $logs_ext);
            unset($err);
            if ($throw_exp == true) {
                throw new Exception_Program($exp_code, $exp_msg);
            } else {
                return $defaut;
            }
        }

        return $result;
    }

}