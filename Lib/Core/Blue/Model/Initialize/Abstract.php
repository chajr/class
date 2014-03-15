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
        $this->init();
    }

    abstract public function init();
}