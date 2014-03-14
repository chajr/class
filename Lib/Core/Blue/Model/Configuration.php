<?php
/**
 * Read module configuration
 *
 * @package     Core
 * @subpackage  Blue
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Blue_Model_Configuration extends Core_Blue_Model_Object
{
    /**
     * read all configurations, merge them and set as DATA
     */
    public function __construct()
    {
        $coreConfig     = $this->_loadModuleConfiguration('Core');
        $otherConfig    = $this->_loadEnabledModulesConfiguration($coreConfig['modules']);
        $mainConfig     = array_merge($coreConfig, $otherConfig);

        parent::__construct($mainConfig);
        $newData = $this->traveler('_convertToObject');
        $this->setData($newData);
    }

    /**
     * get configuration for single module
     * 
     * @param string $module
     * @return array
     */
    protected function _loadModuleConfiguration($module)
    {
        $module = Loader::code2name($module);
        try {
            $mainConfigPath = $this->_getConfigPaths($module);
            $mainConfig     = $this->_readConfiguration($mainConfigPath, $module);

            return $mainConfig;
        } catch (Exception $e) {
            Loader::exceptions($e);
        }

        return [];
    }

    /**
     * return path for module configuration file
     * 
     * @param string $module
     * @return string
     */
    protected function _getConfigPaths($module)
    {
        if ($module === 'Core') {
            $mainConfigPath = CORE_LIB . 'config.ini';
        } else {
            $libPath = Loader::name2path($module, FALSE);
            $mainConfigPath = CORE_LIB . $libPath . '/etc/config.ini';
        }

        return $mainConfigPath;
    }

    /**
     * read and parse module configuration file
     * 
     * @param string $mainConfigPath
     * @param string $module
     * @return array|boolean
     * @throws Exception
     */
    protected function _readConfiguration($mainConfigPath, $module)
    {
        if (file_exists($mainConfigPath)) {
            $mainConfig = parse_ini_file($mainConfigPath, TRUE);
        } else {
            throw new Exception (
                'Missing ' . $module . ' configuration: ' . $mainConfigPath
            );
        }

        return $mainConfig;
    }

    /**
     * load configuration for all enabled modules
     * 
     * @param array $modules
     * @return array
     */
    protected function _loadEnabledModulesConfiguration($modules)
    {
        $modulesConfiguration = [];
        foreach ($modules as $moduleName => $enabled) {
            if ($enabled === 'enabled') {
                $configuration = $this->_loadModuleConfiguration($moduleName);
                $modulesConfiguration = array_merge($modulesConfiguration, $configuration);
            }
        }

        return $modulesConfiguration;
    }

    /**
     * convert first level arrays to blue objects
     * 
     * @param string $key
     * @param mixed $data
     * @return Core_Blue_Model_Object
     */
    protected function _convertToObject($key, $data)
    {
        if (is_array($data)) {
            return new Core_Blue_Model_Object($data);
        }

        return $data;
    }
}