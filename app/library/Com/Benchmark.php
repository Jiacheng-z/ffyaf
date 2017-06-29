<?php

class Com_Benchmark
{
    static private $_time = [];

    /**
     * 行原因
     * @var string
     */
    static private $_reason = 'default';

    /**
     * 是否记录内存消耗
     * @var bool
     */
    static private $_setmemory = true;

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
        if (is_null($name)) {
            return self::$_time[] = microtime(true);
        } else {
            return self::$_time[$name] = microtime(true);
        }
    }

    static private function cost($p1 = 'start', $p2 = 'end', $decimals = 4)
    {
        $t1 = (empty(self::$_time[$p1])) ? self::mark($p1) : self::$_time[$p1];
        $t2 = (empty(self::$_time[$p2])) ? self::mark($p2) : self::$_time[$p2];
        $t1 = $t1 * 1000;
        $t2 = $t2 * 1000;

        return abs(number_format($t2 - $t1, $decimals));
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
        if (!defined("APP_TIME_LOG") or APP_TIME_LOG !== true) {
            return;
        }

        $url = $_SERVER['REQUEST_URI'];
        $urlArr = explode('/', $url);
        if (isset($urlArr[1]) and in_array($urlArr[1], ['css', 'iconfont', 'img', 'js'])) {
            return;
        }

        $log = new Com_Log(LOG_RUNTIME_PATH, LOG_FILE_TIME);

        $data = [self::$_reason, self::cost(), json_encode(self::time()), $_SERVER['REQUEST_URI']];
        if (self::$_setmemory === true) {
            $data[] = number_format(self::memory()) . 'byte';
            $data[] = number_format(self::peak_memory()) . 'byte';
        }

        $log->setLog(implode(' ', $data));
    }

}
