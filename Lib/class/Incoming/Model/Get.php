<?php
/**
 * convert $_GET array to blue object
 *
 * @package     Core
 * @subpackage  Incoming
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Incoming\Model;
use Loader;
class Get extends ModelAbstract
{
    /**
     * @var array
     */
    protected $_url = [
        'scheme'    => '',
        'host'      => '',
        'port'      => '',
        'user'      => '',
        'pass'      => '',
        'path'      => '',
        'query'     => '',
        'fragment'  => ''
    ];

    /**
     * parse url to array and start object
     */
    public function __construct()
    {
        Loader::tracer('start get class', debug_backtrace(), '00306A');
        Loader::callEvent('initialize_get_object_before', $this);

        parent::__construct('get');
        $this->_parseUrl();

        Loader::callEvent('initialize_get_object_after', $this);
    }

    /**
     * start url parsing
     */
    protected function _parseUrl()
    {
        $url = parse_url($_SERVER['SCRIPT_URI'] . '?' . $_SERVER['QUERY_STRING']);
        $this->_url = array_merge($this->_url, $url);
    }

    /**
     * return parsed url array
     * 
     * @return array
     */
    public function parsedUrl()
    {
        return $this->_url;
    }

    /**
     * return current page name with path
     * 
     * @return string
     */
    public function currentPage()
    {
        return $this->_url['host'] . $this->_url['path'];
    }

    /**
     * return current page directory
     * 
     * @return string
     */
    public function currentDir()
    {
        return $this->_url['path'];
    }

    /**
     * return only domain
     * 
     * @return string
     */
    public function domain()
    {
        return $this->_url['host'];
    }

    /**
     * return page protocol
     * 
     * @return string
     */
    public function protocol()
    {
        return $this->_url['scheme'];
    }

    /**
     * return array of sub domains
     * 
     * @return array
     */
    public function subDomains()
    {
        $parts = explode('.', $this->_url['host']);
        array_pop($parts);
        array_pop($parts);

        return $parts;
    }

    /**
     * return protocol with domain
     * 
     * @return string
     */
    public function currentUrl()
    {
        return $this->_url['scheme'] . '://' . $this->_url['host'];
    }

    /**
     * return full path with parameters
     * 
     * @return string
     */
    public function fullPath()
    {
        return $_SERVER['SCRIPT_URI'] . '?' . $_SERVER['QUERY_STRING'];
    }

    /**
     * return array of anchors
     * 
     * @return mixed
     */
    public function anchor()
    {
        return $this->_url['fragment'];
    }

    /**
     * return port number
     * 
     * @return string
     */
    public function port()
    {
        return $this->_url['port'];
    }

    /**
     * check that protocol is http or https
     * 
     * @return bool
     */
    public function isSecure()
    {
        if ($this->_url['scheme'] === 'http') {
            return FALSE;
        }

        return TRUE;
    }
}
