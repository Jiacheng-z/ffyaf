<?php

abstract class Com_Api_Abstract
{
    protected $http_request;

    /**
     * @var string 请求方法
     */
    protected $method;

    /**
     * @var array请求参数别名
     */
    protected $alias = array();

    /**
     * $rules[实名] = array(
     * 'data_type' => 'int/int64/string/filepath/float',
     * 'where' => 'PARAM_IN_*',
     * 'is_required' => 'true/false',
     * 'final_value' => ''
     * );
     * @var array 存放参数规则
     */
    protected $rules = array();

    /**
     * $rule_method[param_name] = array(
     *     method,
     * );
     * @var array 存放参数名对应的请求方式
     */
    protected $rule_method = array();

    /**
     * @var array 参数的值，key为actual_name
     */
    protected $values = array();

    /**
     * @var array 设置各种的回调
     */
    protected $callback = array();

    /**
     * @var int 参数位置由接口的http method决定（在url或http body中）
     */
    const PARAM_IN_BY_METHOD = 0;

    /**
     * @var int 强行将参数放在url中
     */
    const PARAM_IN_GET = 1;

    /**
     * @var int 强行将参数放在http body中
     */
    const PARAM_IN_POST = 2;

    /**
     * 供 ##接口开发者## 设置URL和HTTP REQUEST METHOD
     * Com_Api_Abstract constructor.
     * @param $url
     * @param bool $method
     */
    public function __construct($url, $method = false)
    {
        $this->http_request = new Com_Http_Request($url);

        $this->method = strtoupper($method);
        $this->http_request->set_method($method);
    }

    /**
     * 接口请求方法
     */
    abstract public function get_rst();

