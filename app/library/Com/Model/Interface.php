<?php


interface Com_Model_Interface
{
    /**
     * 单条添加
     * @param $table
     * @param array $arr
     * @return bool|string
     */
    public function add($table, array $arr);

    /**
     * 更新单条数据
     * @param string $table
     * @param int $id
     * @param array $arr
     * @return int
     */
    public function update($table, $id, array $arr);

    /**
     * 获取单条数据
     * @param string $table
     * @param int $id
     * @param bool $lock
     * @return mixed
     */
    public function getById($table, $id, $lock = false);

    /**
     * 根据id array获取多条数据
     * @param string $table
     * @param array $ids
     * @return array
     */
    public function getByIds($table, array $ids);

    /**
     * 删除单条数据
     * @param string $table
     * @param int $id
     * @param bool $hard
     * @return int
     */
    public function delete($table, $id, $hard = false);

    /**
     * 获取表中全部数据
     * @param $table
     * @param $filter_status
     * @return array
     */
    public function getAll($table,$filter_status = false);
}