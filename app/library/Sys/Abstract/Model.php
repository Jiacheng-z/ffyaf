<?php

abstract class Sys_Abstract_Model extends Singleton
{
    private $cachePrefix = "cache";
    private $gCachePrefix = "gCache";
    private $yCachePrefix = "yCache";
    private $delCachePrefix = "delCache";

    public function __call($strMethod, $arrParam)
    {
        $cacheName = null;
        $methodName = null;

        $tmp = explode('_', $strMethod, 2);
        if (count($tmp) == 2) {
            $cacheName = $tmp[0];
            $methodName = $tmp[1];
        }

        if ($cacheName && $methodName && method_exists($this, $cacheName)) {
            switch ($cacheName) {
                case $this->cachePrefix:
                    return $this->cache($methodName, $arrParam);
                    break;
                case $this->gCachePrefix:
                    return $this->gCache($methodName, $arrParam);
                    break;
                case $this->yCachePrefix:
                    return $this->yCache($methodName, $arrParam);
                    break;
                case $this->delCachePrefix:
                    return $this->decache($methodName, $arrParam);
                    break;
                default:
                    break;
            }
        }
        throw new Exception(get_class($this) . "::{$strMethod} not defined");
    }

    public function cache($strMethod, $arrParam, $cache_time)
    {
        $memcache = CMemcache::getInstance();

        $mainConfig = Yaf_Application::app()->getConfig();
        if (!$mainConfig->enableCache) {
            $cacheRet = call_user_func_array(array($this, $strMethod), $arrParam);
        } else {
            $paramKey = "";
            foreach ($arrParam as $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $paramKey .= '_' . $value;
            }
            $paramMd5 = md5($paramKey);
            $cacheKey = $mainConfig->cache_prefix . get_called_class() . '_' . $strMethod . '_' . $paramMd5;

            $cacheRet = $memcache->get($cacheKey);
            if ($cacheRet === false) {
                $cacheRet = call_user_func_array(array($this, $strMethod), $arrParam);
                $cache_time = $cache_time ? $cache_time * 60 : 300;
                $memcache->set($cacheKey, $cacheRet, 0, $cache_time);
            } elseif ($cacheRet === '') {
                return false;
            }
        }
        return $cacheRet;
    }

    public function gCache($strMethod, $arrParam)
    {
        $paramKey = "";
        foreach ($arrParam as $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $paramKey .= '_' . $value;
        }
        $paramMd5 = md5($paramKey);
        $cacheKey = get_called_class() . '_' . $strMethod . '_' . $paramMd5;

        $cacheRet = Yaf_Registry::get($cacheKey);
        if (!isset($cacheRet)) {
            $cacheRet = call_user_func_array(array($this, $strMethod), $arrParam);
            Yaf_Registry::set($cacheKey, $cacheRet);
        }

        return $cacheRet;
    }

    public function decache($strMethod, $arrParam)
    {
        $memcache = CMemcache::getInstance();

        $mainConfig = Yaf_Application::app()->getConfig();
        $paramKey = "";
        foreach ($arrParam as $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $paramKey .= '_' . $value;
        }
        $paramMd5 = md5($paramKey);
        $cacheKey = $mainConfig->cache_prefix . get_called_class() . '_' . $strMethod . '_' . $paramMd5;

        $memcache->delete($cacheKey);
    }



    public function yCache($strMethod, $arrParam, $cache_time)
    {
        $yac = CYac::getInstance();

        $mainConfig = Yaf_Application::app()->getConfig();
        if (!$mainConfig->enableCache) {
            $cacheRet = call_user_func_array(array($this, $strMethod), $arrParam);
        } else {
            $paramKey = "";
            foreach ($arrParam as $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $paramKey .= '_' . $value;
            }
            $paramMd5 = md5($paramKey);
            $cacheKey = md5($mainConfig->cache_prefix . get_called_class() . '_' . $strMethod . '_' . $paramMd5);

            $cacheRet = $yac->get($cacheKey);
            if ($cacheRet === false) {
                if ($cacheRet = call_user_func_array(array($this, $strMethod), $arrParam)) {
                    $cache_time = $cache_time ? $cache_time * 60 : 150;
                    $res = $yac->set($cacheKey, $cacheRet, $cache_time);
                }
            } elseif ($cacheRet === '') {
                return false;
            }
        }
        return $cacheRet;
    }

    public function reQuote($v)
    {
        return '`' . $v . '`';
    }

    public function add($arr)
    {
        if (!isset($arr['create_time'])) {
            $arr['create_time'] = date('Y-m-d H:i:s', time());
        }
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
