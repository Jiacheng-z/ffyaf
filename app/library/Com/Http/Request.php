<?php
/**
 * Created by PhpStorm.
 * User: jiaoyuan
 * Date: 2017/3/9
 * Time: 下午7:01
 */

class Com_Http_Request
{

    const CRLF = "\r\n";

    public $response_state;

    public $curl_info;

    public $error_msg;
    public $error_no;

    public $actual_host_ip;

    public $has_upload = false;

    public $post_fields = array();

    public $query_string = '';

    public $cookies = array();

    public $headers = array();

    public $query_fields = array();

    public $url;
    public $method = false;
    public $host_name;
    public $host_port = "80";


    private $ch = null;
    private $curl_id = false;

    public $no_body = false;
    public $is_ssl = false;
    public $req_range = array();
    public $debug = false;
    public $gzip = false;

    public $connect_timeout = 1000;
    public $timeout = 1000;

    public $user = null;
    public $psw = null;

    public $curl_cli;

    public $response_header;
    public $response_content;

    public $urlencode = "urlencode_rfc3986";

    private $callback_method;
    private $callback_obj;

    private $new_curl_pool = false;  //是否需要新的curl会话

    public function __construct($url = "") {
        if (!empty($url)) {
            $this->set_url($url);
        }
    }

    /**
     * 根据url设置相关的信息
     * @param $url
     * @throws Exception_Program
     */
    public function set_url($url) {

        if (!empty($this->url)) {
            throw new Exception_Program(HTTP_ERR_PARAMS);
        }

        $url_element = parse_url($url);


        if ($url_element["scheme"] == "https") {
            $this->is_ssl = true;
            $this->host_port = '443';
        } elseif ($url_element["scheme"] != "http") {
            throw new Exception_Program(HTTP_ERR_PARAMS);
        }

        $this->host_name = $url_element['host'];

        $this->url = $url_element['scheme'] . '://' . $this->host_name;

        if (isset($url_element['port'])) {
            $this->host_port = $url_element['port'];
            $this->url .= ':' . $url_element['port'];
        }

        if (isset($url_element['path'])) {
            $this->url .= $url_element['path'];
        }

        if (!empty($url_element['query'])) {
            parse_str($url_element['query'], $query_fields);
            $keys = array_map(array($this, "run_urlencode"), array_keys($query_fields));
            $values = array_map(array($this, "run_urlencode"), array_values($query_fields));
            $this->query_fields = array_merge($this->query_fields, array_combine($keys, $values));
        }
    }

    /**
     * 设置请求方法
     * @param $method
     */
    public function set_method($method) {
        $this->method = strtoupper($method);
    }

    public function add_header($primary, $secondary, $urlencode = false) {
        $primary = $this->run_urlencode($primary, $urlencode);
        $secondary = $this->run_urlencode($secondary, $urlencode);
        $this->headers[$primary] = $secondary;
    }

    public function add_cookie($name, $value, $urlencode = false) {
        $name = $this->run_urlencode($name, $urlencode);
        $value = $this->run_urlencode($value, $urlencode);
        $this->cookies[$name] = $value;
    }

    /**
     * 增加post请求参数
     * @param $name
     * @param $value
     * @param bool $urlencode
     */
    public function add_post_field($name, $value, $urlencode = false) {
        $name = $this->run_urlencode($name, $urlencode);
        $value = $this->run_urlencode($value, $urlencode);
        $this->post_fields[$name] = $value;
    }

    public function add_post_file($name, $path) {
        $this->has_upload = true;
        $name = $this->run_urlencode($name);
        $this->post_fields[$name] = '@' . $path;
    }

    /**
     * 增加GET请求参数
     * @param $name
     * @param $value
     * @param bool $urlencode
     */
    public function add_query_field($name, $value, $urlencode = false) {
        $name = $this->run_urlencode($name, $urlencode);
        $value = $this->run_urlencode($value, $urlencode);
        $this->query_fields[$name] = $value;
    }

    /**
     * 初始化一个curl会话
     */
    public function curl_init() {
        if ($this->ch !== null) {
            throw new Exception_Program(HTTP_ERR_PARAMS,'curl init already');
        }

        //从RequestPool获取一个会话句柄
        $ch = Com_Http_RequestPool::get_curl($this->get_host_id(), $this->new_curl_pool);
        $this->curl_id = self::fetch_curl_id($ch);
        $this->ch = $ch;
        $this->curl_cli = 'curl -v ';
        $this->curl_setopt();
    }

    public static function fetch_curl_id($ch) {
        preg_match('/[^\d]*(\d+)[^\d]*/', (string)$ch, $matches);
        return $matches[1];
    }

