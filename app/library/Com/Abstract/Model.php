<?php

/**
 * 业务数据库查询基类
 * Class Com_Abstract_Model
 */
abstract class Com_Abstract_Model
{

    /**
     * 连接池对象
     * @var Com_Model_Pool|PDO
     */
    protected $db = null;

    /**
     * @var string 表名
     */
    protected $table = null;

    public function __construct($ms = Com_Model_Pool::MODEL_S)
    {
        $this->db = $this->initDb($ms);
    }

    abstract protected function initDb($ms);


    /**
     * 设置连接池
     * @param Com_Model_Pool $obj
     * @return $this
     */
    public function setPool(Com_Model_Pool $obj)
    {
        $this->db = $obj;
        return $this;
    }


    protected function reQuote($v)
    {
        return '`' . $v . '`';
    }

    /**
     * @param array $arr
     * @return bool|string
     */
    public function add(array $arr)
    {
        return $this->db->add($this->table, $arr);
    }

    /**
     * @param $id
     * @param array $arr
     * @return int
     */
    public function update($id, array $arr)
    {
        return $this->db->update($this->table, $id, $arr);
    }

    /**
     * @param int $id
     * @param bool $lock
     * @return mixed
     */
    public function getById($id, $lock = false)
    {
        return $this->db->getById($this->table, $id, $lock);
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getByIds(array $ids)
    {
        return $this->db->getByIds($this->table, $ids);
    }

    /**
     * @param int $id
     * @param bool $hard
     * @return int
     */
    public function delete($id, $hard = false)
    {
        return $this->db->delete($this->table, $id, $hard);
    }

    /**
     * @param bool $filter_status
     * @return array
     */
    public function getAll($filter_status=false)
    {
        return $this->db->getAll($this->table,$filter_status);
    }

    public function beginTransaction()
    {
        /* 开始事务 */
        $this->db->beginTransaction();
    }

    public function rollBack()
    {
        $this->db->rollback();
    }

    public function commit()
    {
        /* 提交事务 */
        $this->db->commit();
    }
}
