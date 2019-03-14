<?php

/**
 * 分页类
 * Class Util_Page
 *
 * Typical Usage:
 * @code
 * list($sPage, $sLimit) = Util_Page::limitHelperBegin($page, $limit, $cache_limit);
 * return Util_Page::limitHelperEnd($data, $data_count, $page, $limit, $cache_limit);
 *
 */
class Util_Page
{

    public static function cut($allList, $page = 0, $limit)
    {
        if ($limit === 0) {
            return [$allList, 0, 0];
        }

        $list = [];
        $count = count($allList);

        $start = $page * $limit;

        for ($i = 0; $i < ($start + $limit); $i++) {

            if ($i < $start) {
                continue;
            }

            if (isset($allList[$i])) {
                $list[($i + 1)] = $allList[$i];
            }
        }

        return [array_values($list), $count, ceil($count / $limit)];
    }

    /**
     * 分页帮助函数: 起始
     *
     * @param int $page 当前页码 (起始页码: 0)
     * @param int $limit 一页数量
     * @param int $cacheLimit 缓存条目数量
     * @return array
     *
     * for instance:
     *  limit = 3, cacheLimit = 6: 前2页都会被缓存,  2页之后的数据再分开按照单页缓存.
     *  limit = cacheLimit: 不缓存多页. 只是单页缓存.
     *
     * return:
     *  getPage: 数据查询时使用的页码
     *  getLimit: 数据查询时一页的数量
     */
    public static function limitHelperBegin($page = 0, $limit = 0, $cacheLimit = 0)
    {
        $searchPage = $page;
        $searchLimit = $limit;
        if ($limit != 0 and (($page + 1) * $limit) <= $cacheLimit) {
            $searchPage = 0;
            $searchLimit = $cacheLimit;
        }

        return [$searchPage, $searchLimit];
    }

    /**
     * 分页帮助函数: 结束
     *
     * @param array $data   数据数组
     * @param int $count    数据个数(真正的个数)
     * @param int $page 当前页码
     * @param int $limit 一页数量
     * @param int $cacheLimit 缓存条目数量
     * @return array
     */
    public static function limitHelperEnd($data, $count, $page = 0, $limit = 0, $cacheLimit = 0)
    {
        $sumPage = ceil($count / $limit);
        if ($limit != 0 and (($page + 1) * $limit) <= $cacheLimit) {
            list($cutData, $c, $p) = self::cut($data, $page, $limit);
            return [$cutData, $count, $sumPage];
        }

        return [$data, $count, $sumPage];
    }
}