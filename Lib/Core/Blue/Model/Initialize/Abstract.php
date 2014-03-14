<?php
abstract class Core_Blue_Model_Initialize_Abstract
{
    protected function __construct($params)
    {
        
        $this->init();
    }
    
    abstract public function init();
}