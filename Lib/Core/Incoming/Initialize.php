<?php
/**
 * Initialize incoming data models
 *
 * @package     Core
 * @subpackage  Incoming
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Incoming;
use Core\Blue\Model\Initialize\InitAbstract;
use Exception;
use Loader;
class Initialize extends InitAbstract
{
    /**
     * Initialize incoming data models
     */
    public function init()
    {
        try {
            Loader::getObject('Core\Incoming\Model\Get', '', 'GET');
            Loader::getObject('Core\Incoming\Model\Post', '', 'POST');
            Loader::getObject('Core\Incoming\Model\Cookie', '', 'COOKIE');
            Loader::getObject('Core\Incoming\Model\Session', '', 'SESSION');
            Loader::getObject('Core\Incoming\Model\File', '', 'FILE');
        } catch (Exception $e) {
            Loader::exceptions($e);
        }
    }
}
