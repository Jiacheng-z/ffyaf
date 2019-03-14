<?php

class Com_Benchmark
{
    static private $_time = [];

    /**
     * 行原因
     * @var string
     */
    static private $_reason = 'default';

    static private $_ext = [];

    static public function set_ext($key, $value)
    {
        self::$_ext[$key] = $value;
    }

    /**
     * 是否记录内存消耗
     * @var bool
     */
    static private $_setmemory = false;

    static public function set_memory($bool = true)
    {
        self::$_setmemory = $bool;
    }

    static public function reason($reason)
    {
        self::$_reason = $reason;
    }

    static public function start($mark = 'start')
    {
        self::$_time = [];
        return self::mark($mark);
    }

    static public function end($mark = 'end')
    {
        $ret = self::mark($mark);
        self::set_log();
        return $ret;
    }

    static public function mark($name = null)
    {
        list($usec, $sec) = explode(" ", microtime());

        if (is_null($name)) {
            return self::$_time[] = ((float)$usec + (float)$sec);
        } else {
            return self::$_time[$name] = ((float)$usec + (float)$sec);
        }
    }

    static private function cost($p1 = 'start', $p2 = 'end', $decimals = 4)
    {
        $t1 = (empty(self::$_time[$p1])) ? self::mark($p1) : self::$_time[$p1];
        $t2 = (empty(self::$_time[$p2])) ? self::mark($p2) : self::$_time[$p2];
        $time = ($t2 - $t1) * 1000;

        return number_format($time, 4, '.', '');
    }

    static private function step($decimals = 4)
    {
        $t1 = end(self::$_time);
        $t2 = self::mark();
        return number_format($t2 - $t1, $decimals);
    }

    static private function time()
    {
        return self::$_time;
    }

    static private function memory($flag = false)
    {
        return memory_get_usage($flag);
    }

    static private function peak_memory($flag = false)
    {
        return memory_get_peak_usage($flag);
    }

    static public function set_log()
    {
        $main = Com_Config::get();
        if (!isset($main->time_log) or !isset($main->time_log->enable) or $main->time_log->enable != true) {
            return;
        }
        $ips = $main->time_log->ips->toArray();
        $currentIp = Com_Tool::getIp();

        //检测当前IP是否在IP段中
        $inIps = false;
        if (Com_Util::is_private_ip($currentIp)) {  //内网IP直接记录
            $inIps = true;
        }

        if ($inIps === false) {
            foreach ($ips as $ip) {
                if (Com_Util::cidr_match($currentIp, $ip) === true) {
                    $inIps = true;
                    break;
                }
            }
        }

        if ($inIps === false) {
            return;
        }

        //记录时间日志
        $url = (substr(php_sapi_name(),0,3) != 'cli') ? $_SERVER['REQUEST_URI'] : '';
        $urlArr = explode('/', $url);
        if (isset($urlArr[1]) and in_array($urlArr[1], ['css', 'iconfont', 'img', 'js'])) {
            return;
        }

        $log = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_TIME);

        //计算所有time的间隔
        $data = [self::$_reason, self::make_costs(self::time()), json_encode(self::time())];
        if (!empty(self::$_ext)) {
            $data[] = json_encode(self::$_ext);
        }
        if (self::$_setmemory === true) {
            $data[] = number_format(self::memory()) . 'byte';
            $data[] = number_format(self::peak_memory()) . 'byte';
        }

        $cmd = function() {
            global $argv;
            return implode(" ", $argv);
        };

        $data[] = (substr(php_sapi_name(),0,3) != 'cli') ? $_SERVER['REQUEST_URI'] : $cmd();

        $log->setLog(implode(' ', $data));
    }

    /**
     * 计算时间差值
     * @param array $marks
     * @return string
     */
    private static function make_costs($marks = [])
    {
        $costs = [];
        $costs[] = self::cost();

        $keys = array_keys($marks);//s -> a -> e
        for ($i = 1; $i < count($keys); $i++) {
            $p1 = $keys[$i - 1];
            $p2 = $keys[$i];
            $costs[] = self::cost($p1, $p2);
        }

        return implode('|', $costs);
    }

}
