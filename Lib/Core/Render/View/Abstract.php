<?php

class Core_Render_View_Abstract
{
    /**
     * regular expression that corresponds to all display class markers
     * @var string
     */
    protected $_contentMarkers = [
        'markers'           => "#{;[\\w=\\-|&();\\/,]+;}#",
        'empty'             => "{;empty;}",
        'external'          => '#{;external;([\\w\/-])+;}#',
        'external_start'    => "{;external;",
        'marker_end'        => ";}",
        'marker_start'      => "{;",
    ];

    /**
     * session object
     * @var Core_Incoming_Model_Session
     */
    protected $_session = NULL;

    /**
     * allow to remove or not unused markers
     * @var boolean
     */
    protected $_clearMarkers = TRUE;

    /**
     * @var Core_Blue_Model_Object
     */
    protected $_templates;

    /**
     * @var Core_Blue_Model_Object
     */
    protected $_markers;

    /**
     * @var array
     */
    protected $_blocks = [];

    /**
     * create block instance
     * 
     * @param string $template
     * @param null|array $data
     */
    public function __construct($template, array $data = NULL)
    {
        Loader::tracer('start block abstract class', debug_backtrace(), '006400');
        Loader::callEvent('initialize_block_abstract_object_before', $this);
        $this->initializeBlock();

        $this->_session     = Loader::getObject('SESSION');
        $this->_templates   = Loader::getClass('Core_Blue_Model_Object');
        $this->_markers     = Loader::getClass('Core_Blue_Model_Object', $data);
        $this->_createMainLayout($template);

        $this->afterInitializeBlock();
        Loader::callEvent('initialize_block_abstract_object_after', $this);
    }

    /**
     * set that markers will be cleared
     * 
     * @param boolean $val
     */
    public function setClearMarkers($val)
    {
        $this->_clearMarkers = (bool)$val;
    }

    /**
     * get information about clear markers option
     * 
     * @return bool
     */
    public function getClearMarkers()
    {
        return $this->_clearMarkers;
    }

    protected function _createMainLayout($template)
    {
        Loader::tracer('create main layout for block', debug_backtrace(), '006400');
        Loader::callEvent('create_main_layout_before', [$this, &$template]);

        $content = $this->_checkTemplatePath($template);
        $this->_templates->setMainTemplate($content);
        $this->_external('main_template');

        Loader::callEvent('create_main_layout_after', $this);
        return $this;
    }

    /**
     * return correct path for required templates
     *
     * @param string $template
     * @return string
     */
    protected function _checkTemplatePath($template)
    {
        Loader::tracer('create path for required template', debug_backtrace(), '006400');
        Loader::callEvent('load_template_content_before', [$this, &$template]);

        $content        = $this->_contentMarkers['empty'];
        $templateExists = file_exists($template);
        if ($templateExists) {
            $content =  file_get_contents($template);
        } else {
            Loader::log('warning', $template, 'missing template');
        }

        Loader::callEvent('load_template_content_after', [
            $this,
            &$content,
            $templateExists
        ]);
        return $content;
    }

    /**
     * load external templates to main template,
     * or some external templates to module template
     *
     * @param string $template module name that want to load external template
     * @return Core_Render_View_Abstract
     */
    protected function _external($template)
    {
        Loader::tracer('load external template', debug_backtrace(), '006400');

        $list           = [];
        $baseContent    = $this->_templates->getData($template);

        preg_match_all(
            $this->_contentMarkers['external'],
            $baseContent,
            $list)
        ;

        foreach ($list[0] as $externalTemplate) {

            $newTemplate = str_replace(
                [
                    $this->_contentMarkers['external_start'],
                    $this->_contentMarkers['marker_end']
                ],
                '',
                $externalTemplate
            );

            $content    = $this->_checkTemplatePath($newTemplate);
            $newContent = str_replace(
                $externalTemplate,
                $content,
                $baseContent
            );

            $this->_templates->setData($template, $newContent);
        }

        return $this;
    }

    public function initializeBlock()
    {
        
    }

    public function afterInitializeBlock()
    {
        
    }

    /**
     * allows to replace marker with content, or group of markers by array
     *
     * @param string|array $marker marker name or array (marker => value)
     * @param string|boolean $content some string or NULL if marker array given
     * @param string|boolean $template name of module that wants to replace content (default core)
     * @return integer count of replaced markers
     * @example generate('marker', 'content')
     * @example generate('marker', 'content', 'module')
     * @example generate(array('marker' => 'content', 'marker2' => 'other content'), '')
     */
    public function generate($marker, $content, $template = 'main_content')
    {
        $content = $this->_checkContent($content);
        $markerStart = $this->_contentMarkers['marker_start'];

        if ($this->_templates->hasData($template)) {
            if (is_array($marker)) {

                foreach ($marker as $element => $content) {
                    $this->DISPLAY[$template] = str_replace(
                        '{;'.$element.';}',
                        $content,
                        $this->DISPLAY[$template],
                        $int2
                    );
                }

            } else {
                $string             = $this->DISPLAY[$template];
                $convertedString    = str_replace(
                    '{;'.$marker.';}',
                    $content,
                    $string,
                    $int
                );
                $this->DISPLAY[$template] = $convertedString;
            }
        }

        return $this;
    }

    /**
     * check if content is array, and return serialized array and info to log
     *
     * @param string|array $content
     * @return string
     */
    protected function _checkContent($content)
    {
        if (is_array($content)) {
            $exportContent = var_export($content, TRUE);
            Loader::log('info', $exportContent, 'content as array');

            return serialize($content);
        }

        return $content;
    }
}