    private function load_cookies() {
        if (empty($this->cookies)) {
            return;
        }

        $pairs = array();
        foreach ($this->cookies as $name => $value) {
            $pairs[] = $name . '=' . $value;
        }

        $cookie = implode('; ', $pairs);
        curl_setopt($this->ch, CURLOPT_COOKIE, $cookie);
        $this->curl_cli .= " -b \"" . $cookie . "\"";
        unset($pairs);
    }

    private function load_headers() {
        if (empty($this->headers)) {
            return;
        }
        $headers = array();
        foreach ($this->headers as $k => $v) {
            $tmp = $k . ":" . $v;
            $this->curl_cli .= " -H \"" . $tmp . "\"";
            $headers[] = $tmp;
        }

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
    }

    private function load_query_fields() {
        $this->query_string = '';
        if (empty($this->query_fields)) {
            return;
        }

        foreach ($this->query_fields as $name => $value) {
            $pairs[] = $name . '=' . $value;
        }

        if($pairs){
            $this->query_string = implode('&', $pairs);
        }
        curl_setopt($this->ch, CURLOPT_URL, $this->url . '?' . $this->query_string);
    }

    private function load_post_fields() {
        if (empty($this->post_fields)) {
            return;
        }
        if(true == $this->has_upload){
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->post_fields);
        }
        else{
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, self::http_build_query($this->post_fields));
        }
        foreach ($this->post_fields as $name => $value) {
            if ($this->has_upload) {
                $this->curl_cli .= " --form \"" . $name . '=' . $value . "\"";
            } else {
                $pairs[] = $name . '=' . $value;
            }
        }

        if (!empty($pairs)) {
            $this->curl_cli .= " -d \"" . implode('&', $pairs) . "\"";
        }
    }

    /**
     * 拼装http查询串（不经过urlencode）
     */
    public static function http_build_query($query_data = array()){
        if(empty($query_data)){
            return '';
        }
        $pairs = array();
        foreach ($query_data as $key => $value){
            $pairs[] = "{$key}={$value}";
        }
        $query_string = implode("&", $pairs);
        return $query_string;
    }

    private function load_userpwd() {
        if(is_null($this->user) || is_null($this->psw)) {
            return;
        }
        $str_userpwd = $this->user . ':' . $this->psw;
        $this->curl_cli .= "-u \"$str_userpwd\" ";
        curl_setopt($this->ch, CURLOPT_USERPWD, $str_userpwd);
    }

    /**
     * 设置会话参数
     * curl_setopt
     */
    private function curl_setopt() {

        //设置URL
        curl_setopt($this->ch, CURLOPT_URL, $this->url);

        // 设置header
        curl_setopt($this->ch, CURLOPT_HEADER, true);

        if ($this->is_ssl) {
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
            $this->curl_cli .= " -k";
        }

        //启用时将不对HTML中的body部分进行输出
        if ($this->no_body) {
            curl_setopt($this->ch, CURLOPT_NOBODY, true);
        }

        //设置HTTP传输范围
        if (!empty($this->req_range)) {
            curl_setopt($this->ch, CURLOPT_RANGE, $this->req_range[0]."-".$this->req_range[1]);
        }

        // -v
        //如果成功只将结果返回，不自动输出任何内容。如果失败返回FALSE
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

        // default
        //设置curl使用的HTTP协议
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        // not use
        //在HTTP请求中包含一个”user-agent”头的字符串。
        curl_setopt($this->ch, CURLOPT_USERAGENT, "gjz framework HttpRequest class");

        if ($this->debug) {
            // -v
            //发送请求的字符串
            curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);
        }

        //header中“Accept-Encoding: ”部分的内容
        if ($this->gzip) {
            curl_setopt($this->ch, CURLOPT_ENCODING, "gzip");
            $this->curl_cli .= " --compressed ";
        }


        //设置超时时间
        $version = curl_version();
        if (version_compare($version["version"], "7.16.2") < 0) {
            //如果timeout为0，则curl将wait indefinitely.故此处将意外设置timeout < 1sec的情况，重新
            //设置为1s
            $timeout = floor($this->connect_timeout / 1000);
            if($this->connect_timeout > 0 && $timeout <= 0){
                $timeout = 1;
            }
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
        } else {
            curl_setopt($this->ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connect_timeout);
            curl_setopt($this->ch, CURLOPT_TIMEOUT_MS, $this->timeout);
        }
        unset($version);
        $this->curl_cli .= " --connect-timeout " . round($this->connect_timeout / 1000, 3);
        $this->curl_cli .= " -m " . round($this->timeout / 1000, 3);

        // -x
        if (!empty($this->actual_host_ip)) {
            curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($this->ch, CURLOPT_PROXY, $this->actual_host_ip);  //设置通过的HTTP代理服务器
            curl_setopt($this->ch, CURLOPT_PROXYPORT, $this->host_port);
            $this->curl_cli .= " -x " . $this->actual_host_ip . ":" . $this->host_port;
        }

        //cookie 根据实际需要在打开
        $this->load_cookies();

        //设置一个header中传输内容的数组
        $this->load_headers();

        //get参数
        $this->load_query_fields();

        //post参数
        $this->load_post_fields();

        //用户名&密码
        $this->load_userpwd();

        if ($this->method) {
            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->method));
            $this->curl_cli .= " -X \"{$this->method}\"";
        }
        $this->curl_cli .= " \"" . $this->url . ($this->query_string ? '?' . $this->query_string : '') . "\"";
    }

    /**
     * 设置接口响应的状态
     * @param $state
     * @param $error_msg
     * @param $error_no
     */
    public function set_response_state($state, $error_msg, $error_no) {
        $this->response_state = $state;
        $this->error_msg = $error_msg;
        $this->error_no = $error_no;
    }




    public function set_response($content, $info, $invoke_callback = true) {
        $this->curl_info = $info;

        if (empty($content)) {
            return;
        }

        $section_separator = str_repeat(self::CRLF, 2);
        $section_separator_length = strlen($section_separator);
        // pick out http 100 status header
        $http_100 = "HTTP/1.1 100 Continue" . $section_separator;
        if (false !== strpos($content, $http_100)) {
            $content = substr($content, strlen($http_100));
        }

        $last_header_pos = 0;
        // put header and content into each var, 3xx response will generate many header :(
        for($i = 0, $pos = 0; $i <= $this->curl_info['redirect_count']; $i ++) {
            if ($i + 1 > $this->curl_info['redirect_count'] && $pos) {
                $last_header_pos = $pos + $section_separator_length;
            }
            $pos += $i > 0 ? $section_separator_length : 0;
            $pos = strpos($content, $section_separator, $pos);
        }

        $this->response_content = substr($content, $pos + $section_separator_length);
        $headers = substr($content, $last_header_pos, $pos - $last_header_pos);
        $headers = explode(self::CRLF, $headers);
        foreach ($headers as $header) {
            if (false !== strpos($header, "HTTP/1.1")) {
                continue;
            }

            $tmp = explode(":", $header, 2);
            $response_header_key = strtolower(trim($tmp[0]));
            if(!isset($this->response_header[$response_header_key])){
                $this->response_header[$response_header_key] = trim($tmp[1]);
            }
            else{
                if(!is_array($this->response_header[$response_header_key])){
                    $this->response_header[$response_header_key] = (array)$this->response_header[$response_header_key];
                }
                $this->response_header[$response_header_key][] = trim($tmp[1]);
            }
        }
        // is there callback?
        if ($invoke_callback && !empty($this->callback_obj) && !empty($this->callback_method)) {
            call_user_func_array(array($this->callback_obj, $this->callback_method), array($this));
        }
    }

    public function reset_ch() {
        $this->ch = null;
        $this->curl_id = false;
    }


    public function send()
    {
        $this->curl_init();

        //执行curl会话。
        $content = curl_exec($this->ch);
        if (curl_errno($this->ch) === 0) {
            $rtn = true;
            $this->set_response_state(true, "", curl_errno($this->ch));
        } else {
            $this->set_response_state(false, curl_error($this->ch), curl_errno($this->ch));
            $rtn = false;
        }
        $this->set_response($content, curl_getinfo($this->ch));

        Com_Http_RequestPool::reset_curl_state($this->get_host_id(), $this->get_curl_id());

        $this->reset_ch();

        return $rtn;
    }

    public function get_response_content() {
        return $this->response_content;
    }

    public function run_urlencode($input, $urlencode = false) {
        if ($urlencode) {
            return $this->{$urlencode}($input);
        } elseif ($this->urlencode) {
            return $this->{$this->urlencode}($input);
        } else {
            return $input;
        }
    }

    public static function urlencode_rfc3986($input) {
        if (is_array($input)) {
            return array_map(array('Com_Http_Request', 'urlencode_rfc3986'), $input);
        } else if (is_scalar($input)) {
            return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));
        } else {
            return '';
        }
    }

    private function get_host_id() {
        return $this->host_name . ':' . $this->host_port;
    }

    public function get_curl_id() {
        return $this->curl_id;
    }

    public function get_error_msg() {
        return $this->error_msg;
    }

    public function get_error_no() {
        return $this->error_no;
    }

    public function get_response_info($key = "") {
        if (empty($key)) {
            return $this->curl_info;
        } else {
            if (isset($this->curl_info[$key])) {
                return $this->curl_info[$key];
            }
        }
    }

}