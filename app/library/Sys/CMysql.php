<?php

class CMysql extends Singleton
{
    private $_config = NULL;
    private $db = NULL;
    private $_obj = NULL;

    static private $_source = array();
    static private $_instance = array();

    public function __construct($obj = 'miaoche') {
        $this->_obj = $obj;
        if (!($this->_config = Yaf_Registry::get("db_config"))) {
            $appConfig = Yaf_Application::app()->getConfig();
            $this->_config = new Yaf_Config_Simple(include(CONFIG_PATH . '/db.php'));
            Yaf_Registry::set("db_config", $this->_config);
        }
    }

    static public function getInstance($obj = 'miaoche') {
        if (!isset(self::$_instance[$obj])) {
            self::$_instance[$obj] = new CMysql($obj);
        }
        return self::$_instance[$obj];
    }

    private function connect($conf) {
        $dsn = 'mysql:host='.$conf->host.';dbname='.$conf->dbname.';port='.$conf->port.';';
        $pdo = new PDO($dsn, $conf->user, $conf->pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $stmt = $pdo->query("show variables like '%sql_mode%'");
        $res = $stmt->fetch();
        $sql_mode = $res['Value'].",NO_UNSIGNED_SUBTRACTION";
        $pdo->query("SET sql_mode='".$sql_mode."'");
        $pdo->query("SET NAMES utf8");
        return $pdo;
    }

    private function getDbSource($statement,$master = 0) {
        if (isset($this->_config->{$this->_obj}->slave)) {

            if (is_array($statement) AND empty($statement)) $statement = "";
            //检测语句
            if (is_string($statement) AND substr(strtolower(trim($statement)),0,strlen('select')) == 'select' AND $master == 0) {
                //返回从库链接
                if (!isset(self::$_source[$this->_obj]['slave'])) {
                    self::$_source[$this->_obj]['slave'] = $this->connect($this->_config->{$this->_obj}->slave);
                }

                return self::$_source[$this->_obj]['slave'];
            }

            if (!isset(self::$_source[$this->_obj]['master'])) {
                self::$_source[$this->_obj]['master'] = $this->connect($this->_config->{$this->_obj}->master);
            }

            return self::$_source[$this->_obj]['master'];
        }

        if (!isset(self::$_source[$this->_obj])) {
            self::$_source[$this->_obj] = $this->connect($this->_config->{$this->_obj});
        }
        return self::$_source[$this->_obj];
    }

    /**
     * @param $statement  String SQL 语句
     * @param array $driver_options
     */
    public function prepare($statement , array $driver_options = array(),$master = 0) {
        //检测是否有主从配置
        return $this->getDbSource($statement,$master)->prepare($statement,$driver_options);
    }

    public function __call($strMethod, $arrParam) {
        $this->db = $this->getDbSource($arrParam);
        if (method_exists($this->db, $strMethod)) {
            return call_user_func_array(array($this->db, $strMethod), $arrParam);
        }
        throw new Exception(get_class($this)."::{$strMethod} not defined");
    }

}

?>
