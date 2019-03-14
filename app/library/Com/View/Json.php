<?php

class Com_View_Json extends Com_Abstract_View
{

    /**
     * @param string $tpl
     * @param array $var_array
     * @return string
     *
     * {
     *  "code":0,
     *  "desc":"success",
     *  "content":...,
     * }
     */
    public function render($tpl, $var_array = array())
    {
        if (!is_null($tpl)) {
            $tpl = str_replace('.phtml', '.php', $tpl);
            $tpl = $this->path . $tpl;
        }

        $ret = [];
        if (file_exists($tpl)) {
            $ret = include($tpl);
        } else {
            $ret = [
                "code" => ((!isset($this->values["code"])) ? 0 : $this->values["code"]),
                "desc" => ((!isset($this->values["desc"])) ? "success" : $this->values["desc"]),
                "content" => ((!isset($this->values["content"])) ? "" : $this->values["content"]),
            ];
        }

        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }
}

?>
