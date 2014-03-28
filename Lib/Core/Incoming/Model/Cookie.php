<?php
/**
 * convert $_COOKIE array to blue object
 *
 * @package     Core
 * @subpackage  Incoming
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Incoming_Model_Cookie extends Core_Blue_Model_Object
{
    /**
     * convert cookie to object
     * 
     * @param array $cookie
     * @param null|string $indexKeyPrefix
     */
    public function __construct(&$cookie, $indexKeyPrefix = NULL)
    {
        Loader::tracer('start cookie class', debug_backtrace(), '213A59');
        Loader::callEvent('initialize_cookie_object_before', $this);

        $this->initializeObject();
        if ($indexKeyPrefix) {
            $this->_integerKeyPrefix = $indexKeyPrefix;
        }

        $this->_DATA = &$cookie;

        $this->afterInitializeObject();

        Loader::callEvent('initialize_cookie_object_after', $this);
    }

    /**
     * set some data in object
     * can give pair key=>value or array of keys
     *
     * @param string|array $key
     * @param mixed $data
     * @param integer|null $time
     * @return Core_Incoming_Model_Cookie
     */
    public function setData($key, $data = NULL, $time = NULL)
    {
        if(is_array($key)) {
            foreach ($key as $dataKey => $data) {
                $this->createCookie($dataKey, $data, $time);
            }

        } else {
            $this->createCookie($key, $data, $time);
        }

        return $this;
    }

    /**
     * set cookie file that exist on object, with default lifetime value
     * regenerate session id
     * 
     * @param string $key
     * @param mixed $val
     * @param integer|null $time
     * @return Core_Incoming_Model_Cookie 
     */
    public function createCookie($key, $val, $time = NULL)
    {
        Loader::tracer('set new cookie: ' . $key, debug_backtrace(), '213A59');

        if (!$time) {
            $time = time() + Loader::getConfiguration()->getCoreIncoming()->getCookieLifetime();
        }

        setcookie($key, $val, $time);
        $this->_putData($key, $val);

        return $this;
    }
}
