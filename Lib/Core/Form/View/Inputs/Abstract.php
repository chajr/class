<?php
/**
 * base class for all inputs renderer
 *
 * @package     Core
 * @subpackage  Form
 * @author      chajr   <chajr@bluetree.pl>
 */
abstract class Core_Form_View_Inputs_Abstract extends Core_Render_View_Abstract
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
