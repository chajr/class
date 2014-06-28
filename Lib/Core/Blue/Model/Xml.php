<?php
/**
 * extend DOMDocument to use framework xml configuration files
 *
 * @package     Core
 * @subpackage  Blue
 * @author      Michał Adamiak    <chajr@bluetree.pl>
 * @copyright   chajr/bluetree
 * @version     1.6.0
 */
namespace Core\Blue\Model;
use DOMDocument;
use DOMNodeList;
use DOMNode;
use DOMNamedNodeMap;
use DOMDocumentType;
use DOMImplementation;
use DomElement;
use Loader;
class Xml extends DOMDocument
{
    /**
     * Root element
     * @var DOMElement
     */
    public $documentElement;

    /**
     * node name
     * @var string
     */
    public $nodeName;

    /**
     * node type
     * ELEMENT_NODE                 (1) element
     * ATTRIBUTE_NODE               (2) attribute
     * TEXT_NODE                    (3) text node (element or attribute)
     * CDATA_SECTION_NODE           (4) CDATA section
     * ENTITY_REFERENCE_NODE        (5) entity reference
     * ENTITY_NODE                  (6) entity
     * PROCESSING_INSTRUCTION_NODE  (7) process instruction
     * COMMENT_NODE                 (8) comment
     * DOCUMENT_NODE                (9) main document element
     * @var integer
     */
    public $nodeType;

    /**
     * node value
     * @var mixed
     */
    public $nodeValue;

    /**
     * parent node
     * @var DOMNode
     */
    public $parentNode;

    /**
     * child nodes collection
     * @var DOMNodeList
     */
    public $childNodes;

    /**
     * first child node
     * @var DOMNode
     */
    public $firstChild;

    /**
     * last child node
     * @var DOMNode
     */
    public $lastChild;

    /**
     * collection of attributes
     * @var DOMNamedNodeMap
     */
    public $attributes;

    /**
     * next node in collection
     * @var DOMNode
     */
    public $nextSibling;

    /**
     * previous node in collection
     * @var DOMNode
     */
    public $previousSibling;

    /**
     * namespace fo current node
     * @var string
     */
    public $namespaceURI;

    /**
     * reference node object
     * @var DOMDocument
     */
    public $ownerDocument;

    /**
     * number of elements in collection
     * @var integer
     */
    public $length;

    /**
     * DTD, if return documentType object
     * @var DOMDocumentType
     */
    public $doctype;

    /**
     * document, implementation type, compatible with document mime type
     * @var DOMImplementation
     */
    public $implementation;

    /**
     * error information
     * @var string
     */
    public $err = NULL;

    /**
     * last free id
     * @var string
     */
    public $idList;

    /**
     * default constructor options
     * 
     * @var array
     */
    protected $_options = [
        'version'   =>'',
        'encoding'  => ''
    ];

    /**
     * start DOMDocument, optionally create new document
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        Loader::tracer('start xml class', debug_backtrace(), '7E3A02');

        $this->_options = array_merge($this->_options, $options);

        parent::__construct(
            $this->_options['version'],
            $this->_options['encoding']
        );
    }

    /**
     * load xml file, optionally check file DTD
     *
     * @param string $url xml file path
     * @param boolean $parse if TRUE will check file DTD
     * @return boolean
     * @example loadXml('cfg/config.xml', TRUE)
     */
    public function loadXmlFile($url, $parse = FALSE)
    {
        Loader::tracer('load xml file', debug_backtrace(), '7E3A02');

        $this->preserveWhiteSpace    = FALSE;
        $bool                        = @file_exists($url);

        if (!$bool) {
            $this->err = 'file_not_exists';
            return FALSE;
        }

        $bool = $this->load($url);
        if (!$bool) {
            $this->err = 'loading_file_error';
            return FALSE;
        }

        if ($parse && !@$this->validate()) {
            $this->err = 'parse_file_error';
            return FALSE;
        }

        return TRUE;
    }

    /**
     * save xml file, optionally will return as string
     *
     * @param string $url xml file path
     * @param boolean $asString if TRUE return as string
     * @return string|boolean
     * @example saveXml('sciezka/plik.xml'); zapis do pliku
     * @example saveXml(0, 1) zwraca jako tekst
     */
    public final function saveXmlFile($url, $asString = FALSE)
    {
        Loader::tracer('save xml file', debug_backtrace(), '7E3A02');

        $this->formatOutput = TRUE;

        if ($url) {
            $bool = $this->save($url);
            if (!$bool) {
                $this->err = 'save_file_error';
                return FALSE;
            }
        }

        if ($asString) {
            $data = $this->saveXML();
            return $data;
        }

        return TRUE;
    }

    /**
     * generate free numeric id
     *
     * @return integer|boolean return ID or NULL if there wasn't any node
     */
    public final function getFreeId()
    {
        $root = $this->documentElement;

        if ($root->hasChildNodes()) {
            $tab    = $this->_searchByAttribute($root->childNodes, 'id');
            $tab[]  = 'create_new_free_id';
            $id     = array_keys($tab, 'create_new_free_id');

            unset($tab);
            $this->idList = $id;
            return $id[0];
        }

        return NULL;
    }

    /**
     * search node for elements that contains element with give attribute
     *
     * @param DOMNodeList $node
     * @param string $value attribute value to search
     * @param array|boolean $list list of find nodes for recurrence
     * @return array
     */
    private function _searchByAttribute(
        DOMNodeList $node,
        $value,
        array $list = array()
    ){
        /** @var DomElement $child */
        foreach ($node as $child) {
            if($child->nodeType === 1){

                if ($child->hasChildNodes()) {
                    $list = $this->_searchByAttribute(
                        $child->childNodes,
                        $value,
                        $list
                    );
                }

                $id = $child->getAttribute($value);
                if ($id) {
                    $list[$id] = $id;
                }
            }
        }

        return $list;
    }

    /**
     * search node for elements that contains element with give attribute
     *
     * @param DOMNodeList $node
     * @param string $value attribute value to search
     * @return array
     */
    public function searchByAttribute(DOMNodeList $node, $value)
    {
        return $this->_searchByAttribute($node, $value);
    }

    /**
     * check that element with given id exists
     *
     * @param string $id
     * @return boolean return TRUE if exists
     */
    public final function checkId($id)
    {
        $id = $this->getElementById($id);

        if ($id) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * shorter version to return element with given id
     *
     * @param string $id
     * @return DOMElement
     */
    public function getId($id)
    {
        $id = $this->getElementById($id);
        return $id;
    }
}