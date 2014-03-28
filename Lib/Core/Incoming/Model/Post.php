<?php
/**
 * convert $_POST array to blue object
 *
 * @package     Core
 * @subpackage  Incoming
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Incoming_Model_Post extends Core_Incoming_Model_Abstract
{
    const NOT_SECURE        = 0;
    const ENTITIES_SECURE   = 2;
    const SLASHES_SECURE    = 4;

    /**
     * convert post to object
     * also secure given data
     */
    public function __construct()
    {
        Loader::tracer('start post class', debug_backtrace(), '00306A');
        Loader::callEvent('initialize_post_object_before', $this);

        parent::__construct('post');
        $this->_secureData();

        Loader::callEvent('initialize_post_object_after', $this);
    }

    /**
     * use traveler to check all post data
     */
    protected function _secureData()
    {
        if (Loader::getConfiguration()->getSecure()->getPostSecure()) {
            $this->traveler('_checkPostValue', NULL, NULL, FALSE, TRUE);
        }
    }

    /**
     * secure post data
     * 
     * @param string $key
     * @param mixed $value
     */
    protected function _checkPostValue($key, $value)
    {
        $postSecure = Loader::getConfiguration()->getSecure()->getPostSecure();

        if ($postSecure & self::ENTITIES_SECURE) {
            $value = htmlspecialchars($value, ENT_NOQUOTES);
        }

        if ($postSecure & self::SLASHES_SECURE) {
            $value = addslashes($value);
        }

        $this->getData($key, $value);
    }
}
