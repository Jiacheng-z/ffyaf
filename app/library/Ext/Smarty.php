<?php
/**
 * Smarty.php
 *
 * @author  Laruence
 * @date    2010-08-01 14:15
 * @version $Id$
 */
ini_set("yaf.use_spl_autoload", "on");
Yaf_Loader::import(APPLICATION_PATH . "/extensions/smarty/Smarty.class.php");
ini_set("yaf.use_spl_autoload", "off");

class Ext_Smarty extends Com_Abstract_View
{
    /**
     * Smarty object
     * @var Smarty
     */
    public $_smarty;

    /**
     * Ext_Smarty constructor.
     * @param null $tmplPath
     * @param array $extraParams
     * @throws Exception
     */
    public function __construct($tmplPath = null, $extraParams = array())
    {
        ini_set("yaf.use_spl_autoload", "on");

        $this->_smarty = new Smarty;
        if (null !== $tmplPath) {
            $this->setScriptPath($tmplPath);
        }

        foreach ($extraParams as $key => $value) {
            $this->_smarty->$key = $value;
        }

        ini_set("yaf.use_spl_autoload", "off");
    }

    /**
     * Return the template engine object
     *
     * @return Smarty
     */
    public function getEngine()
    {
        return $this->_smarty;
    }

    /**
     * Set the path to the templates
     *
     * @param $path
     * @throws Exception
     */
    public function setScriptPath($path)
    {
        if (is_readable($path)) {
            $this->_smarty->template_dir = $path;
            return;
        }

        throw new Exception('Invalid path provided');
    }

    /**
     * Retrieve the current template directory
     *
     * @return array|string
     */
    public function getScriptPath()
    {
        return $this->_smarty->template_dir;
    }

    /**
     * Alias for setScriptPath
     * @param $path
     * @param string $prefix
     * @throws Exception
     */
    public function setBasePath($path, $prefix = 'Zend_View')
    {
        return $this->setScriptPath($path);
    }

    /**
     * Alias for setScriptPath
     *
     * @param $path
     * @param string $prefix
     * @throws Exception
     */
    public function addBasePath($path, $prefix = 'Zend_View')
    {
        return $this->setScriptPath($path);
    }

    /**
     * Assign a variable to the template
     *
     * @param string $key The variable name.
     * @param mixed $val The variable value.
     * @return void
     */
    public function __set($key, $val)
    {
        $this->_smarty->assign($key, $val);
    }

    /**
     * Allows testing with empty() and isset() to work
     *
     * @param string $key
     * @return boolean
     */
    public function __isset($key)
    {
        return (null !== $this->_smarty->get_template_vars($key));
    }

    /**
     * Allows unset() on object properties to work
     *
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        $this->_smarty->clear_assign($key);
    }

    /**
     * Assign variables to the template
     *
     * Allows setting a specific key to the specified value, OR passing
     * an array of key => value pairs to set en masse.
     *
     * @see __set()
     * @param string|array $spec The assignment strategy to use (key or
     * array of key => value pairs)
     * @param mixed $value (Optional) If assigning a named variable,
     * use this as the value.
     * @return void
     */
    public function assign($spec, $value = null)
    {
        if (is_array($spec)) {
            $this->_smarty->assign($spec);
            return;
        }

        $this->_smarty->assign($spec, $value);
    }

    /**
     * Clear all assigned variables
     *
     * Clears all variables assigned to Zend_View either via
     * {@link assign()} or property overloading
     * ({@link __get()}/{@link __set()}).
     *
     * @return void
     */
    public function clearVars()
    {
        $this->_smarty->clear_all_assign();
    }

    /**
     * Processes a template and returns the output.
     *
     * @param $name
     * @param null $value
     * @return string|void
     * @throws SmartyException
     */
    public function render($name, $value = null)
    {
        ini_set("yaf.use_spl_autoload", "on");
        $name = str_replace('.phtml', '.tpl', $name);
        $tpl = $this->_smarty->fetch($name);
        ini_set("yaf.use_spl_autoload", "off");
        return $tpl;
    }

    public function display($name, $value = null)
    {
        Yaf_Dispatcher::getInstance()->autoRender(false);
        echo $this->render($name, $value);
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
?>
