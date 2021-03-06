<?php
/**
 * allow rendering templates and replace marker content
 *
 * @package     Core
 * @subpackage  Render
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Render\View;

use Loader;
use Exception;
use Core\Incoming\Model as Incoming;
use Core\Blue\Model as Blue;

abstract class ViewAbstract
{
    const MAIN_TEMPLATE_KEY_NAME    = 'main_template';
    const CORE_SKIN_DIRECTORY       = 'core/default';
    const SKIN_DEFAULT              = 'default';
    const CACHE_SUFFIX              = '_templates';
    const CACHE_DATA_SUFFIX         = '_templates_data';
    const LOOP_KEY_PREFIX           = 'loop_data_';

    /**
     * string used to join many content for one marker
     * 
     * @var string
     */
    protected $_contentGlue = ' ';

    /**
     * regular expression that corresponds to all display class markers
     * @var string
     */
    protected $_contentMarkers = [
        'markers'           => "#{;[\\w=\\-|&();\\/,]+;}#",
        'marker_key'        => "[\\w=\\-|&();\\/,]+",
        'empty'             => "{;empty;}",
        'external'          => '#{;external;([\\w\/-])+;}#',
        'external_start'    => "{;external;",
        'marker_end'        => ";}",
        'marker_start'      => "{;",
        'marker_delimiter'  => ";",
        'domain'            => '#{;core;domain;}#',
        'current_page'      => '#{;core;current_page;}#',
        'current_dir'       => '#{;core;current_dir;}#',
        'protocol'          => '#{;core;protocol;}#',
        'current_url'       => '#{;core;current_url;}#',
        'full_path'         => '#{;core;full_path;}#',
        'anchor'            => '#{;core;anchor;}#',
        'port'              => '#{;core;port;}#',
        'loop_markers'      => '#{;(start|end);([\\w-])+;}#',
        'loop_start'        => '{;start;',
        'loop_end'          => '{;end;',
        'loop_start_empty'  => '{;start_empty;',
        'loop_end_empty'    => '{;end_empty;',
        'optional_markers'  => '#{;op;([\\w-])+;}#',
        'optional_start'    => '{;op;',
        'optional_end'      => '{;op_end;',
        'view'              => '{;view;',
    ];

    /**
     * session object
     * 
     * @var Incoming\Session
     */
    protected $_session = null;

    /**
     * allow to remove or not unused markers
     * 
     * @var boolean
     */
    protected $_clearMarkers = true;

    /**
     * list of templates
     * 
     * @var Blue\Object
     */
    protected $_templates;

    /**
     * content markers
     * 
     * @var Blue\Object
     */
    protected $_markers;

    /**
     * name for cache file
     * 
     * @var string
     */
    protected $_cacheName;

    /**
     * default constructor options
     * 
     * @var array
     */
    protected $_options = [
        'template'      => '',
        'data'          => null,
        'module'        => null,
        'cache'         => true,
        'cache_data'    => false,
    ];

    /**
     * contains regular expression to detect loop marker
     *
     * @var string
     */
    protected $_loopMarkerPrefix;

    /**
     * create block instance
     * 
     * @param string|array $options
     */
    public function __construct($options)
    {
        Loader::tracer('start block abstract class', debug_backtrace(), '006400');
        Loader::callEvent('initialize_block_abstract_object_before', $this);
        $this->initializeBlock($options);
        $this->_prepareLoopMarkerPrefix();

        if (is_string($options)) {
            $template = $options;
        } else {
            $this->_options = array_merge($this->_options, $options);
            $template       = $this->_options['template'];
        }

        $newData            = $this->_getMarkersFromSession($this->_options['data']);
        $this->_session     = Loader::getObject('SESSION');
        $this->_templates   = Loader::getClass('Core\Blue\Model\Object');
        $this->_markers     = Loader::getClass('Core\Blue\Model\Object', $newData);

        $this->_setCacheName();
        $this->_createMainLayout($template);

        $this->afterInitializeBlock();
        Loader::callEvent('initialize_block_abstract_object_after', $this);
    }

    /**
     * return cache class name or full cache name
     * 
     * @param string $suffix
     * @return string
     */
    public function getCacheName($suffix = '')
    {
        if ($suffix === 'template') {
            $suffix = self::CACHE_SUFFIX;
        }

        if ($suffix === 'data') {
            $suffix = self::CACHE_DATA_SUFFIX;
        }

        return $this->_cacheName . $suffix;
    }

    /**
     * set up cache name from class using view_abstract
     * 
     * @return ViewAbstract
     */
    protected function _setCacheName()
    {
        $this->_cacheName = strtolower(get_class($this));
        return $this;
    }

    /**
     * cascade search in skin directory for template
     * if template was not found log information and return null
     * 
     * @param string $template
     * @return null|string
     */
    protected function _prepareTemplateUrl($template)
    {
        $currentSkinMod         = '';
        $currentSkinDefaultMod  = '';
        $skinDefaultMod         = '';

        $rendererConfig         = Loader::getConfiguration()->getDesign();
        $package                = $rendererConfig->getSkinPackage();
        $template               = '/' . $template;

        $currentSkin            = $package . '/' . $rendererConfig->getSkinView() . $template;
        $currentSkinDefault     = $package . '/' . self::SKIN_DEFAULT . $template;
        $skinDefault            = self::CORE_SKIN_DIRECTORY . $template;

        if ($this->_options['module']) {
            $modulePath             = Loader::name2path($this->_options['module'], false);
            $currentSkinMod         = CORE_LIB . $modulePath . '/Skin/' . $currentSkin;
            $currentSkinDefaultMod  = CORE_LIB . $modulePath . '/Skin/' . $currentSkinDefault;
            $skinDefaultMod         = CORE_LIB . $modulePath . '/Skin/' . $skinDefault;
        }

        $currentSkinCore        = CORE_SKIN . $currentSkin;
        $currentSkinDefaultCore = CORE_SKIN . $currentSkinDefault;
        $skinDefaultCore        = CORE_SKIN . $skinDefault;

        switch (true) {
            case Incoming\File::exist($currentSkinMod):
                return $currentSkinMod;
            case Incoming\File::exist($currentSkinCore):
                return $currentSkinCore;
            case Incoming\File::exist($currentSkinDefaultMod):
                return $currentSkinDefaultMod;

            case Incoming\File::exist($currentSkinDefaultCore):
                return $currentSkinDefaultCore;

            case Incoming\File::exist($skinDefaultMod):
                return $skinDefaultMod;

            case Incoming\File::exist($skinDefaultCore):
                return $skinDefaultCore;

            default:
                Loader::log(
                    'warning',
                    [
                        $currentSkinMod,
                        $currentSkinCore,
                        $currentSkinDefaultMod,
                        $currentSkinDefaultCore,
                        $skinDefaultMod,
                        $skinDefaultCore,
                    ],
                    'missing template'
                );
                return null;
        }
    }

    /**
     * apply markers set in session
     * 
     * @param array|null|Blue\Object $data
     * @return array|null
     */
    protected function _getMarkersFromSession($data)
    {
        if ($data instanceof Blue\Object) {
            $data = $data->getData();
        }

        /** @var Incoming\Session $session */
        $sessionModel   = Loader::getObject('SESSION');
        $instance       = $sessionModel instanceof Incoming\Session;
        $sessionData    = $sessionModel->getSessionMarkers();
        $isData         = is_array($data);

        if ($instance && $isData && $sessionData) {
            $data = array_merge($sessionData, $data);
        }

        return $data;
    }

    /**
     * set that markers will be cleared
     * 
     * @param boolean $val
     * @return ViewAbstract
     */
    public function setClearMarkers($val)
    {
        $this->_clearMarkers = (bool)$val;
        return $this;
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

    /**
     * create object instance main layout
     * join all required templates
     * 
     * @param string $template
     * @return ViewAbstract
     */
    protected function _createMainLayout($template)
    {
        Loader::tracer('create main layout for block', debug_backtrace(), '006400');
        Loader::callEvent('create_main_layout_before', [$this, &$template]);

        $cache = $this->_templateCache();
        if ($cache && $this->_options['cache']) {
            $this->_templates->setData(self::MAIN_TEMPLATE_KEY_NAME, $cache);

            Loader::callEvent('create_main_layout_after', [$this, $template]);
            return $this;
        }

        $template = $this->_prepareTemplateUrl($template);

        if ($template) {
            $content = $this->_getTemplateContent($template);
            $this->_templates->setData(self::MAIN_TEMPLATE_KEY_NAME, $content);
            $this->_external();
            $this->_templateCache(
                $this->_templates->getData(self::MAIN_TEMPLATE_KEY_NAME)
            );
        }

        Loader::callEvent('create_main_layout_after', [$this, $template]);
        return $this;
    }

    /**
     * return correct path for required templates
     *
     * @param string $template
     * @return string
     */
    protected function _getTemplateContent($template)
    {
        Loader::tracer('create path for required template', debug_backtrace(), '006400');
        Loader::callEvent('load_template_content_before', [$this, &$template]);

        $content =  file_get_contents($template);
        if (empty($content)) {
            $content        = $this->_contentMarkers['empty'];
            Loader::log('warning', $template, 'empty template content');
        }

        Loader::callEvent('load_template_content_after', [
            $this,
            &$content,
            $template
        ]);

        return $content;
    }

    /**
     * load external templates to main template,
     * or some external templates to module template
     *
     * @return ViewAbstract
     */
    protected function _external()
    {
        Loader::tracer('load external template', debug_backtrace(), '006400');

        $list           = [];
        $baseContent    = $this->_templates->getData(self::MAIN_TEMPLATE_KEY_NAME);

        preg_match_all(
            $this->_contentMarkers['external'],
            $baseContent,
            $list
        );

        foreach ($list[0] as $externalTemplate) {

            $baseContent = $this->_templates->getData(self::MAIN_TEMPLATE_KEY_NAME);
            $newTemplate = str_replace(
                [
                    $this->_contentMarkers['external_start'],
                    $this->_contentMarkers['marker_end']
                ],
                '',
                $externalTemplate
            );

            $newTemplate = $this->_prepareTemplateUrl($newTemplate . '.html');

            if ($newTemplate) {
                $content    = $this->_getTemplateContent($newTemplate);
                $newContent = str_replace(
                    $externalTemplate,
                    $content,
                    $baseContent
                );

                $this->_templates->setData(self::MAIN_TEMPLATE_KEY_NAME, $newContent);
            }
        }

        return $this;
    }

    /**
     * allows to replace marker with content, or group of markers by array
     *
     * @param string|array $marker marker name or array (marker => value)
     * @param string|boolean $content some string or null if marker array given
     * @return ViewAbstract
     * @example generate('marker', 'content')
     * @example generate(['marker' => 'content', 'marker2' => 'other content'], '')
     */
    public function generate($marker, $content = null)
    {
        if (is_array($marker)) {
            foreach ($marker as $key => $value) {
                $this->_setMarkerData($key, $value);
            }
        } else {
            $this->_setMarkerData($marker, $content);
        }

        return $this;
    }

    /**
     * process array and generate proper for loop content
     *
     * @param string $marker
     * @param array $contentArray
     * @return ViewAbstract
     * @example loop('marker', [[key => val], [key2 => val2]]);
     */
    public function loop($marker, array $contentArray)
    {
        $markerData = $this->_markers->getData(self::LOOP_KEY_PREFIX . $marker);

        if ($markerData) {
            array_push($markerData, $contentArray);
            $contentArray = $markerData;
        }

        $this->_markers->setData(self::LOOP_KEY_PREFIX . $marker, $contentArray);
        return $this;
    }

    /**
     * render loop content
     *
     * @return ViewAbstract
     */
    protected function _renderLoops()
    {
        $keys       = array_keys($this->_markers->getData());
        $content    = $this->_templates->getData(self::MAIN_TEMPLATE_KEY_NAME);

        foreach ($keys as $markerKey) {
            if (!preg_match($this->_loopMarkerPrefix, $markerKey)) {
                continue;
            }

            $content = $this->_renderLoop($markerKey, $content);
        }

        $this->_templates->setData(self::MAIN_TEMPLATE_KEY_NAME, $content);
        return $this;
    }

    /**
     * render single loop
     *
     * @param string $marker
     * @param string $content
     * @return string
     */
    protected function _renderLoop($marker, $content)
    {
        $contentArray       = $this->_markers->getData($marker);
        $markerKey          = str_replace(self::LOOP_KEY_PREFIX, '', $marker);
        $loopStart          = $this->_contentMarkers['loop_start'];
        $loopEnd            = $this->_contentMarkers['loop_end'];
        $markerEnd          = $this->_contentMarkers['marker_end'];
        $startEmpty         = $this->_contentMarkers['loop_start_empty'];
        $endEmpty           = $this->_contentMarkers['loop_end_empty'];
        $startMarker        = $loopStart . $markerKey . $markerEnd;
        $endMarker          = $loopEnd . $markerKey . $markerEnd;
        $messageStartMarker = $startEmpty . $markerKey . $markerEnd;
        $messageEndMarker   = $endEmpty . $markerKey . $markerEnd;
        $end                = '';
        $loopContent        = $this->_getGroupMarkerContent(
            $content,
            $startMarker,
            $endMarker
        );

        if ($loopContent === null) {
            return $content;
        }

        if (empty($contentArray)) {
            $content = str_replace($loopContent, '', $content);

            $this->generate($messageStartMarker, '');
            $this->generate($messageEndMarker, '');
        } else {
            foreach ($contentArray as $row) {
                $tmp = $loopContent;

                foreach($row as $key => $value){
                    $model = $this->_contentMarkers['marker_start']
                        . $markerKey
                        . $this->_contentMarkers['marker_delimiter']
                        . $key
                        . $this->_contentMarkers['marker_end'];

                    $tmp = str_replace($model, $value, $tmp);
                }

                $end .= $tmp;
            }

            $content = str_replace(
                $loopContent,
                $end,
                $content
            );

            $emptyMessage = $this->_getGroupMarker(
                $content,
                $messageStartMarker,
                $messageEndMarker
            );

            $content = str_replace($emptyMessage, '', $content);
            $content = str_replace($startMarker, '', $content);
            $content = str_replace($endMarker, '', $content);
        }

        return $content;
    }

    /**
     * get whole loop content without markers of marker group from given content
     *
     * @param string $content
     * @param string $startMarker
     * @param string $endMarker
     * @return null|string
     */
    protected function _getGroupMarkerContent($content, $startMarker, $endMarker)
    {
        $position1 = strpos($content, $startMarker);
        $position1 = $position1 + mb_strlen($startMarker);
        $position2 = strpos($content, $endMarker);
        $position2 = $position2 - $position1;

        if ($position2 < 0 || !$position1) {
            return null;
        }

        return substr($content, $position1, $position2);
    }

    /**
     * get whole loop content with markers of marker group (eg. loop marker and content)
     *
     * @param string $content
     * @param string $startMarker
     * @param string $endMarker
     * @return null|string
     */
    protected function _getGroupMarker($content, $startMarker, $endMarker)
    {
        $position1 = strpos($content, $startMarker);
        $position2 = strpos($content, $endMarker);
        $position2 = $position2 + mb_strlen($endMarker);
        $position2 = $position2 - $position1;

        if ($position2 < 0 || !$position1) {
            return null;
        }

        return substr($content, $position1, $position2);
    }

    /**
     * set data in marker object
     * 
     * @param string $marker
     * @param string $content
     * @return ViewAbstract
     */
    protected function _setMarkerData($marker, $content)
    {
        $content    = $this->_checkContent($content);
        $markerData = $this->_markers->getData($marker);
        if ($markerData) {
            if (is_array($markerData)) {
                array_push($markerData, $content);
                $content = $markerData;
            } else {
                $content = [$markerData, $content];
            }
        }

        $this->_markers->setData($marker, $content);
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
            $exportContent = var_export($content, true);
            Loader::log('info', $exportContent, 'content as array');

            return serialize($content);
        }

        return $content;
    }

    /**
     * join contents included in modules groups in complete page, replace paths
     * fix urls, clean from markers and optionally compress
     *
     * @return string complete content to display
     */
    public function render()
    {
        Loader::tracer('render content of display class', debug_backtrace(), '006400');
        Loader::callEvent('render_template_before', $this);

        $cache = $this->_templateCache(null, 'data');
        if ($cache && $this->_options['cache_data']) {
            $this->_templates->setData(self::MAIN_TEMPLATE_KEY_NAME, $cache);

            Loader::callEvent('render_template_after', [$this, &$cache]);
            return $cache;
        }

        try {
            $this->_joinTemplates();
            $this->_renderLoops();
            $this->_renderMarkers();
            $this->_path();
            $this->_clean();
        } catch (Exception $e) {
            Loader::exceptions($e, 'render', 'warning');
        }

        $finalContent = $this->_templates->getData(self::MAIN_TEMPLATE_KEY_NAME);
        $this->_templateCache($finalContent, 'data');

        Loader::callEvent('render_template_after', [$this, &$finalContent]);
        return $finalContent;
    }

    /**
     * render and join all templates added to main renderer
     * 
     * @return ViewAbstract
     */
    protected function _joinTemplates()
    {
        foreach ($this->_templates->getData() as $template => $content) {
            $isMainTemplate = $template === self::MAIN_TEMPLATE_KEY_NAME;
            $isViewObject   = !($content instanceof ViewAbstract);

            if ($isMainTemplate && $isViewObject) {
                continue;
            }

            /** @var ViewAbstract $content */
            $mainTemplate = $this->_templates->getData(self::MAIN_TEMPLATE_KEY_NAME);
            $mainTemplate = str_replace(
                $this->_contentMarkers['view'] . $template . $this->_contentMarkers['marker_end'],
                $content->render(),
                $mainTemplate
            );
            $this->_templates->setData(self::MAIN_TEMPLATE_KEY_NAME, $mainTemplate);
        }

        return $this;
    }

    /**
     * add view class to be rendered at end
     * 
     * @param ViewAbstract $view
     * @param string $name
     * @return ViewAbstract
     */
    public function addView(ViewAbstract $view, $name)
    {
        $this->_templates->setData($name, $view);
        return $this;
    }

    /**
     * return ViewAbstract object to make changes
     * 
     * @param string $name
     * @return ViewAbstract
     */
    public function getView($name)
    {
        return $this->_templates->getData($name);
    }

    /**
     * render content to markers
     * 
     * @return ViewAbstract
     */
    protected function _renderMarkers()
    {
        $keys       = array_keys($this->_markers->getData());
        $content    = $this->_templates->getData(self::MAIN_TEMPLATE_KEY_NAME);

        foreach ($keys as $markerKey) {
            if (preg_match($this->_loopMarkerPrefix, $markerKey)) {
                continue;
            }

            $markerContent  = $this->_implodeContent($markerKey);
            $markerStart    = $this->_contentMarkers['marker_start'];
            $markerEnd      = $this->_contentMarkers['marker_end'];
            $marker         = $markerStart . $markerKey . $markerEnd;
            $content        = str_replace($marker, $markerContent, $content);
        }

        $this->_templates->setData(self::MAIN_TEMPLATE_KEY_NAME, $content);
        return $this;
    }

    /**
     * prepare regular expression for loop markers
     *
     * @return ViewAbstract
     */
    protected function _prepareLoopMarkerPrefix()
    {
        $this->_loopMarkerPrefix = '#^'
            . self::LOOP_KEY_PREFIX
            . $this->_contentMarkers['marker_key']
            . '#';

        return $this;
    }

    /**
     * implode content for marker using set up glue
     * 
     * @param string $marker
     * @return string
     */
    protected function _implodeContent($marker)
    {
        $content = $this->_markers->getData($marker);
        if (is_array($content)) {
            return implode($this->_contentGlue, $this->_markers->getData($marker));
        }

        return $content;
    }

    /**
     * change default string glue
     * 
     * @param string $glue
     * @return ViewAbstract
     */
    public function setContentGlue($glue)
    {
        $this->_contentGlue = $glue;
        return $this;
    }

    /**
     * return set up string glue
     * 
     * @return string
     */
    public function getContentGlue()
    {
        return $this->_contentGlue;
    }

    /**
     * run clean methods to remove unused markers
     * 
     * @return ViewAbstract
     */
    protected function _clean()
    {
        if ($this->_clearMarkers === false) {
            return $this;
        }

        Loader::tracer('remove unused markers', debug_backtrace(), '006400');

        $this->_cleanMarkers('loop');
        $this->_cleanMarkers('optional');

        $mainContent = $this->_templates->getData(self::MAIN_TEMPLATE_KEY_NAME);
        $mainContent = preg_replace($this->_contentMarkers['markers'], '', $mainContent);
        $this->_templates->setData(self::MAIN_TEMPLATE_KEY_NAME, $mainContent);

        return $this;
    }

    /**
     * clean template from unused markers on loops and optional values
     *
     * @param string $type type to check
     * @return ViewAbstract
     */
    protected function _cleanMarkers($type)
    {
        switch ($type) {
            case'loop':
                $reg1 = $this->_contentMarkers['loop_markers'];
                $reg2 = false;
                $reg3 = $this->_contentMarkers['loop_start'];
                $reg4 = $this->_contentMarkers['loop_end'];
                break;

            case'optional':
                $reg1 = $this->_contentMarkers['optional_markers'];
                $reg2 = $this->_contentMarkers['markers'];
                $reg3 = $this->_contentMarkers['optional_start'];
                $reg4 = $this->_contentMarkers['optional_end'];
                break;

            default:
                return $this;
        }

        $mainContent = $this->_templates->getData(self::MAIN_TEMPLATE_KEY_NAME);

        preg_match_all($reg1, $mainContent, $array);
        if (!empty($array) && !empty($array[0])) {
            foreach ($array[0] as $marker) {

                $start      = strpos($mainContent, $marker);
                $endMarker  = str_replace($reg3, $reg4, $marker);
                $end        = strpos($mainContent, $endMarker);

                if (!$start || !$end) {
                    continue;
                }

                $startContent   = $start + mb_strlen($marker);
                $contentLength  = $end - $startContent;
                $string         = substr($mainContent, $startContent, $contentLength);
                $end            += mb_strlen($endMarker);
                $len            = $end - $start;
                $stringToRemove = substr($mainContent, $start, $len);

                if ($reg2 && !preg_match($reg2, $string)) {
                    $mainContent = str_replace($stringToRemove, $string, $mainContent);
                } else {
                    $mainContent = str_replace($stringToRemove, '', $mainContent);
                }
            }
        }

        $this->_templates->setData(self::MAIN_TEMPLATE_KEY_NAME, $mainContent);
        return $this;
    }

    /**
     * replace paths marker with data
     *
     * @return ViewAbstract
     */
    protected function _path()
    {
        Loader::tracer('replace path markers', debug_backtrace(), '006400');

        $this->_convertPathMarkers('current_page');
        $this->_convertPathMarkers('full_path');
        $this->_convertPathMarkers('current_dir');
        $this->_convertPathMarkers('domain');
        $this->_convertPathMarkers('protocol');
        $this->_convertPathMarkers('current_url');
        $this->_convertPathMarkers('anchor');
        $this->_convertPathMarkers('port');

        return $this;
    }

    /**
     * convert specific core path marker
     * 
     * @param string $type
     * @return ViewAbstract
     */
    protected function _convertPathMarkers($type)
    {
        /** @var Incoming\Get $getModel */
        $getModel    = Loader::getObject('GET');
        $mainContent = $this->_templates->getData(self::MAIN_TEMPLATE_KEY_NAME);
        $instance    = $getModel instanceof Incoming\Get;

        if (!$instance) {
            return $this;
        }

        switch($type)
        {
            case'current_page':
                $mainContent = preg_replace(
                    $this->_contentMarkers[$type],
                    $getModel->currentPage(),
                    $mainContent
                );
                break;

            case'full_path':
                $mainContent = preg_replace(
                    $this->_contentMarkers[$type],
                    $getModel->fullPath(),
                    $mainContent
                );
                break;

            case'current_dir':
                $mainContent = preg_replace(
                    $this->_contentMarkers[$type],
                    $getModel->currentDir(),
                    $mainContent
                );
                break;

            case'domain':
                $mainContent = preg_replace(
                    $this->_contentMarkers[$type],
                    $getModel->domain(),
                    $mainContent
                );
                break;

            case'protocol':
                $mainContent = preg_replace(
                    $this->_contentMarkers[$type],
                    $getModel->protocol(),
                    $mainContent
                );
                break;

            case'current_url':
                $mainContent = preg_replace(
                    $this->_contentMarkers[$type],
                    $getModel->currentUrl(),
                    $mainContent
                );
                break;

            case'anchor':
                $mainContent = preg_replace(
                    $this->_contentMarkers[$type],
                    $getModel->anchor(),
                    $mainContent
                );
                break;

            case'port':
                $mainContent = preg_replace(
                    $this->_contentMarkers[$type],
                    $getModel->port(),
                    $mainContent
                );
                break;
        }

        $this->_templates->setData(self::MAIN_TEMPLATE_KEY_NAME, $mainContent);
        return $this;
    }

    /**
     * return cached configuration or save it to cache file
     *
     * @param null|mixed $data
     * @param string $type
     * @return bool|void
     */
    protected function _templateCache($data = null, $type = 'template')
    {
        /** @var Blue\Cache $cache */
        $cache      = Loader::getObject('Core\Blue\Model\Cache');
        $cacheKey   = $this->getCacheName($type);

        if ($data) {
            return $cache->setCache($cacheKey, $data);
        } else {
            return $cache->getCache($cacheKey);
        }
    }

    /**
     * initialize block
     * 
     * @param array|null $options
     */
    public function initializeBlock(&$options)
    {
        
    }

    /**
     * lunch after block initialization
     */
    public function afterInitializeBlock()
    {
        
    }
}
