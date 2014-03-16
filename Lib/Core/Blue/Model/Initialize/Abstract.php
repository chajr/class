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
        $this->init();
    }

    abstract public function init();
}