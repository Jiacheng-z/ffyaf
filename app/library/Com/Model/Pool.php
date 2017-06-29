<?php

abstract Class Com_Model_Pool implements Com_Model_Interface
{

    /**
     * @var array 已连接的连接
     */
    static private $pools = [];

    const MODEL_M = 1; /* 主 */
    const MODEL_S = 2; /* 从 */

    /**
     * @var array 配置文件
     */
    protected $configs = [];

    /**
     * @var PDO
     */
    protected $pdo = null;

    protected function __construct($dbName, $ms)
    {
        $this->configs = $this->initConfig();
        $this->pdo = $this->initPDO($dbName, $ms);
    }

    /**
     * 根据db名字和主从初始化连接池并返回连接
     * @param $dbName
     * @param int $ms
     * @return Com_Model_Pool
     * @throws Exception_Program
     */
    static public function getInstance($dbName, $ms = Com_Model_Pool::MODEL_M)
    {

        $class = get_called_class();
        if (!class_exists($class) || !in_array('Com_Model_Interface', class_implements($class))) {
            throw new Exception_Program(MODEL_ERR_PARAMS, "{$class}必须实现Com_Model_Interface");
        }

        if (!isset(self::$pools[$class][$dbName][$ms])) {
            self::$pools[$class][$dbName][$ms] = new $class($dbName, $ms);
        }

        return self::$pools[$class][$dbName][$ms];
    }

    /**
     * 连接PDO
     * @param $dbKey
     * @param int $ms
     * @return PDO
     */
    abstract protected function initPDO($dbKey, $ms = Com_Model_Pool::MODEL_M);

    /**
     * 返回配置选项
     * @return mixed
     */
    abstract protected function initConfig();


    public function __call($strMethod, $arrParam)
    {
        if (method_exists($this->pdo, $strMethod)) {
            return call_user_func_array(array($this->pdo, $strMethod), $arrParam);
        }
        throw new Exception(get_class($this) . "::{$strMethod} not defined");
    }


}