<?php


class Url
{
    /**
     * 重写规则列表
     * @var array
     */
    private static $rules = [
        'example' => 'example',
    ];

    public static function exampleUrl($id)
    {
        return self::rewriteUrl(self::$rules['example'], 'index', ['id' => $id]);
    }


    private static function buildGet($baseUrl, array $get)
    {
        $get = http_build_query($get);
        if (!empty($get)) {
            $baseUrl .= '?' . $get;
        }

        return $baseUrl;
    }

    /**
     * url重写规则
     * @param $rule
     * @param $url
     * @param array $get
     * @return string
     */
    private static function rewriteUrl($rule, $url, $get = [])
    {
        $rewrite = Com_Config::get()->urlRewrite;
        if ($rewrite == false) {
            return self::buildGet($url, $get);
        }

        $newUrl = '';
        switch ($rule) {
            case self::$rules['example']:
                $newUrl .= 'example' . $get['id'] . '.html';
                unset($get['id']);
                break;

            default:
                $newUrl .= $url;
                break;
        }

        return self::buildGet($newUrl, $get);
    }

}