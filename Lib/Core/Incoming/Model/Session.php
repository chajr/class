<?php
/**
 * convert $_SESSION array to blue object
 *
 * @package     Core
 * @subpackage  Incoming
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Incoming_Model_Session extends Core_Blue_Model_Object
{
    /**
     * convert session to object
     * 
     * @param array $session
     */
    public function __construct(&$session)
    {
        Loader::tracer('start session class', debug_backtrace(), '374557');
        Loader::callEvent('initialize_session_object_before', $this);
        
        $this->initializeObject();
        $this->_DATA = &$session;
        $this->afterInitializeObject();

        Loader::callEvent('initialize_session_object_after', $this);
    }
}
