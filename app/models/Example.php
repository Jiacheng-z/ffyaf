<?php

class ExampleModel extends Com_Abstract_Model
{
    protected $table = 'example_table';

    protected function initDb($ms)
    {
        return Com_Model_Mysql::getInstance("db_1", $ms);
    }

    public function getFetch($param) {
        $sql = 'SELECT * FROM ' . self::reQuote($this->table) . ' WHERE `param` = :vParam';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':vParam', $param);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getFetchAll($param) {
        $sql = 'SELECT * FROM ' . self::reQuote($this->table) . ' WHERE `param` = :vParam';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':vParam', $param);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateEx($id, $up)
    {
        $sql = 'UPDATE ' . self::reQuote($this->table) . ' SET `field` = :vUp WHERE id = :vId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':vId', $id);
        $stmt->bindValue(':vNum', $up);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
