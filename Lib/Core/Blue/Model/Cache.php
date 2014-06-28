<?php
/**
 * Allows to manage cache
 * cache structure (module_code/cache_code.cache)
 *
 * @package     Core
 * @subpackage  Blue
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Blue\Model;
use Loader;
use Exception;
class Cache extends Object
{
    /**
     * one day base value in seconds
     */
    const CACHE_BASE_TIME = 86400;

    /**
     * default time for configuration file
     */
    const CACHE_CONFIG_TIME = 1;

    /**
     * check that cache directory exist and create it if not
     */
    public function __construct()
    {
        try{
            if (!file_exists(CORE_CACHE)) {
                @mkdir(CORE_CACHE);
                @chmod(CORE_CACHE, 0777);
            }
        } catch (Exception $e) {
            Loader::exceptions($e);
        }
    }

    /**
     * get cached configuration if it exists
     * 
     * @param string $cacheCode
     * @return bool|mixed
     */
    public function getCache($cacheCode)
    {
        $file = CORE_CACHE . $cacheCode . '.cache';
        if (!file_exists($file)) {
            return FALSE;
        }

        if ($this->_checkCachedTimes($file)) {
            return @file_get_contents($file);
        }

        return FALSE;
    }

    /**
     * add data to cache file, or create it
     * 
     * @param string $cacheCode
     * @param mixed $data
     * @return bool
     */
    public function setCache($cacheCode, $data)
    {
        $file = CORE_CACHE . $cacheCode . '.cache';
        return (bool)file_put_contents($file, $data);
    }

    /**
     * check cached file time
     * 
     * @param string $file
     * @return bool
     */
    protected function _checkCachedTimes($file)
    {
        $coreConfig     = Loader::getConfiguration();
        $currentTime    = time();
        $fileTime       = filemtime($file);

        if ($coreConfig) {
            $validTime = $coreConfig->getCore()->getCacheTime();
        } else {
            $validTime = self::CACHE_CONFIG_TIME;
        }

        $expireTime = ($validTime * self::CACHE_BASE_TIME) + $fileTime;

        if ($expireTime > $currentTime) {
            return TRUE;
        }

        return FALSE;
    }
}
