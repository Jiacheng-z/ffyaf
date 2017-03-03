<?php


class Com_Config
{
    /**
     * 获取配置
     * @param string $conf
     * @return mixed
     */
    static public function get($conf = "main")
    {
        static $_configs = [];

        if ($conf === "main") {
            return Yaf_Application::app()->getConfig();
        }

        $config_name = $conf . ".php";
        if (!isset($_configs[$config_name]) or empty($_configs[$config_name])) {
            $_configs[$config_name] = new Yaf_Config_Simple(include(CONFIG_PATH . $config_name));
        }

        return $_configs[$config_name];
    }

}