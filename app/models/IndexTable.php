<?php

class IndexTableModel extends Com_Abstract_Model
{
    protected $_table = 'index_table';

    /**
     * @param $ms
     * @return Com_Model_Pool
     */
    protected function initDb($ms)
    {
        return Com_Model_Mysql::getInstance("example", $ms);
    }

    public function getExample($status)
    {
        $sql = "SELECT * FROM " . $this->reQuote($this->_table) . " WHERE status = :vStatus";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':vStatus', $status);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}