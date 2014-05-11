<?php
/**
 * base class to create real data models
 * one model == one table in database
 *
 * @package     Core
 * @subpackage  Db
 * @author      chajr <chajr@bluetree.pl>
 */
abstract class Core_Db_Model_Resource_Abstract
    extends Core_Blue_Model_Object
    implements Core_Db_Model_Resource_Interface
{
    const TABLE_STRUCTURE_CACHE_PREFIX  = 'table_structure_';
    const DATA_TYPE_COLLECTION          = 'collection';
    const DATA_TYPE_OBJECT              = 'object';

    /**
     * resource table name
     * 
     * @var string
     */
    protected $_tableName;

    /**
     * id column name
     * 
     * @var string
     */
    protected $_columnId;

    /**
     * resource table structure
     * 
     * @var array
     */
    protected $_tableStructure = [];

    /**
     * filter to retrieve data from database
     * 
     * @var array
     */
    protected $_filters = [];

    /**
     * main query used in current instance
     * 
     * @var string
     */
    protected $_query;

    /**
     * information about data type (object or collection)
     * 
     * @var string
     */
    protected $_dataType = self::DATA_TYPE_OBJECT;

    /**
     * number of returned from database rows
     * 
     * @var int
     */
    protected $_rows = 0;

    /**
     * name of key prefix for xml node
     * if array key was integer
     *
     * @var string
     */
    protected $_integerKeyPrefix = 'collection_index';

    /**
     * default page size for collection
     * default load all collection
     * NULL if not use pagination
     * 
     * @var int
     */
    protected $_pageSize = 10;

    /**
     * contains given page number
     * 
     * @var int
     */
    protected $_currentPage = 1;

    /**
     * contain current row number when transforming collection to object
     * 
     * @var int
     */
    protected $_collectionCounter;

    /**
     * list of order information
     * 
     * @var array
     */
    protected $_orderBy = [];

    /**
     * where clause
     * 
     * @var string
     */
    protected $_where = '';

    /**
     * create resource object
     * get table structure if not exist in cache
     */
    public function initializeObject()
    {
        $message = 'initialize resource model:' . $this->_tableName;
        Loader::tracer($message, debug_backtrace(), '000000');
        Loader::callEvent('initialize_resource_model_before', $this);

        try{
            if (empty($this->_tableStructure)) {
                $this->_tableStructure = $this->_tableStructure();
                if (!$this->_tableStructure) {
                    $this->_tableStructure = $this->_returnTableStructure();
                    $this->_tableStructure($this->_tableStructure);
                }
            }
        } catch (Exception $e) {
            $this->_hasErrors       = TRUE;
            $this->_errorsList[]    = $e->getMessage();
            Loader::exceptions($e, 'initialize resource', 'database');
        }
    }

    /**
     * call event after initialize resource
     */
    public function afterInitializeObject()
    {
        Loader::callEvent('initialize_resource_model_after', $this);
    }

    /**
     * get or set table structure from cache
     * 
     * @param mixed $structure
     * @return mixed
     */
    protected function _tableStructure($structure = NULL)
    {
        /** @var Core_Blue_Model_Cache $cache */
        $cache = Loader::getClass('Core_Blue_Model_Cache');
        $name  = self::TABLE_STRUCTURE_CACHE_PREFIX . $this->_tableName;

        if ($structure) {
            $readyData = serialize($structure);
            return $cache->setCache($name, $readyData);
        } else {
            return unserialize($cache->getCache($name));
        }
    }

    /**
     * read table structure from database
     * 
     * @return array
     * @throws Exception
     */
    protected function _returnTableStructure()
    {
        $this->_query       = 'DESCRIBE ' . $this->_tableName;
        $result             = $this->_executeQuery($this->_query);
        $structure          = $result->fullResult();

        if (empty($structure)) {
            throw new Exception('missing table structure');
        }

        return $structure;
    }

    /**
     * allow to set new table name
     * (require to reinitialize object)
     * 
     * @param string $tableName
     * @return Core_Db_Model_Resource_Abstract
     */
    public function tableName($tableName)
    {
        $this->_tableName = $tableName;
        return $this;
    }

    /**
     * reinitialize object
     * 
     * @return Core_Db_Model_Resource_Abstract
     */
    public function reinitialize()
    {
        $this->initializeObject();
        $this->afterInitializeObject();

        return $this;
    }

    /**
     * return model table name
     * 
     * @return string
     */
    public function returnTableName()
    {
        return $this->_tableName;
    }

    /**
     * allow to set new name of id column
     * 
     * @param string $columnId
     * @return Core_Db_Model_Resource_Abstract
     */
    public function columnId($columnId)
    {
        $this->_columnId = $columnId;
        return $this;
    }

    /**
     * return column id name
     * 
     * @return string
     */
    public function returnColumnId()
    {
        return $this->_columnId;
    }

    /**
     * return full table structure information
     * 
     * @return array
     */
    public function tableStructure()
    {
        return $this->_tableStructure;
    }

    /**
     * load single row or all data from table
     * 
     * @param null|string|integer $id
     * @param null|string $column
     * @return Core_Db_Model_Resource_Abstract
     */
    public function load($id = NULL, $column = NULL)
    {
        $message = 'load resource:' . $this->_tableName . ', ' . $id;
        Loader::tracer($message, debug_backtrace(), '000000');
        Loader::callEvent('load_data_to_resource_before', [$this, &$id, &$column]);

        $this->_query = 'SELECT * FROM ' . $this->_tableName;

        if ($id && !$column) {
            $this->where($this->_columnId . " = '$id'");
        } else if ($id && $column) {
            $this->where($column . " = '$id'");
        }

        $this->_applyWhere()->_applyOrder()->_applyPageSize();

        try {
            $this->unsetData();
            $resource = $this->_executeQuery();
            $this->_createCollection($resource);
            $this->replaceDataArrays();
            Loader::callEvent('load_data_to_resource_after', [$this, $id, $resource]);
        } catch (Exception $e) {
            Loader::callEvent('load_data_to_resource_error', [$this, $id, $e]);
            $this->_hasErrors       = TRUE;
            $this->_errorsList[]    = $e->getMessage();
            Loader::exceptions($e, 'load resource', 'database');
        }

        return $this;
    }

    /**
     * add limit to main query
     * 
     * @return Core_Db_Model_Resource_Abstract
     */
    protected function _applyPageSize()
    {
        if ($this->_pageSize && $this->_currentPage) {
            $start           = ($this->_currentPage -1) * $this->_pageSize;
            $this->_query   .= " LIMIT $start, " . $this->_pageSize;
        }

        return $this;
    }

    /**
     * return data of first element in collection
     * default return as array, but can return as blue object
     * 
     * @param bool $asObject
     * @return Core_Blue_Model_Object|mixed|null
     */
    public function returnFirstItem($asObject = FALSE)
    {
        return $this->returnRow(0, $asObject);
    }

    /**
     * return last element in collection
     * 
     * @param bool $asObject
     * @return Core_Blue_Model_Object|mixed|null
     */
    public function returnLastItem($asObject = FALSE)
    {
        $index = $this->_rows -1;
        return $this->returnRow($index, $asObject);
    }

    /**
     * return whole collection
     * 
     * @return mixed|null
     */
    public function returnCollection()
    {
        if ($this->_dataType === self::DATA_TYPE_COLLECTION) {
            return $this->getData();
        }

        return NULL;
    }

    /**
     * return row with given index
     * 
     * @param integer $rowIndex
     * @param bool $asObject
     * @return Core_Blue_Model_Object|Core_Db_Model_Resource_Abstract|mixed|null
     */
    public function returnRow($rowIndex, $asObject = FALSE)
    {
        if ($this->_dataType === self::DATA_TYPE_COLLECTION) {
            $key = $this->_integerToStringKey($rowIndex);
            /** @var Core_Blue_Model_Object $object */
            $object = $this->getData($key);

            if ($object instanceof Core_Blue_Model_Object && $asObject) {
                return $object;
            }

            if ($object instanceof Core_Blue_Model_Object) {
                return $object->getData();
            }

            return NULL;
        }

        if ($asObject) {
            return $this;
        }
        return $this->getData();
    }

    /**
     * set returned data as object data or collection of objects
     * 
     * @param Core_Db_Helper_Mysql $resource
     * @return Core_Db_Model_Resource_Abstract
     */
    protected function _createCollection(Core_Db_Helper_Mysql $resource)
    {
        $result         = $resource->fullResult();
        $this->_rows    = $resource->rows;

        if ($resource->rows === 0) {
            return $this;
        }

        if ($resource->rows === 1) {
            $this->setData($result[0]);
            $this->_dataType = self::DATA_TYPE_OBJECT;
        } else {
            $this->_transformRowsToObject($result);
            $this->_dataType = self::DATA_TYPE_COLLECTION;
        }

        return $this;
    }

    /**
     * convert array of dta from database to Core_Blue_Model_Object
     * 
     * @param array $result
     * @return $this
     */
    protected function _transformRowsToObject(array $result)
    {
        $this->_collectionCounter = 0;

        foreach ($result as $row) {
            $object = Loader::getClass('Core_Blue_Model_Object', $row);
            $key    = $this->_integerToStringKey($this->_collectionCounter);
            $this->setData($key, $object);
            $this->_collectionCounter++;
        }

        return $this;
    }

    /**
     * return number of elements in collection
     * 
     * @return int
     */
    public function returnCollectionSize()
    {
        return $this->_collectionCounter;
    }

    /**
     * convert data saved in object to query string
     * 
     * @return string
     * @todo collection handling
     */
    protected function _transformStructureToUpdate()
    {
        $query = 'UPDATE ' . $this->_tableName . ' SET ';
        foreach ($this->_tableStructure as $column) {
            $field  = $column['Field'];
            $isNull = !$this->getData($field);
            $isId   = $column['Field'] === $this->_columnId;

            if ($isNull || $isId) {
                continue;
            }

            $query .= $field . ' = \'' . $this->getData($field) . '\',';
        }

        return rtrim($query, ',');
    }

    /**
     * convert data saved in object to update query
     * 
     * @return string
     */
    protected function _transformStructureToInsert()
    {
        $fields = '';
        $data   = '';
        $query  = 'INSERT INTO ' . $this->_tableName . ' ';

        if ($this->_dataType === self::DATA_TYPE_OBJECT) {
            foreach ($this->_tableStructure as $column) {
                if ($this->getData($column['Field'])) {
                    $fields .= $column['Field'] . ',';
                    $data   .= '\'' . $this->getData($column['Field']) . '\',';
                }
            }

            $fields = rtrim($fields, ',');
            $data   = rtrim($data, ',');
            $query  .= '(' . $fields . ') VALUES (' . $data . ')';
        } elseif ($this->_dataType === self::DATA_TYPE_COLLECTION) {
            
        }

        return $query;
    }

    /**
     * prepare delete query
     * 
     * @return string
     */
    protected function _prepareDeleteQuery()
    {
        return 'DELETE FROM ' . $this->_tableName . ' ';
    }

    /**
     * allow to save data from model
     * 
     * @return Core_Db_Model_Resource_Abstract
     * @todo collection handling
     */
    public function save()
    {
        $message = 'save resource:' . $this->_tableName;
        Loader::tracer($message, debug_backtrace(), '000000');
        Loader::callEvent('save_resource_data_before', $this);

        if ($this->_dataType === self::DATA_TYPE_OBJECT) {
            $id = $this->getData($this->_columnId);
            if ($id) {
                $this->_query = $this->_transformStructureToUpdate();
                $this->where($this->_columnId . " = '$id'");
            } else {
                $this->where('');
                $this->_query = $this->_transformStructureToInsert();
            }
        }

        try {
            $this->_applyWhere()->_executeQuery();
            Loader::callEvent('save_resource_data_after', $this);
        } catch (Exception $e) {
            $this->_hasErrors       = TRUE;
            $this->_errorsList[]    = $e->getMessage();
            Loader::callEvent('save_resource_data_error', [$this, $e]);
            Loader::exceptions($e, 'save error', 'database');
        }

        return $this;
    }

    /**
     * remove row loaded to object, or some other row with given id
     * 
     * @param null|string|integer $id
     * @param null|string $column
     * @return Core_Db_Model_Resource_Abstract
     */
    public function delete($id = NULL, $column = NULL)
    {
        $message = 'delete resource:' . $this->_tableName;
        Loader::tracer($message, debug_backtrace(), '000000');
        Loader::callEvent('delete_resource_data_before', [$this, &$id, &$column]);

        if ($this->_dataType === self::DATA_TYPE_OBJECT) {
            if (!$id) {
                $id = $this->getData($this->_columnId);
            }

            if ($id && !$column) {
                $this->where($this->_columnId . " = '$id'");
            } else if ($id && $column) {
                $this->where($column . " = '$id'");
            }

            $this->_prepareDeleteQuery();
        }

        try {
            $this->_applyWhere()->_executeQuery();
            Loader::callEvent('delete_resource_data_after', $this);
        } catch (Exception $e) {
            $this->_hasErrors       = TRUE;
            $this->_errorsList[]    = $e->getMessage();
            Loader::callEvent('delete_resource_data_error', [$this, $e]);
            Loader::exceptions($e, 'delete error', 'database');
        }

        return $this;
    }

    public function addFilter()
    {
        
    }

    public function returnFilters()
    {
        
    }

    public function removeFilter()
    {
        
    }

    /**
     * add order to list
     * 
     * @param string $rule
     * @return Core_Db_Model_Resource_Abstract
     */
    public function orderBy($rule)
    {
        $this->_orderBy[] = $rule;
        return $this;
    }

    /**
     * sets where clause
     * 
     * @param string $rule
     * @return Core_Db_Model_Resource_Abstract
     */
    public function where($rule)
    {
        $this->_where = $rule;
        return $this;
    }

    /**
     * apply where clause to existing one
     * (remember ot add AND | OR before)
     * 
     * @param string $rule
     * @return Core_Db_Model_Resource_Abstract
     */
    public function applyWhere($rule)
    {
        $this->_where .= $rule;
        return $this;
    }

    /**
     * return array of orders
     * 
     * @return array
     */
    public function returnOrder()
    {
        return $this->_orderBy;
    }

    /**
     * return where string
     * 
     * @return string
     */
    public function returnWhere()
    {
        return $this->_where;
    }

    /**
     * apply order to main query
     * 
     * @return Core_Db_Model_Resource_Abstract
     */
    protected function _applyOrder()
    {
        if (!empty($this->_orderBy)) {
            $this->_query .= ' ORDER BY ';

            foreach ($this->_orderBy as $order) {
                $this->_query .= $order . ',';
            }

            $this->_query = rtrim($this->_query, ',');
        }
        return $this;
    }

    /**
     * apply where to main query
     * 
     * @return Core_Db_Model_Resource_Abstract
     */
    protected function _applyWhere()
    {
        if ($this->_where) {
            $this->_query .= ' WHERE ' . $this->_where;
        }
        return $this;
    }

    /**
     * set limit for collection
     * (use pagination methods)
     * 
     * @param integer $start
     * @param integer $count
     * @return Core_Db_Model_Resource_Abstract
     */
    public function limit($start, $count)
    {
        $this->_pageSize    = $count;
        $this->_currentPage = $start;

        return $this;
    }

    /**
     * @return string
     */
    public function returnQuery()
    {
        return $this->_query;
    }

    /**
     * allow to replace created by model query
     * 
     * @param string $query
     * @return Core_Db_Model_Resource_Abstract
     */
    public function replaceQuery($query)
    {
        $this->_query = $query;
        return $this;
    }

    /**
     * return data type
     * 
     * @return string
     */
    public function returnDataType()
    {
        return $this->_dataType;
    }

    /**
     * execute given query and checks that there was some errors
     * 
     * @return Core_Db_Helper_Mysql
     * @throws Exception
     */
    protected function _executeQuery()
    {
        /** @var Core_Db_Helper_Mysql $result */
        $result      = Loader::getClass('Core_Db_Helper_Mysql', $this->_query);
        $hasErrors   = $result->err;

        if ($result->id) {
            $this->setData($this->_columnId, $result->id);
        }

        if ($hasErrors) {
            throw new Exception('error with resource query ' . $hasErrors);
        }

        return $result;
    }

    /**
     * allow to set page size
     * 
     * @param integer $size
     * @return Core_Db_Model_Resource_Abstract
     */
    public function pageSize($size)
    {
        $this->_pageSize = $size;
        return $this;
    }

    /**
     * retrieve page size value
     * 
     * @return int
     */
    public function returnPageSize()
    {
        return $this->_pageSize;
    }

    /**
     * set page number to retrieve
     * 
     * @param integer $page
     * @return Core_Db_Model_Resource_Abstract
     */
    public function page($page)
    {
        $this->_currentPage = $page;
        return $this;
    }

    /**
     * return current page number
     * 
     * @return int
     */
    public function returnCurrentPage()
    {
        return $this->_currentPage;
    }

    /**
     * allow to load all collection without using pagination
     * 
     * @param mixed $id
     * @param null|string $column
     * @return Core_Db_Model_Resource_Abstract
     */
    public function loadAll($id, $column = NULL)
    {
        $pageSize           = $this->_pageSize;
        $this->_pageSize    = NULL;

        $this->load($id, $column);

        $this->_pageSize = $pageSize;
        return $this;
    }

    /**
     * return number of returned from database rows
     * 
     * @return int
     */
    public function returnLoadedRows()
    {
        return $this->_rows;
    }
}
