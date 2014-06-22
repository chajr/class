<?php
/**
 * Read module configuration
 *
 * @package     Core
 * @subpackage  Blue
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Blue\Model;
use Loader;
use Exception;
class Configuration extends Object
{
    /**
     * read all configurations, merge them and set as DATA
     */
    public function __construct()
    {
        $mainConfig = $this->_configCache();

        if (!$mainConfig) {
            $coreConfig     = $this->_loadModuleConfiguration('Core');
            $otherConfig    = $this->_loadEnabledModulesConfiguration($coreConfig['modules']);
            $mainConfig     = array_merge_recursive($coreConfig, $otherConfig);
            $this->_configCache($mainConfig);
        }

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
            $mainConfig     = $this->_getConfiguration($module);
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
     * @throws Exception
     */
    protected function _getConfiguration($module)
    {
        if ($module === 'Core') {
            $basePath = CORE_LIB . 'config.';
        } else {
            $libPath = Loader::name2path($module, FALSE);
            $basePath = CORE_LIB . $libPath . '/etc/config.';
        }

        $ini    = $basePath . 'ini';
        $json   = $basePath . 'json';
        $php    = $basePath . 'php';

        switch (TRUE) {
            case file_exists($ini):
                return parse_ini_file($ini, TRUE);

            case file_exists($json):
                $data = file_get_contents($json);
                return json_decode($data, TRUE);

            case file_exists($php):
                return file_get_contents($php, TRUE);

            default:
                throw new Exception (
                    'Missing ' . $module
                    . " configuration:\n    $ini\n    $json\n    $php"
                );
                break;
        }
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
                $modulesConfiguration = array_merge_recursive($modulesConfiguration, $configuration);
            }
        }

        return $modulesConfiguration;
    }

    /**
     * convert first level arrays to blue objects
     * 
     * @param string $key
     * @param mixed $data
     * @return Object
     */
    protected function _convertToObject($key, $data)
    {
        if (is_array($data)) {
            return new Object($data);
        }

        return $data;
    }

    /**
     * return cached configuration or save it to cache file
     * 
     * @param null|mixed $data
     * @return bool|void
     */
    protected function _configCache($data = NULL)
    {
        /** @var Cache $cache */
        $cache = Loader::getObject('Core\Blue\Model\Cache');
        if ($data) {
            $readyData = serialize($data);
            return $cache->setCache('main_configuration', $readyData);
        } else {
            return unserialize($cache->getCache('main_configuration'));
        }
    }
}