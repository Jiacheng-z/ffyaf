<?php

class Yours_Example
{
    const CACHE_POOL = 'example_cache';


    public static function getExampleWithCacheDb($id)
    {
        $gc = function () use ($id) {
            $c = new Cache_Example(self::CACHE_POOL);
            return $c->getExample($id);
        };
        $sc = function ($data) use ($id) {
            $c = new Cache_Example(self::CACHE_POOL);
            $c->setExample($id, $data);
        };
        $dc = function () use ($id) {
            $model = new ExampleModel();
            return $model->getFetch($id);
        };

        $key = 'only_one:' . $id;
        return Util_Tool::cacheProcess($key, $gc, $sc, $dc);
    }

    public static function modelTransaction($id, $up)
    {
        $pool = new ExampleModel(Com_Model_Pool::MODEL_M);
        $pool->beginTransaction();

        try {
            $pool->updateEx($id, $up);
            $pool->commit();
        } catch (Exception $e) {
            $pool->rollBack();
            return false;
        }

        return true;
    }
}