<?php
/**
 * allow to remove cached files
 * by shell and browser
 *
 * @package     Core
 * @subpackage  Shell
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Shell\Helper;
use Core\Shell\Model\ModelAbstract;
use Core\Disc\Helper\Common;
class Cache extends ModelAbstract
{
    const NONE              = 0;
    const CONFIGURATION     = 1;
    const DATABASE          = 2;
    const TEMPLATES         = 4;
    const MISC              = 8;
    const CONTENT_TEMPLATES = 16;
    const ALL               = 31;

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
        $this->_setShellConfiguration('allow_browser', 'enabled');
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

        if ($this->hasArgument('--all') || $this->hasArgument('-a')) {
            $clear += self::ALL;
        }

        if ($this->hasArgument('--config')) {
            $clear += self::CONFIGURATION;
        }

        if ($this->hasArgument('--database')) {
            $clear += self::DATABASE;
        }

        if ($this->hasArgument('--templates') || $this->hasArgument('--all_templates')) {
            $clear += self::TEMPLATES;
        }

        if ($this->hasArgument('--content_templates') || $this->hasArgument('--all_templates')) {
            $clear += self::CONTENT_TEMPLATES;
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

        if ($clear & self::CONTENT_TEMPLATES) {
            $this->_clearContentTemplatesCache();
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
     * remove joined templates cache
     */
    protected function _clearTemplatesCache()
    {
        $structure = glob(CORE_CACHE . '*_templates.cache');
        $this->_removeMatchedFiles($structure);
    }

    /**
     * remove full rendered templates
     */
    protected function _clearContentTemplatesCache()
    {
        $structure = glob(CORE_CACHE . '*_templates_data.cache');
        $this->_removeMatchedFiles($structure);
    }

    /**
     * remove given list of files
     * 
     * @param $cacheFiles
     */
    protected function _removeMatchedFiles($cacheFiles)
    {
        foreach ($cacheFiles as $file) {
            $bool       = Common::delete($file);
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

    --help or -h         This help

    --all or -a          remove all cache

    --config             remove only configuration cache file

    --database           remove database structure cache files

    --templates          remove all template cached files

    --no-colors          display information without colorized strings

    --content_templates  remove templates with rendered content

    --all_templates      remove all templates cache

\n
USAGE;
    }
}