    /**
     * 发送请求
     * curl错误在这里被处理
     * 正确的返回值由get_rst处理
     *
     */
    protected function send()
    {
        $this->apply_rules();

        $this->run_callback("before_send");

        $send_rst = $this->http_request->send();

        $this->run_callback("after_send");

        //发送请求失败记录错误日志
        if (!$send_rst) {
            $err = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_ERR);
            $err->write_api_log($this->http_request, Com_Log::ERROR_CURL);
            unset($err);
            throw new Exception_Program(HTTP_ERR_SEND);
        }

    }

    /**
     * 供 ##接口调用者## 设置参数
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        Com_Assert::as_exception();
        Com_Assert::true($actual_name = $this->get_actual_name($name), "{$name} is not allowed");
        $this->values[$actual_name] = $this->run_callback('set', $actual_name, $value);
    }

    /**
     * 返回values数据
     * @param string $actual_name
     * @return mixed|null
     */
    public function __get($actual_name)
    {
        if (isset($this->values[$actual_name])) {
            return $this->values[$actual_name];
        }
        return null;
    }

    public function set_manual($name, $value)
    {
        Com_Assert::as_exception();
        Com_Assert::true($actual_name = $this->get_actual_name($name), "{$name} is not allowed");
        $this->values[$actual_name] = $this->run_callback('set', $actual_name, $value);
    }


    /**
     * 返回values数据
     */
    public function get_values()
    {
        return $this->values;
    }

    protected function apply_rules()
    {
        if (empty($this->rules)) {
            return;
        }

        foreach ($this->rules as $actual_name => $rule) {
            if ($rule['is_required'] && !isset($this->values[$actual_name])) {
                throw new Exception_Program(HTTP_ERR_PARAMS, "param " . $actual_name . " is required");
            } elseif (!isset($this->values[$actual_name])) {
                continue;
            }

            $value = $this->values[$actual_name];
            switch ($rule['data_type']) {
                case "boolean" :
                    $value = ((boolean)$value) ? 'true' : 'false';
                    break;
                case "int" :
                    $value = (int)$value;
                    break;
                case "string" :
                case "filepath" :
                case "date" :
                    $value = (string)$value;
                    break;
                case "float" :
                    $value = (float)$value;
                    break;
                case "int64" :
                    if (!Com_Util::is_64bit()) {
                        //if (!is_string($value) && !is_float($value)) {/*throw?*/}
                        $value = (string)$value;
                    } else {
                        $value = (int)$value;
                    }
                    break;
                default :
                    throw new Exception_Program(HTTP_ERR_PARAMS, "invalid data type");
            }

            if (isset($this->rule_method[$actual_name])) {
                $method = $this->rule_method[$actual_name];
            } else {
                $method = $this->method;
            }
            if (($rule['where'] == self::PARAM_IN_BY_METHOD && $method === "GET") || $method === "DELETE" || $rule['where'] == self::PARAM_IN_GET) {
                $this->http_request->add_query_field($actual_name, $value);
            } else {
                if ($rule['data_type'] === 'filepath') {
                    $this->http_request->add_post_file($actual_name, $value);
                } else {
                    $this->http_request->add_post_field($actual_name, $value);
                }
            }
        }
    }


    /**
     * 供 ##接口开发者## 设置接口规则
     * @param string $actual_name 参数名称
     * @param string $data_type 类型
     * @param bool $is_required 是否必须参数
     * @param int $where
     */
    public function add_rule($actual_name, $data_type, $is_required = false, $where = 0)
    {
        $this->rules[$actual_name]['data_type'] = $data_type;
        $this->rules[$actual_name]['is_required'] = $is_required;
        $this->rules[$actual_name]['where'] = $where;
    }

    /**
     * 为参数添加特殊的请求
     * @param string $actual_name
     * @param string $method
     * @throws Exception_Program
     */
    public function add_rule_method($actual_name, $method)
    {
        $allow_methods = array('GET' => 0, 'POST' => 1, 'DELETE' => 2);
        if (!isset($allow_methods[$method])) {
            throw new Exception_Program("method for the param {$actual_name} error:  $method");
        }
        if ($this->method != 'POST' && $method == 'POST') {
            $this->http_request->set_method('POST');
        }
        $this->rule_method[$actual_name] = $method;
    }

    /**
     * 供 ##接口开发者## 设置参数别名
     * @param $actual_name
     * @param $alias
     */
    public function add_alias($actual_name, $alias)
    {
        $this->alias[$alias] = $actual_name;
    }

    /**
     * 供 ##接口开发者## 增加 ##设置单个参数时## 的callback
     * $request->add_set_callback('ids', 'Comm_Weibo_Api_Util', 'check_batch_values', array('int64', '~', 2000));
     *
     * @param $actual_name
     * @param $obj      回调的类名称或者对象
     * @param $method   回调的类方法
     * @param array $param 参数
     */
    public function add_set_callback($actual_name, $obj, $method, $param = array())
    {
        $this->callback['set'][$actual_name][] = array($obj, $method);
        $this->callback['set'][$actual_name][] = $param;
    }

    /**
     * 供 ##接口开发者## 增加 ##发送请求前## 的callback
     * @param $obj      回调的类名称或者对象
     * @param $method   回调的类方法
     * @param array $param 参数
     * 注:每次请求只能设置一次的before_callback
     */
    public function add_before_send_callback($obj, $method, $param = array())
    {
        Com_Assert::as_exception();
        Com_Assert::false(isset($this->callback['before_send']), "don not add before send callback repeatly");
        $this->callback['before_send'][] = array($obj, $method);
        $this->callback['before_send'][] = $param;
    }


    /**
     * 供 ##接口开发者## 增加 ##发送请求后## 的callback
     * @param $obj     回调的类名称或者对象
     * @param $method   回调的类方法
     * @param array $param 参数
     * 注:每次请求只能设置一次的after_callback
     */
    public function add_after_send_callback($obj, $method, $param = array())
    {
        Com_Assert::as_exception();
        Com_Assert::false(isset($this->callback['after_send']), "don not add after send callback repeatly");
        $this->callback['after_send'][] = array($obj, $method);
        $this->callback['after_send'][] = $param;
    }


    /**
     * 设置请求超时时间 单位毫秒ms
     * @param $connect_timeout
     * @param $time
     */
    public function set_request_timeout($connect_timeout, $time)
    {
        $this->http_request->connect_timeout = $connect_timeout;
        $this->http_request->timeout = $time;
    }

    public function set_request_header($primary, $secondary, $urlencode = false)
    {
        $this->http_request->add_header($primary, $secondary, $urlencode);
    }

    public function set_request_cookie($name, $value, $urlencode = false)
    {
        $this->http_request->add_cookie($name, $value, $urlencode = false);
    }

    public function set_gzip($bool = true)
    {
        $this->http_request->gzip = $bool;
    }

    private function run_callback($phase, $actual_name = '', $value = '')
    {
        if (!isset($this->callback[$phase])) {
            return $value;
        }

        $param = array();
        if ($phase == "set") {
            Com_Assert::true($actual_name != '');
            if (isset($this->callback['set'][$actual_name])) {
                $callback = $this->callback['set'][$actual_name][0];
                $param = $this->callback['set'][$actual_name][1];
                $param = is_array($param) ? $param : array();
                array_unshift($param, $value);
                $value = call_user_func_array($callback, $param);
                return $value;
            } else {
                return $value;
            }
        } else {
            if (isset($this->callback[$phase])) {
                $callback = $this->callback[$phase][0];
                $param = $this->callback[$phase][1];
                $param[] = $this;
                call_user_func_array($callback, $param);
            }
        }
    }

    /**
     * 检查参数是否在允许范围内
     * @param $name
     * @return bool
     */
    private function get_actual_name($name)
    {
        if (isset($this->rules[$name])) {
            return $name;
        }

        if (array_key_exists($name, $this->alias)) {
            return $this->alias[$name];
        }

        return false;
    }


    public function get_url()
    {
        return isset($this->http_request->curl_info['url']) ? $this->http_request->curl_info['url'] : $this->http_request->url;
    }

}