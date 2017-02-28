<?php

class IndexTableModel extends Sys_Abstract_Model
{
    protected $_db;
    protected $_table = 'index_table';
    static protected $_instance;

    public function __construct()
    {
        $this->_db = CMysql::getInstance('db_1');
    }

    public function getByStatus($status)
    {
        $sql = "SELECT * FROM " . $this->reQuote($this->_table) . " WHERE status = :vStatus";
        $stmt = $this->_db->prepare($sql);
        $stmt->bindValue(':vStatus', $status);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}