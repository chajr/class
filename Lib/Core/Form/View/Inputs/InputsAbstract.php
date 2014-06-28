<?php
/**
 * base class for all inputs renderer
 *
 * @package     Core
 * @subpackage  Form
 * @author      chajr   <chajr@bluetree.pl>
 */
namespace Core\Form\View\Inputs;
use Core\Render\View\ViewAbstract;
abstract class InputsAbstract extends ViewAbstract
{
    /**
     * array of non used in HTML5 attributes
     * 
     * @var array
     */
    protected $_illegalAttributes = array(
        'valid_dependency',
        'other_val',
        'valid_type',
        'js',
        'minlength',
        'check_val',
        'check_field',
        'escape',
        'entities',
        'dynamic',
        'check_callback_value',
        'check_value'
    );
}
