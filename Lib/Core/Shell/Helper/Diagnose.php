<?php
/**
 * check that some system parts are working properly
 * by shell and browser
 *
 * @package     Core
 * @subpackage  Shell
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Shell\Helper;
use Core\Shell\Model\ModelAbstract;
use Core\Blue\Model;
use Core\Disc\Helper\Common;
use Core\Db\Helper\Mysql;
use Loader;
use Exception;
use SplFileinfo;
class Diagnose extends ModelAbstract
{
    /**
     * @var bool
     */
    protected $_diagnosticFlag = TRUE;

    /**
     * @var Model\Object
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
            new Loader('', [
                'check_tmp'     => FALSE,
                'init_modules'  => FALSE,
                'set_paths'     => FALSE
            ]);
            $this->_initModules();
        }

        if ($this->_diagnosticFlag) {
            $this->_getConnections();
        }

        $this->_lunchCustomDiagnostic();
        $this->_showLogs();
        $this->_showCachedFiles();
        $this->_checkFlag();
    }

    /**
     * lunch diagnostic classes defined for module
     */
    protected function _lunchCustomDiagnostic()
    {
        if (!$this->_configuration) {
            return;
        }

        echo "\n";
        echo $this->_colorizeString('[Custom diagnostic]', 'cyan_label');
        echo "\n";

        foreach ($this->_configuration->getModules()->getData() as $module => $status) {
            if ($status !== 'enabled') {
                continue;
            }

            $moduleName     = Loader::code2name($module);
            $libraryName    = $moduleName . '_Test_Diagnose';
            $libraryPath    = CORE_LIB . Loader::name2path($libraryName, FALSE);

            if (file_exists($libraryPath)) {
                Loader::getClass($libraryName);
            }
        }

        echo "\n";
        echo $this->_colorizeString('[End custom diagnostic]', 'cyan_label');
        echo "\n";
    }

    /**
     * try to initialize modules
     */
    protected function _initModules()
    {
        echo "\n";
        echo $this->_colorizeString('[Initialize modules]', 'blue_label');
        echo "\n";

        if ($this->_configuration->getCore()->getInitialize() !== 'enabled') {
            $this->_errorMessage('Initialize is disabled in configuration');
            return;
        }

        foreach ($this->_configuration->getModules()->getData() as $module => $status) {
            if ($status !== 'enabled') {
                continue;
            }

            $initStatus = $this->_configuration->getData($module)->getInitialize();
            $moduleName = Loader::code2name($module);
            $modulePath = Loader::name2path($moduleName, FALSE);
            $path       = CORE_LIB . $modulePath . '/Initialize.php';
            $exists     = file_exists($path);
            $initialize = $initStatus === 'enabled';

            if (!$initialize) {
                $this->_warningMessage("Module $moduleName initialization is disabled");
                continue;
            }

            if (!$exists) {
                $this->_warningMessage("Module $moduleName initialization file not exists");
                continue;
            }

            if ($exists && $initialize) {
                try {
                    Loader::getObject($moduleName . '_Initialize');
                    $this->_successMessage("Module $moduleName was initialized");
                } catch (Exception $e) {
                    $this->_errorMessage($moduleName . ': ' . $e->getMessage());
                }
            }
        }
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
        if (class_exists('Core\Disc\Helper\Common')) {
            $cache = Common::readDirectory($path);
            /** @var SplFileInfo $file */
            foreach ($cache as $file) {
                $modified = strftime('%d-%m-%Y %H:%M:%S', $file->getCTime());
                echo $file->getRealPath() . " [{$modified}]";
                echo "\n";
            }
        } else {
            $this->_errorMessage(
                "Core\\Disc module not exists, cannot read $type directory",
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
        $pdoConnections   = $this->_configuration->getDatabasePdo()->getData();

        $this->_connect($mysqlConnections, 'mysql');
        $this->_connect($pdoConnections, 'pdo');

        if ($this->_configuration->getCoreDb()->getConnectMysql() === 'enabled') {
            $this->_databaseAccess('Mysql');
        }

        if ($this->_configuration->getCoreDb()->getConnectPdo() === 'enabled') {
            $this->_databaseAccess('Pdo_Mysql');
        }
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

        $this->_configuration = new Model\Configuration();

        if ($this->_configuration->getData()) {
            $this->_successMessage('Configuration loaded');
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
            $this->_successMessage($name . ' exists: ' . $value);
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
            $this->_successMessage($name. ' is writable');
        } else {
            $this->_errorMessage($name . ' is not writable', $flag);
        }
    }

    /**
     * show warning message
     *
     * @param string $message
     */
    protected function _warningMessage($message) {
        echo $this->formatOutput(
            $message,
            $this->_colorizeString('[WARNING]', 'brown')
        );
        echo "\n";
    }

    /**
     * show success message
     * 
     * @param string $message
     */
    protected function _successMessage($message) {
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
     * initialize database connection
     *
     * @param array $connections
     * @param string $type
     */
    protected function _connect(array $connections, $type)
    {
        foreach ($connections as $connection => $config) {
            try {
                $config['connection_name'] = $connection;

                $conn = Loader::getObject(
                    'Core_Db_Helper_Connection_' . ucfirst($type),
                    $config
                );

                if ($conn->err) {
                    $this->_errorMessage($conn->err);
                } else {
                    $this->_successMessage(
                        $type
                        . ' connection with: '
                        . $config['connection_name']
                        . ' - ' . $config['host']
                    );
                }
            } catch (Exception $e) {
                $this->_errorMessage($e->getMessage());
            }
        }
    }

    /**
     * try to execute simple query to database and get number of tables
     * 
     * @param string $type
     */
    protected function _databaseAccess($type)
    {
        echo "\n";
        echo $this->_colorizeString(
            '[Database access]',
            'blue_label'
        );
        echo "\n";

        /** @var Mysql $query */
        $query = Loader::getClass('Core\Db\Helper\\' . $type, [
            'sql' => 'SHOW TABLES'
        ]);

        if ($query->err) {
            $this->_errorMessage($query->err);
            return;
        }

        $this->_successMessage(
            "Query returns: {$query->rows} tables from database ($type)"
        );
    }
}
