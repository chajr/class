<?php
/**
 * base model to handle form creation and validation
 *
 * @package     Core
 * @subpackage  Form
 * @author      chajr   <chajr@bluetree.pl>
 */
class Core_Form_Model_Form
{
    /**
     * contains array with object options
     * 
     * @var array
     */
    protected $_configuration = [
        'input_error_class'         => 'inputError',
        'input_parent_error_class'  => 'input_error',
        'form_error_class'          => 'form_error',
        'attributes_to_hide'        => [],
        'use_error_node'            => TRUE,
        'pattern_symbol'            => '#',
        'validation_class'          => 'Validator_Simple',

        'module_name'               => 'Core_Form',
        'form_container_template'   => 'form/container.html',
        'container_renderer'        => 'Core_Form_View_Container',
        'container_cache'           => TRUE,
        'container_data_cache'      => FALSE,
        'form_configuration'        => [
            'form-enctype'              => 'text/plain',
            'form-class'                => NULL,
            'form-method'               => 'post',
            'form-name'                 => NULL,
            'form-action'               => '',
            'form-autocomplete'         => 'off',
            'form-novalidate'           => NULL,
            'form-accept-charset'       => 'UTF-8',
            'form-target'               => '_self',
            'form-id'                   => 'default_form',
        ],
        
    ];

    /**
     * contains information about object errors
     * 
     * @var string
     */
    public $error = NULL;

    /**
     * list of inputs with errors
     * 
     * @var array
     */
    public $errorList = [];

    /**
     * create form class instance
     * 
     * @param array $configuration
     */
    public function __construct(array $configuration = [])
    {
        if (!empty($configuration)) {
            $this->_configuration = array_merge(
                $this->_configuration,
                $configuration
            );
        }
    }

    /**
     * return form configuration
     * 
     * @return array
     */
    public function getConfiguration()
    {
        return $this->_configuration;
    }

    /**
     * add class to given element id
     * 
     * @param string $elementId
     * @param string $class
     */
    public function addElementClass($elementId, $class)
    {
        
    }

    /**
     * apply new class to form
     * 
     * @param string $class
     * @return Core_Form_Model_Form
     */
    public function addFormClass($class)
    {
        $this->_configuration['form_configuration']['form-class'] .= ' ' . $class;
        return $this;
    }

    /**
     * remove class from form element
     * 
     * @param string $class
     * @return Core_Form_Model_Form
     */
    public function removeFormClass($class)
    {
        $this->_configuration['form_configuration']['form-class'] = str_replace(
            $class,
            '',
            $this->_configuration['form_configuration']['form-class']
        );

        return $this;
    }

    /**
     * add input to form or block created inside of form
     * default order of inputs are the same as creation
     * order can be added manually
     * 
     * @param array $parameters
     * @param string $block
     * @param int $order
     * @return Core_Form_Model_Form
     */
    public function addInput(array $parameters, $block = 'main', $order = NULL)
    {
        /** @var Core_Form_Model_Inputs_Input $input */
        $input = Loader::getClass('Core_Form_Model_Inputs_Input', $parameters);

        return $this;
    }

    public function addTextArea(array $parameters, $block = 'main', $order = NULL)
    {
        
    }

    public function addSelect(array $parameters, $block = 'main', $order = NULL)
    {
        
    }

    public function addCheckbox(array $parameters, $block = 'main', $order = NULL)
    {
        
    }

    public function addRadio(array $parameters, $block = 'main', $order = NULL)
    {
        
    }

    public function addLabel($inputId)
    {
        
    }

    public function validate()
    {
        
    }

    /**
     * render complete form
     * 
     * @return string
     */
    public function renderForm()
    {
        /** @var Core_Form_View_Container $container */
        $container = Loader::getClass(
            $this->_configuration['container_renderer'],
            [
                'template'      => $this->_configuration['form_container_template'],
                'data'          => $this->_configuration['form_configuration'],
                'module'        => $this->_configuration['module_name'],
                'cache'         => $this->_configuration['container_cache'],
                'cache_data'    => $this->_configuration['container_data_cache'],
            ]
        );

        return $container->render();
    }
}
