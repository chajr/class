<?php
/**
 * convert $_SESSION array to blue object
 *
 * @package     Core
 * @subpackage  Incoming
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Incoming\Model;
use Loader;
use Core\Blue\Model;
class Session extends Model\Object
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

        $this->initializeObject($session);
        $this->_DATA = &$session;
        $this->afterInitializeObject();

        Loader::callEvent('initialize_session_object_after', $this);
    }
}
