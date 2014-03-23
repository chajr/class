<?php
/**
 * Base class for all initialize classes
 *
 * @package     Core
 * @subpackage  Blue
 * @author      chajr <chajr@bluetree.pl>
 */
abstract class Core_Blue_Model_Initialize_Abstract
{
    public function __construct($params)
    {
        Loader::tracer('initialize module', debug_backtrace(), '4d4d4d');
        Loader::callEvent('init_module_before', $this);
        $this->init();
        Loader::callEvent('init_module_after', $this);
    }

    abstract public function init();
}