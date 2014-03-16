<?php
/**
 * Contains all object instances
 *
 * @package     Core
 * @subpackage  Blue
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Blue_Model_Register extends Core_Blue_Model_Object
{
    /**
     * @param string $class
     * @param string $name
     * @param mixed $args
     * @return mixed
     */
    public function setObject($class, $name, $args)
    {
        Loader::tracer('initialize object', debug_backtrace(), '006c94');
        $object = FALSE;

        try {
            $object = new $class($args);
        } catch (Exception $e) {
            Loader::exceptions($e);
        }

        if ($object) {
            $this->setData($name, $object);
        }

        return $this->getData($name);
    }

    /**
     * return list of registered objects with their codes
     * 
     * @return array
     */
    public function getRegisteredObjects()
    {
        $list = [];
        foreach ($this->_DATA as $name => $class) {
            $list[$name] = get_class($class);
        }

        return $list;
    }
}