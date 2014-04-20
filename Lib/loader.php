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
     * allow to turn off events handling
     *
     * @var bool
     */
    public static $skipEvents = FALSE;

    /**
     * @var Core_Blue_Model_Configuration
     */
    protected static $_configuration;

    /**
     * @var Core_Blue_Model_Register
     */
    protected static $_register;

    /**
     * @var array
     */
    protected $_skipConstructorMethods = [
        'set_paths'     => TRUE,
        'check_tmp'     => TRUE,
        'set_register'  => TRUE,
        'load_config'   => TRUE,
        'init_modules'  => TRUE,
    ];

    /**
     * initialize framework
     * 
     * @param string $filePath
     * @param array $allowed
     */
    public function __construct($filePath, array $allowed = [])
    {
        $this->_skipConstructorMethods = array_merge(
            $this->_skipConstructorMethods,
            $allowed
        );

        try {
            $this->_setPaths($filePath);
            $this->_checkTemp();
            $this->_setRegister();
            $this->_loadConfiguration();
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
     * @return Loader 
     */
    protected function _setPaths($filePath)
    {
        if (!$this->_skipConstructorMethods['set_paths']) {
            return $this;
        }

        $main = dirname($filePath);
        define('MAIN_PATH', $main);
        define('LOG_PATH', $main . '/Lib/log/');
        define('CORE_LIB', $main . '/Lib/');
        define('CORE_CACHE', $main . '/Lib/cache/');
        define('CORE_TEMP', $main . '/Lib/tmp/');

        return $this;
    }

    /**
     * check that tmp directory exists
     * and create it if not
     * 
     * @return Loader
     */
    protected function _checkTemp()
    {
        if (!$this->_skipConstructorMethods['check_tmp']) {
            return $this;
        }

        if (!file_exists(CORE_TEMP)) {
            @mkdir(CORE_TEMP, 0777);
        }

        return $this;
    }

    /**
     * initialize modules if initialize class exists
     * 
     * @return Loader
     */
    protected function _initModules()
    {
        if (!$this->_skipConstructorMethods['init_modules']) {
            return $this;
        }

        if (self::getConfiguration()->getCore()->getInitialize() === 'disabled') {
            return $this;
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

        return $this;
    }

    /**
     * load all configuration for enabled modules
     * 
     * @return Loader
     */
    protected function _loadConfiguration()
    {
        if (!$this->_skipConstructorMethods['load_config']) {
            return $this;
        }

        self::$_configuration = new Core_Blue_Model_Configuration();

        return $this;
    }

    /**
     * create register object to collect all instances
     */
    protected function _setRegister()
    {
        if (!$this->_skipConstructorMethods['set_register']) {
            return $this;
        }

        self::$_register = new Core_Blue_Model_Register();

        return $this;
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
     * convert module name to module code
     *
     * @param string $module
     * @return string
     */
    static function name2code($module)
    {
        return implode('_', array_map('strtolower', explode('_', $module)));
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
        Loader::tracer('class loaded', debug_backtrace(), '008e85');

        $classPath      = self::name2path($class);
        $module         = self::name2module($class);
        $fullPath       = CORE_LIB . $classPath;
        $fileExist      = file_exists($fullPath);
        $moduleExist    = TRUE;
        $instance       = self::$_configuration instanceof Core_Blue_Model_Configuration;
        $coreModule     = $module !== 'core_blue';

        if ($coreModule && $instance) {
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
     * @todo configuration check that module for class is enabled
     */
    static function getObject($class, $args = [], $instanceName = NULL)
    {
        Loader::tracer('get object', debug_backtrace(), '006c94');

        $name = $class;
        if ($instanceName) {
            $name = $instanceName;
        }

        $instanceCode   = self::name2code($name);
        $instance       = self::$_register->getData($instanceCode);

        if (!$instance) {
            try {
                $instance = self::$_register->setObject($class, $instanceCode, $args);
            } catch (Exception $e) {
                self::exceptions($e);
            }
        }

        return $instance;
    }

    /**
     * return list of all objects stored in register
     * 
     * @return array
     */
    static function getRegisteredObjects()
    {
        return self::$_register->getRegisteredObjects();
    }

    /**
     * try to create new object and return it
     * 
     * @param string $name
     * @param array $args
     * @return mixed
     * @todo configuration check that module for class is enabled
     */
    static function getClass($name, $args = [])
    {
        Loader::tracer('create object', debug_backtrace(), '008e85');

        try {
            return new $name($args);
        } catch (Exception $e) {
            self::exceptions($e);
        }
    }

    /**
     * handle exception message
     * 
     * @param Exception $exception
     * @param string $title
     * @param string $type
     */
    static function exceptions(Exception $exception, $title = 'exception', $type = 'exception')
    {
        Loader::tracer($title, debug_backtrace(), '900000');
        self::log($type, $exception, $title);
    }

    /**
     * log message to specific file
     * 
     * @param string $type
     * @param string|array $message
     * @param string $title
     */
    static function log($type, $message, $title)
    {
        Loader::tracer('create log information', debug_backtrace(), '6d6d6d');

        if (is_array($message)) {
            $information = '';
            foreach ($message as $key => $value) {
                $information .= "- $key: $value\n";
            }
            $message = $information;
        } else {
            $message .= "\n";
        }

        $time       = strftime('%H:%M:%S - %d-%m-%Y');
        $logFile    = $type . '.log';
        $logPath    = LOG_PATH . $logFile;
        $title      = strtoupper($title);
        $format     = "$title - [$time]:\n$message----------------------\n\n";

        if (!is_dir(LOG_PATH)) {
            @mkdir(LOG_PATH);
            @chmod(LOG_PATH, 0777);
        }

        if (!file_exists($logPath)) {
            @file_put_contents($logPath, '');
            @chmod($logPath, 0777);
        }

        file_put_contents($logPath, $format, FILE_APPEND);
    }

    /**
     * alias for Core_Benchmark_Helper_Tracer::marker
     * with checking that class exists
     * 
     * @param $message
     * @param $debugBacktrace
     * @param $color
     */
    static function tracer($message, $debugBacktrace = NULL, $color = '000000')
    {
        if (!self::$_configuration) {
            return;
        }

        $classExists    = class_exists('Core_Benchmark_Helper_Tracer');
        $useTracer      = self::$_configuration->getCore()->getTracer();

        if ($classExists && $useTracer) {
            Core_Benchmark_Helper_Tracer::marker([
                $message,
                $debugBacktrace,
                '#' . $color
            ]);
        }
    }

    /**
     * create event
     * create event observer in ini file in that model event_code[class_name] = method
     * 
     * @param string $name
     * @param mixed $data
     */
    static function callEvent($name, $data = [])
    {
        if (self::$skipEvents) {
            return;
        }

        /** @var Core_Blue_Model_Object $events */
        $events = Loader::getConfiguration()->getEvents();

        if ($events) {
            foreach ($events->getData($name) as $class => $method) {
                $object = self::getObject($class);

                try {
                    $object->$method($data);
                } catch (Exception $e) {
                    self::exceptions($e);
                }
            }
        }
    }
}