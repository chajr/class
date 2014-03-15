<?php
/**
 * allow to automatic include classes
 */
spl_autoload_register('Loader::loadClass');

/**
 * Main loader class, responsible for loading module configuration and single classes
 *
 * @category    Loader
 * @author      chajr <chajr@bluetree.pl>
 */
class Loader
{
    /**
     * @var Core_Blue_Model_Configuration
     */
    protected static $_configuration;

    /**
     * @var Core_Blue_Model_Register
     */
    protected static $_register;

    /**
     * initialize framework
     * 
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        try {
            $this->_setPaths($filePath);
            $this->_loadConfiguration();
            $this->_setRegister();
            $this->_initModules();
        } catch (Exception $e) {
            self::exceptions($e);
        }
    }

    /**
     * set constants with main path to index.php, framework files etc
     * basically as argument use __FILE__
     * 
     * @param string $filePath
     */
    protected function _setPaths($filePath)
    {
        $main = dirname($filePath);
        define('MAIN_PATH', $main);
        define('LOG_PATH', $main . '/log/');
        define('CORE_LIB', $main . '/Lib/');
    }

    /**
     * initialize modules if initialize class exists
     */
    protected function _initModules()
    {
        if (self::getConfiguration()->getConfiguration()->getInitialize() === 'disabled') {
            return;
        }

        $modules = array_keys(self::$_configuration->getModules()->getData());

        foreach ($modules as $module) {
            $initialize = Loader::getConfiguration()->getData($module)->getInitialize();
            $modulePath = self::name2path(Loader::code2name($module), FALSE);
            $path       = CORE_LIB . $modulePath . '/Initialize.php';
            $loadClass  = file_exists($path) && $initialize !== '';

            if ($loadClass) {
                self::getObject(self::code2name($module) . '_Initialize');
            }
        }
    }

    /**
     * load all configuration for enabled modules
     */
    protected function _loadConfiguration()
    {
        self::$_configuration = new Core_Blue_Model_Configuration();
    }

    /**
     * create register object to collect all instances
     */
    protected function _setRegister()
    {
        self::$_register = new Core_Blue_Model_Register();
    }

    /**
     * return configuration object
     * 
     * @return Core_Blue_Model_Configuration
     */
    static function getConfiguration()
    {
        return self::$_configuration;
    }

    /**
     * convert class name to path
     * 
     * @param string $name
     * @param boolean $php
     * @return string
     */
    static function name2path ($name, $php = TRUE)
    {
        $path = str_replace('_', '/', $name);
        if ($php) {
            $path .= '.php';
        }
        return $path;
    }

    /**
     * convert module code to module name
     * 
     * @param string $module
     * @return string
     */
    static function code2name($module)
    {
        return implode('_', array_map('ucfirst', explode('_', $module)));
    }

    /**
     * get package and module name form class name
     * 
     * @param string $name
     * @param bool $toLower
     * @return bool|string
     */
    static function name2module($name, $toLower = TRUE)
    {
        $part = explode('_', $name);

        if (count($part) < 2) {
            return FALSE;
        } else {
            if ($toLower) {
                $part[0] = strtolower($part[0]);
                $part[1] = strtolower($part[1]);
            }
            return $part[0] . '_' . $part[1];
        }
    }

    /**
     * load class for enabled module
     * 
     * @param string $class
     * @throws Exception
     */
    static function loadClass($class)
    {
        $classPath      = self::name2path($class);
        $module         = self::name2module($class);
        $fullPath       = CORE_LIB . $classPath;
        $fileExist      = file_exists($fullPath);
        $moduleExist    = TRUE;

        if ($module !== 'core_blue') {
            $modules        = self::$_configuration->getModules()->getData();
            $moduleExist    = isset($modules[$module]) && $modules[$module] === 'enabled';
        }

        if ($moduleExist && $fileExist) {
            include_once $fullPath;
        } else {
            throw new Exception ('Class file is missing: ' . $fullPath);
        }
    }

    /**
     * return object instance, or create it with sets of arguments
     * optionally when create at instance give an instance name to take by that name instead of class name
     * 
     * @param string $class
     * @param array $args
     * @param null|string $instanceName
     * 
     * @return object;
     */
    static function getObject($class, $args = [], $instanceName = NULL)
    {
        $name = $class;
        if ($instanceName) {
            $name = $instanceName;
        }

        $instanceCode   = strtolower($name);
        $instance       = self::$_register->getData($instanceCode);

        if (!$instance) {
            try {
                $instance = self::$_register->setObject($class, $name, $args);
            } catch (Exception $e) {
                self::exceptions($e);
            }
        }

        return $instance;
    }

    /**
     * try to create new object and return it
     * 
     * @param string $name
     * @param array $args
     * @return mixed
     */
    static function getClass($name, $args = [])
    {
        try {
            return new $name($args);
        } catch (Exception $e) {
            self::exceptions($e);
        }
    }
    
    static function exceptions($exception)
    {
        
    }

    static function log()
    {
        
    }
}