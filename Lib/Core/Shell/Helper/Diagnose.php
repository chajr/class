<?php
/**
 * check that some system parts are working properly
 * by shell and browser
 *
 * @package     Core
 * @subpackage  Shell
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Shell_helper_Diagnose extends Core_Shell_Model_Abstract
{
    /**
     * @var bool
     */
    protected $_diagnosticFlag = TRUE;

    /**
     * @var Core_Blue_Model_Object
     */
    protected $_configuration;
    
    /**
     * set allow browser to true
     */
    protected function _beforeRun()
    {
        $this->_allowBrowser = TRUE;
    }

    /**
     * start diagnostic
     */
    public function run()
    {
        echo "\n";
        echo $this->_colorizeString('[System diagnostic]', 'blue_label');
        echo "\n";
        
        $this->_showCachedFiles();
        $this->_checkPaths();

        if ($this->_diagnosticFlag) {
            $this->_getConfiguration();
        }

        if ($this->_diagnosticFlag) {
            $this->_getModules();
        }

        if ($this->_diagnosticFlag) {
            $this->_getConnections();
        }

        if ($this->_diagnosticFlag) {
            $this->_showLogs();
        }

        //info about initialize modules
        //mozliwe definiowanie wlasnych klas diagnostycznych jako helpery modulow
        //sprawdza wtedy czy wlaczony modul posiada klase helper_diagnose i odpala
        
        
        $this->_showCachedFiles();
        $this->_checkFlag();
    }

    /**
     * show all log files
     */
    protected function _showLogs()
    {
        echo "\n";
        echo $this->_colorizeString('[System logs]', 'blue_label');
        echo "\n";

        $this->_readDirectory(LOG_PATH, 'log');
    }

    /**
     * show list of existing cache files
     */
    protected function _showCachedFiles()
    {
        echo "\n";
        echo $this->_colorizeString('[Cached files list]', 'blue_label');
        echo "\n";

        $this->_readDirectory(CORE_CACHE, 'cache');
    }

    /**
     * return list of files in current directory
     * 
     * @param string $path
     * @param string $type
     */
    protected function _readDirectory($path, $type)
    {
        if (class_exists('Core_Disc_Helper_Common')) {
            $cache = Core_Disc_Helper_Common::readDirectory($path);
            /** @var SplFileInfo $file */
            foreach ($cache as $file) {
                $modified = strftime('%d-%m-%Y %H:%M:%S', $file->getCTime());
                echo $file->getRealPath() . " [{$modified}]";
                echo "\n";
            }
        } else {
            $this->_errorMessage(
                "Core_Disc module not exists, cannot read $type directory",
                FALSE
            );
        }
    }

    /**
     * establish database connections
     */
    protected function _getConnections()
    {
        echo "\n";
        echo $this->_colorizeString(
            '[Database connections]',
            'blue_label'
        );
        echo "\n";

        $mysqlConnections = $this->_configuration->getDatabaseMysql()->getData();
        $this->_connectMysql($mysqlConnections);
    }

    /**
     * check that modules are enabled and exists on Lib directory
     */
    protected function _getModules()
    {
        echo "\n";
        echo $this->_colorizeString('[Config module list]', 'blue_label');
        echo "\n";

        foreach ($this->_configuration->getModules()->getData() as $module => $status) {
            $code       = Loader::code2name($module);
            $mod        = $code . ': ';
            $message    = '';

            if ($status === 'enabled') {
                $message .= $this-> _colorizeString(' [ENABLED]  ', 'green');
            } else {
                $message .= $this-> _colorizeString(' [DISABLED] ', 'red');
            }

            if (file_exists(CORE_LIB . Loader::name2path($code, FALSE))) {
                $message .= $this-> _colorizeString(' [EXISTS]     ', 'green');
            } else {
                $message .= $this-> _colorizeString(' [NOT EXISTS] ', 'red');
                $this->_diagnosticFlag = FALSE;
            }

            $version = $this->_configuration->getData($module)->getVersion();
            $message .= " [$version] \n";

            echo $this->formatOutput($message, $mod);
        }
    }

    /**
     * try to get configuration
     */
    protected function _getConfiguration()
    {
        echo "\n";
        echo $this->_colorizeString(
            '[Loading configuration] (will use cached configuration if exists)',
            'blue_label'
        );
        echo "\n";

        $this->_configuration = new Core_Blue_Model_Configuration();

        if ($this->_configuration->getData()) {
            $this->_okMessage('Configuration loaded');
        } else {
            $this->_errorMessage('Configuration was not loaded');
        }
    }

    /**
     * check that flag was true or false and show information
     */
    protected function _checkFlag()
    {
        if (!$this->_diagnosticFlag) {
            echo "\n";
            echo $this->_colorizeString(
                'Some configuration is wrong, other diagnose cannot be proceed',
                'red_label'
            );
            echo "\n";
        } else {
            echo "\n";
            echo $this->_colorizeString(
                'Diagnose finished',
                'green_label'
            );
            echo "\n";
        }
    }

    /**
     * check that path exists and some of them are writable
     */
    protected function _checkPaths()
    {
        echo "\n";
        echo $this->_colorizeString('[Check paths]', 'blue_label');
        echo "\n";

        $this->_checkPath(MAIN_PATH, 'MAIN_PATH');
        $this->_checkPath(LOG_PATH, 'LOG_PATH');
        $this->_checkPath(CORE_LIB, 'CORE_LIB');
        $this->_checkPath(CORE_CACHE, 'CORE_CACHE');
        $this->_checkPath(CORE_TEMP, 'CORE_TEMP');
        $this->_checkPath(CORE_SKIN, 'CORE_SKIN', FALSE);

        $this->_checkIsWritable(CORE_CACHE, 'CORE_CACHE');
        $this->_checkIsWritable(CORE_TEMP, 'CORE_TEMP');
        $this->_checkIsWritable(LOG_PATH, 'LOG_PATH');
    }

    /**
     * check single path exist
     * 
     * @param string $value
     * @param string $name
     * @param bool $flag
     */
    protected function _checkPath($value, $name, $flag = TRUE)
    {
        if (file_exists($value)) {
            $this->_okMessage($name . ' exists: ' . $value);
        } else {
            $this->_errorMessage($name . ' not exists exists: ' . $value, $flag);
        }
    }

    /**
     * check that directory is writable
     * 
     * @param string $value
     * @param string $name
     * @param bool $flag
     */
    protected function _checkIsWritable($value, $name, $flag = TRUE)
    {
        if (is_writable($value)) {
            $this->_okMessage($name. ' is writable');
        } else {
            $this->_errorMessage($name . ' is not writable', $flag);
        }
    }

    /**
     * show success message
     * 
     * @param string $message
     */
    protected function _okMessage($message) {
        echo $this->formatOutput(
            $message,
            $this->_colorizeString('[OK]', 'green')
        );
        echo "\n";
    }

    /**
     * show error message
     * 
     * @param string $message
     * @param bool $flag
     */
    protected function _errorMessage($message, $flag = TRUE) {
        echo $this->formatOutput(
            $message,
            $this->_colorizeString('[ERROR]', 'red')
        );
        echo "\n";

        if ($flag) {
            $this->_diagnosticFlag = FALSE;
        }
    }

    /**
     * initialize mysql connections
     *
     * @param array $mysqlConnections
     */
    protected function _connectMysql(array $mysqlConnections)
    {
        foreach ($mysqlConnections as $connection => $config) {
            try {
                $config['connection_name'] = $connection;

                /** @var Core_Db_Helper_Connection_Mysql $conn */
                $conn = Loader::getObject(
                    'Core_Db_Helper_Connection_Mysql',
                    $config,
                    'connection_mysql_' . $connection
                );

                if ($conn->err) {
                    $this->_errorMessage($conn->err);
                } else {
                    $this->_okMessage(
                        'Connection with: '
                        . $config['connection_name']
                        . ' - ' . $config['host']
                    );
                }
            } catch (Exception $e) {
                $this->_errorMessage($e->getMessage());
            }
        }
    }
}
