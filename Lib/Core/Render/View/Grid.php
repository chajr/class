<?php
/**
 * allow rendering grid
 *
 * @package     Core
 * @subpackage  Render
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Render_View_Grid extends Core_Render_View_Abstract
{
    /**
     * string used to join many content for one marker
     *
     * @var string
     */
    protected $_contentGlue = "\n";

    /**
     * initialize grid renderer
     * 
     * @param array $options
     */
    public function __construct(array $options)
    {
        Loader::tracer('start grid class', debug_backtrace(), '006800');
        Loader::callEvent('initialize_grid_object_before', $this);
        $this->initializeBlock($options);

        $this->_options = array_merge($this->_options, $options);

        $this->afterInitializeBlock();
        Loader::callEvent('initialize_grid_object_after', $this);
    }

    /**
     * render grid content
     * 
     * @return string
     */
    public function render()
    {
        $data = $this->_options['data'];

        if (empty($data)) {
            return '';
        }

        $renderedContent = [];
        foreach ($data as $row) {
            /** @var Core_Render_View_Abstract $renderer */
            $renderer = Loader::getClass(
                'Core_Render_View_Abstract', [
                    'template'  => $this->_options['template'],
                    'data'      => $this->_getDataFromFow($row)
                ]
            );
            $renderer->setClearMarkers($this->getClearMarkers());
            $renderedContent[] = $renderer->render();
        }

        return implode($this->_contentGlue, $renderedContent);
    }

    /**
     * return data for row
     * 
     * @param array|Core_Blue_Model_Object $row
     * @return array
     */
    protected function _getDataFromFow($row)
    {
        if ($row instanceof Core_Blue_Model_Object) {
            return $row->getData();
        }

        return $row;
    }
}
