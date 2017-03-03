<?php

abstract class Com_Abstract_Model extends Singleton
{
    public static function reQuote($v)
    {
        return '`' . $v . '`';
    }

    public function add($arr)
    {
        if (!isset($arr['create_time'])) {
            $arr['create_time'] = date('Y-m-d H:i:s', time());
        }

        $columns = [];
        $values = [];
        foreach ($arr as $k => $v) {
            $columns[] = $this->reQuote($k);
            $values[] = ':' . $k;
        }
        $columns_str = implode(',', $columns);
        $values_str = implode(',', $values);
        $stmt = $this->_db->prepare("insert into " . $this->reQuote($this->_table) . " (" . $columns_str . ") values(" . $values_str . ")");
        foreach ($arr as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->execute();

        $id = $this->_db->lastInsertId();

        if ($id === false) {
            return false;
        }

        return $id;
    }

    public function update($id, $arr)
    {
        foreach ($arr as $k => $v) {
            $sets[] = $this->reQuote($k) . '=:' . $k;
        }
        $sets_str = implode(',', $sets);
        $stmt = $this->_db->prepare("update " . $this->reQuote($this->_table) . " set " . $sets_str . " where id=:id");
        foreach ($arr as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function getById($id, $lock = false)
    {
        $sql = "select * from " . $this->reQuote($this->_table) . " where id = :id";
        if ($lock) {
            $sql .= " for update";
        }
        if ($lock) {
            $stmt = $this->_db->prepare($sql, array(), 1);
        } else {
            $stmt = $this->_db->prepare($sql);
        }
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getByIds($ids)
    {
        $ids_str = implode(',', $ids);
        $stmt = $this->_db->prepare("select * from " . $this->reQuote($this->_table) . " where id in (" . $ids_str . ")");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function delete($id, $hard = 0)
    {
        if ($hard) {
            $stmt = $this->_db->prepare("delete from " . $this->reQuote($this->_table) . " where id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();

        } else {
            return $this->update($id, array('status' => -1));
        }
        return $stmt->rowCount();
    }

    public function getAll()
    {
        $stmt = $this->_db->prepare("select * from " . $this->reQuote($this->_table));
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function incr($id, $arr)
    {
        foreach ($arr as $k => $v) {
            $sets[] = $this->reQuote($k) . '=' . $k . "+" . $v;
        }
        $sets_str = implode(',', $sets);
        $stmt = $this->_db->prepare("update " . $this->reQuote($this->_table) . " set " . $sets_str . " where id=:id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->rowCount();
    }


    public function startBegin()
    {
        /* 开始事务 */
        $this->_db->beginTransaction();
    }

    public function rollBack()
    {
        $this->_db->rollback();
    }

    public function commit()
    {
        /* 提交事务 */
        $this->_db->commit();
    }

}
