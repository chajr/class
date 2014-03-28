<?php
/**
 * Initialize incoming data models
 *
 * @package     Core
 * @subpackage  Incoming
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Incoming_Initialize extends Core_Blue_Model_Initialize_Abstract
{
    /**
     * Initialize incoming data models
     */
    public function init()
    {
        try {
            Loader::getObject('Core_Incoming_Model_Get', '', 'GET');
            Loader::getObject('Core_Incoming_Model_Post', '', 'POST');
            Loader::getObject('Core_Incoming_Model_Cookie', '', 'COOKIE');
            Loader::getObject('Core_Incoming_Model_Session', '', 'SESSION');
            Loader::getObject('Core_Incoming_Model_File', '', 'FILE');
        } catch (Exception $e) {
            Loader::exceptions($e);
        }
    }
}
