<?php

class Com_Model_Mysql extends Com_Model_Pool
{

    /**
     * 设置配置文件
     * @return mixed
     * @throws Exception_Program
     */
    protected function initConfig()
    {
        $configs = Com_Config::get("db");
        if (empty($configs)) {
            throw new Exception_Program(MODEL_ERR_PARAMS, "没有找到数据库配置文件");
        }
        return $configs;
    }

    /**
     * @param $dbKey
     * @param int $ms
     * @return PDO
     * @throws Exception_Program
     */
    protected function initPDO($dbKey, $ms = Com_Model_Pool::MODEL_M)
    {
        if (empty($this->configs[$dbKey])) {
            throw new Exception_Program(MODEL_ERR_PARAMS, "没有找到数据库配置项");
        }

        /**
         * 分主从
         */
        if (isset($this->configs[$dbKey]["master"])) {
            $cHost = (($ms == Com_Model_Pool::MODEL_M) ? $this->configs[$dbKey]["master"]["host"] : $this->configs[$dbKey]["slave"]["host"]);
            $cDbname = (($ms == Com_Model_Pool::MODEL_M) ? $this->configs[$dbKey]["master"]["dbname"] : $this->configs[$dbKey]["slave"]["dbname"]);
            $cPost = (($ms == Com_Model_Pool::MODEL_M) ? $this->configs[$dbKey]["master"]["port"] : $this->configs[$dbKey]["slave"]["port"]);
            $cUser = (($ms == Com_Model_Pool::MODEL_M) ? $this->configs[$dbKey]["master"]["user"] : $this->configs[$dbKey]["slave"]["user"]);
            $cPass = (($ms == Com_Model_Pool::MODEL_M) ? $this->configs[$dbKey]["master"]["pass"] : $this->configs[$dbKey]["slave"]["pass"]);
        } else {
            $cHost = $this->configs[$dbKey]["host"];
            $cDbname = $this->configs[$dbKey]["dbname"];
            $cPost = $this->configs[$dbKey]["port"];
            $cUser = $this->configs[$dbKey]["user"];
            $cPass = $this->configs[$dbKey]["pass"];
        }

        $cHost = isset($_SERVER[$cHost]) ? $_SERVER[$cHost] : $cHost;
        $cDbname = isset($_SERVER[$cDbname]) ? $_SERVER[$cDbname] : $cDbname;
        $cPost = isset($_SERVER[$cPost]) ? $_SERVER[$cPost] : $cPost;
        $cUser = isset($_SERVER[$cUser]) ? $_SERVER[$cUser] : $cUser;
        $cPass = isset($_SERVER[$cPass]) ? $_SERVER[$cPass] : $cPass;

        $dsn = 'mysql:host=' . $cHost . ';dbname=' . $cDbname . ';port=' . $cPost . ';';
        $pdo = new PDO($dsn, $cUser, $cPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $stmt = $pdo->query("show variables like '%sql_mode%'");
        $res = $stmt->fetch();
        $sql_mode = $res['Value'] . ",NO_UNSIGNED_SUBTRACTION";
        $pdo->query("SET sql_mode='" . $sql_mode . "'");
        $pdo->query("SET NAMES utf8");
        return $pdo;
    }

    /* --------------------------- 接口实现 --------------------------- */


    private static function reQuote($v)
    {
        return '`' . $v . '`';
    }

    /**
     * @param $table
     * @param array $arr
     * @return bool|string
     */
    public function add($table, array $arr)
    {
        if (!isset($arr['create_time'])) {
            $arr['create_time'] = date('Y-m-d H:i:s', time());
        }

        $columns = [];
        $values = [];
        foreach ($arr as $k => $v) {
            $columns[] = self::reQuote($k);
            $values[] = ':' . $k;
        }
        $columns_str = implode(',', $columns);
        $values_str = implode(',', $values);

        $stmt = $this->pdo->prepare("INSERT INTO " . self::reQuote($table) . " (" . $columns_str . ") VALUES(" . $values_str . ")");
        foreach ($arr as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->execute();

        $id = $this->pdo->lastInsertId();

        if ($id === false) {
            return false;
        }

        return $id;
    }

    /**
     * 更新单条数据
     * @param $table
     * @param $id
     * @param array $arr
     * @return int
     */
    public function update(&$table, $id, array $arr)
    {
        $sets = [];
        foreach ($arr as $k => $v) {
            $sets[] = self::reQuote($k) . '=:' . $k;
        }
        $sets_str = implode(',', $sets);
        $stmt = $this->pdo->prepare("update " . self::reQuote($table) . " set " . $sets_str . " where id=:id");
        foreach ($arr as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * 获取单条数据
     * @param string $table
     * @param int $id
     * @param bool $lock
     * @return mixed
     */
    public function getById(&$table, $id, $lock = false)
    {
        $sql = "select * from " . self::reQuote($table) . " where id = :id";
        if ($lock) {
            $sql .= " for update";
        }
        if ($lock) {
            $stmt = $this->pdo->prepare($sql, array(), 1);
        } else {
            $stmt = $this->pdo->prepare($sql);
        }
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * 根据id array获取多条数据
     * @param string $table
     * @param array $ids
     * @return array
     */
    public function getByIds(&$table, array $ids)
    {
        $ids_str = implode(',', $ids);
        $stmt = $this->pdo->prepare("select * from " . self::reQuote($table) . " where id in (" . $ids_str . ")");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 删除单条数据
     * @param string $table
     * @param int $id
     * @param bool $hard
     * @return int
     */
    public function delete(&$table, $id, $hard = false)
    {
        if ($hard) {
            $stmt = $this->pdo->prepare("delete from " . self::reQuote($table) . " where id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();

        } else {
            return $this->update($table, $id, ["status" => -1]);
        }
        return $stmt->rowCount();
    }

    /**
     * 获取表中全部数据
     * @param string $table
     * @return array
     */
    public function getAll(&$table)
    {
        $stmt = $this->pdo->prepare("select * from " . self::reQuote($table));
        $stmt->execute();
        return $stmt->fetchAll();
    }

}

?>
