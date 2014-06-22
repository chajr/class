<?php
/**
 * base class to handle all input types
 *
 * @package     Core
 * @subpackage  Form
 * @author      chajr   <chajr@bluetree.pl>
 */
abstract class Core_Form_Model_Inputs_Abstract extends Core_Blue_Model_Object
{
    /**
     * array of non used in HTML5 attributes
     * 
     * @var array
     */
    protected $_illegalAttributes = [
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
        'check_value',
        'secure_data_method',
    ];

    /**
     * all input possible options
     * 
     * @var array
     */
    protected $_allowedAttributes = [
        'valid_dependency'      => NULL,
        'other_val'             => NULL,
        'valid_type'            => NULL,
        'js'                    => NULL,
        'minlength'             => NULL,
        'check_val'             => NULL,
        'check_field'           => NULL,
        'escape'                => NULL,
        'entities'              => NULL,
        'dynamic'               => NULL,
        'check_callback_value'  => NULL,
        'check_value'           => NULL,
        'secure_data_method'    => NULL,

        'name'                  => '',
        'id'                    => '',
        'class'                 => NULL,
        'rel'                   => NULL,
        'type'                  => '',
        'autocomplete'          => NULL,
        'autofocus'             => NULL,
        'disabled'              => NULL,
        'form'                  => NULL,
        'formvalidate'          => NULL,
        'height'                => NULL,
        'width'                 => NULL,
        'list'                  => NULL,
        'maxlength'             => NULL,
        'pattern'               => NULL,
        'placeholder'           => NULL,
        'readonly'              => NULL,
        'required'              => '',
        'size'                  => NULL,
        'step'                  => NULL,
        'value'                 => NULL,
        'style'                 => NULL,
        'onclick'               => NULL,
    ];

    /**
     * attributes that must be implemented into input element
     * 
     * @var array
     */
    private $_requiredAttributes = ['id', 'name'];

    /**
     * add some attributes to list of required attributes
     * 
     * @param string $attribute
     * @return Core_Form_Model_Inputs_Abstract
     */
    public function addRequiredAttribute($attribute)
    {
        if (in_array($attribute, $this->_allowedAttributes)) {
            $this->_requiredAttributes[] = $attribute;
        }

        return $this;
    }

    /**
     * start object by initialize Core_Blue_Model_Object
     * 
     * @param mixed $data
     * @return Core_Form_Model_Inputs_Abstract
     */
    public function initializeObject(&$data)
    {
        $data = array_merge($this->_allowedAttributes, $data);
        return $this;
    }
}
