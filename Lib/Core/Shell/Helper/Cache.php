<?php
/**
 * allow to remove cached files
 * by shell and browser
 *
 * @package     Core
 * @subpackage  Shell
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Shell_Helper_Cache extends Core_Shell_Model_Abstract
{
    const NONE          = 0;
    const CONFIGURATION = 1;
    const DATABASE      = 2;
    const TEMPLATES     = 4;
    const MISC          = 8;
    const ALL           = 15;

    /**
     * count removed files
     * 
     * @var int
     */
    protected $_removedFiles = 0;

    /**
     * count all files to be removed
     * 
     * @var int
     */
    protected $_totalFiles = 0;

    /**
     * set allow browser to true
     */
    protected function _beforeRun()
    {
        $this->_allowBrowser = TRUE;
    }

    /**
     * start shell script
     */
    public function run()
    {
        echo "\n";
        echo $this->_colorizeString('[Clear cache browser]', 'blue_label');
        echo "\n\n";

        if (empty($this->_arguments)) {
            echo $this->usageHelp();
            exit;
        }

        $clear = 0;

        if ($this->hasArgument('--all')) {
            $clear += self::ALL;
        }

        if ($this->hasArgument('--config')) {
            $clear += self::CONFIGURATION;
        }

        if ($this->hasArgument('--database')) {
            $clear += self::DATABASE;
        }

        if ($this->hasArgument('--templates')) {
            $clear += self::TEMPLATES;
        }

        if ($clear & self::CONFIGURATION) {
            $this->_clearConfigurationCache();
        }

        if ($clear & self::DATABASE) {
            $this->_clearDatabaseCache();
        }

        if ($clear & self::TEMPLATES) {
            $this->_clearTemplatesCache();
        }

        echo "\nTotal removed files: {$this->_removedFiles} / {$this->_totalFiles} \n\n";
    }

    /**
     * remove configuration cache
     */
    protected function _clearConfigurationCache()
    {
        $configFiles = glob(CORE_CACHE . '*_configuration.cache');
        $this->_removeMatchedFiles($configFiles);
    }

    /**
     * remove all table structure cache
     */
    protected function _clearDatabaseCache()
    {
        $structure = glob(CORE_CACHE . 'table_structure_*.cache');
        $this->_removeMatchedFiles($structure);
    }

    /**
     * remove template cache
     */
    protected function _clearTemplatesCache()
    {

    }

    /**
     * remove given list of files
     * 
     * @param $cacheFiles
     */
    protected function _removeMatchedFiles($cacheFiles)
    {
        foreach ($cacheFiles as $file) {
            $bool       = Core_Disc_Helper_Common::delete($file);
            $fileName   = str_replace(CORE_CACHE, '', $file);
            $this->_totalFiles++;

            if ($bool) {
                $info       = $this->_colorizeString('[SUCCESS]', 'green');
                $file       = $this->_colorizeString($fileName, 'brown');
                $message    = " File: $file was successfully removed\n";

                echo $this->formatOutput($message, $info);
                $this->_removedFiles++;
            } else {
                $info       = $this->_colorizeString('[ERROR]', 'red');
                $file       = $this->_colorizeString($fileName, 'brown');
                $message    = " File: $file was not removed\n";

                echo $this->formatOutput($message, $info);
            }
        }
    }

    /**
     * Retrieve Usage Help Message
     */
    public function usageHelp()
    {
        return <<<USAGE

------------------------------

    Usage:  php clear_cache.php [options]

    help or h            This help

    --all                remove all cache

    --config             remove only configuration cache file

    --database           remove database structure cache files

    --templates          remove all template cached files

    --no-colors          display information without colorized strings

\n
USAGE;
    }
}