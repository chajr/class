<?php
/**
 * Base class for all initialize classes
 *
 * @package     Core
 * @subpackage  Blue
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Blue\Model\Initialize;
use Loader;
use Exception;
abstract class InitAbstract
{
    public function __construct($params)
    {
        Loader::tracer('initialize module', debug_backtrace(), '4d4d4d');
        Loader::callEvent('init_module_before', $this);

        try {
            $this->init();
        } catch (Exception $e) {
            Loader::exceptions($e, NULL, 'initialize_exception');

            Loader::callEvent('init_module_error_mode_before', [$this, $e]);
            $this->errorMode();
            Loader::callEvent('init_module_error_mode_after', [$this, $e]);
        }

        Loader::callEvent('init_module_after', $this);
    }

    abstract public function init();
    abstract function errorMode();
}
