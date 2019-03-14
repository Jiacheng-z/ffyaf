<?php

/**
 * api接口批量请求类
 *
 * Created by PhpStorm.
 * User: jiacheng
 * Date: 2017/4/12
 * Time: 下午3:19
 */
class Com_Api_PlatformMulti
{

    const METHOD_GET = "GET";
    const METHOD_POST = "POST";

    private $url = "";
    private $method = self::METHOD_GET;

    /**
     * 毫秒
     * @var int
     */
    private $timeout = 1000;

    /**
     * 待发送的参数组
     * @var array
     */
    private $paramsGroup = [];

    public function setParam($key, $params = [], $getBody = null)
    {
        $this->paramsGroup[$key] = [
            'params' => $params,
            'get_body' => $getBody,
        ];
    }

    /**
     * 结果组
     * @var array
     */
    private $resultGroup = [];

    public function getResults()
    {
        return $this->resultGroup;
    }

    /**
     * @var int 发送一组多少个(并发数最大值)
     */
    private $oneGroupNum = 20;

    public function __construct($url, $method = self::METHOD_GET)
    {
        $this->url = $url;
        $this->method = $method;
    }


    public function send()
    {
        $tmpGroup = [];

        foreach ($this->paramsGroup as $key => $value) {
            $tmpGroup[$key] = $value;
            if (count($tmpGroup) >= $this->oneGroupNum) {
                $this->curl_multi_post($tmpGroup);
                $tmpGroup = [];
            }
        }

        if (!empty($tmpGroup)) {
            $this->curl_multi_post($tmpGroup);
        }
    }


    private function curl_multi_post($group)
    {
        // array of curl handles
        $curly = [];

        $mh = curl_multi_init();

        foreach ($group as $key => $struct) {
            $curly[$key] = curl_init();

            $params = $struct["params"];
            $getBody = $struct["get_body"];

            if ($this->method == self::METHOD_GET) {
                $u = $this->url . "?" . http_build_query($params);
                curl_setopt($curly[$key], CURLOPT_URL, $u);
            } else {
                curl_setopt($curly[$key], CURLOPT_URL, $this->url);
            }

            curl_setopt($curly[$key], CURLOPT_HEADER, false);
            curl_setopt($curly[$key], CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

            //设置超时时间
            $version = curl_version();
            if (version_compare($version["version"], "7.16.2") < 0) {
                //如果timeout为0，则curl将wait indefinitely.故此处将意外设置timeout < 1sec的情况，重新
                //设置为1s
                $timeout = floor($this->timeout / 1000);
                if ($this->timeout > 0 && $timeout <= 0) {
                    $timeout = 1;
                }
                curl_setopt($curly[$key], CURLOPT_CONNECTTIMEOUT, $timeout);
                curl_setopt($curly[$key], CURLOPT_TIMEOUT, $timeout);
            } else {
                curl_setopt($curly[$key], CURLOPT_NOSIGNAL, 1);
                curl_setopt($curly[$key], CURLOPT_CONNECTTIMEOUT_MS, $this->timeout);
                curl_setopt($curly[$key], CURLOPT_TIMEOUT_MS, $this->timeout);
            }
            unset($version);

            curl_setopt($curly[$key], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curly[$key], CURLOPT_HTTPHEADER, array('Accept-Encoding:gzip'));
            curl_setopt($curly[$key], CURLOPT_ENCODING, "gzip");

            if ($this->method == self::METHOD_POST) {
                curl_setopt($curly[$key], CURLOPT_POST, 1);
                curl_setopt($curly[$key], CURLOPT_POSTFIELDS, http_build_query($params));
            }

            if ($this->method == self::METHOD_GET and !empty($getBody)) {
                curl_setopt($curly[$key], CURLOPT_POSTFIELDS, $getBody);
            }

            curl_multi_add_handle($mh, $curly[$key]);
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        foreach ($curly as $id => $c) {
            $this->resultGroup[$id] = curl_multi_getcontent($c);
            curl_multi_remove_handle($mh, $c);
        }
        // all done
        curl_multi_close($mh);
    }
}