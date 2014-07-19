<?php
/**
 * base class to create real data models
 * one model == one table in database
 *
 * @package     Core
 * @subpackage  Db
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Db\Model\Resource;
use Core\Db\Model\Resource\Multi\MultiAbstract;
use Core\Db\Helper\Connection;
use Core\Db\Helper\Mysql;
use Core\Blue\Model;
use Loader;
use Exception;
abstract class ResourceAbstract extends Model\Object implements ResourceInterface
{
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
     * type of model
     * 
     * @var string
     */
    protected $_modelType = self::MODEL_TYPE_SINGLE;

    /**
     * create resource object
     * get table structure if not exist in cache
     */
    public function initializeObject()
    {
        $message = 'initialize '
            . $this->_modelType
            . ' resource model:'
            . $this->_tableName;

        Loader::tracer($message, debug_backtrace(), '000000');
        Loader::callEvent(
            'initialize_' . $this->_modelType . '_resource_model_before',
            $this
        );

        try{
            if (empty($this->_tableStructure)) {
                $this->_tableStructure = $this->_tableStructure();
            }
        } catch (Exception $e) {
            $this->_hasErrors       = TRUE;
            $this->_errorsList[]    = $e->getMessage();
            Loader::exceptions(
                $e,
                'initialize ' . $this->_modelType . ' resource',
                'database'
            );
        }
    }

    /**
     * call event after initialize resource
     */
    public function afterInitializeObject()
    {
        Loader::callEvent(
            'initialize_' . $this->_modelType . '_resource_model_after', $this
        );
    }

    /**
     * get or set table structure from cache
     * 
     * @param mixed $structure
     * @return mixed
     */
    protected function _tableStructure($structure = NULL)
    {
        $structure = $this->_prepareStructure($this->_tableName, $structure);
        return $this->_checkTableStructure($this->_tableName, $structure);
    }

    /**
     * check if cache return table structure
     * if not lunch method to read table structure
     * 
     * @param string $table
     * @param array|null $structure
     * @return array
     */
    protected function _checkTableStructure($table, $structure)
    {
        if (!$structure) {
            return $this->_getTableStructure($table);
        }

        return $structure;
    }

    /**
     * get or set table structure from cache for given table
     * 
     * @param string $tableName
     * @param null|array $structure
     * @return mixed
     */
    protected function _prepareStructure($tableName, $structure = NULL)
    {
        /** @var Model\Cache $cache */
        $cache = Loader::getClass('Core\Blue\Model\Cache');
        $name  = self::TABLE_STRUCTURE_CACHE_PREFIX . $tableName;

        if ($structure) {
            $readyData = serialize($structure);
            return $cache->setCache($name, $readyData);
        } else {
            return unserialize($cache->getCache($name));
        }
    }

    /**
     * read table structure from database for given table
     * 
     * @param string $table
     * @return array
     * @throws Exception
     */
    protected function _getTableStructure($table)
    {
        $this->_query       = 'DESCRIBE ' . $table;
        $result             = $this->_executeQuery($this->_query);
        $structure          = $result->fullResult();

        if (empty($structure)) {
            throw new Exception('missing table structure: ' . $table);
        }

        return $structure;
    }

    /**
     * allow to set new table name
     * (require to reinitialize object)
     * 
     * @param string $tableName
     * @return ResourceAbstract
     */
    public function tableName($tableName)
    {
        $this->_tableName = $tableName;
        return $this;
    }

    /**
     * reinitialize object
     * 
     * @return ResourceAbstract
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
     * @return ResourceAbstract
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
     * @return ResourceAbstract
     */
    public function load($id = NULL, $column = NULL)
    {
        $this->_loadBegin($id, $column);

        if ($id && !$column) {
            $this->where($this->_columnId . " = '$id'");
        } else if ($id && $column) {
            $this->where($column . " = '$id'");
        }

        return $this->_applyWhere()
            ->_applyOrder()
            ->_applyPageSize()
            ->_loadEnd($id);
    }

    /**
     * begin load data
     * 
     * @param int $id
     * @param string|null $column
     * @return ResourceAbstract
     */
    protected function _loadBegin(&$id, &$column)
    {
        $message = 'load resource:' . $this->_tableName . ', ' . $id;
        Loader::tracer($message, debug_backtrace(), '000000');
        Loader::callEvent(
            'load_data_to_' . $this->_modelType . '_resource_before',
            [$this, &$id, &$column]
        );

        $this->_query = 'SELECT ' . $this->_applySelectFilter() . ' FROM ' . $this->_tableName;

        return $this;
    }

    /**
     * finish load and execute query
     * 
     * @param int $id
     * @return ResourceAbstract
     */
    protected function _loadEnd($id)
    {
        try {
            $this->unsetData();
            $resource = $this->_executeQuery();
            $this->_createCollection($resource);
            $this->replaceDataArrays();
            Loader::callEvent(
                'load_data_to_' . $this->_modelType . '_resource_after',
                [$this, $id, $resource]
            );
        } catch (Exception $e) {
            Loader::callEvent(
                'load_data_to_' . $this->_modelType . '_resource_error',
                [$this, $id, $e]
            );
            $this->_hasErrors       = TRUE;
            $this->_errorsList[]    = $e->getMessage();
            Loader::exceptions($e, 'load resource', 'database');
        }

        return $this;
    }

    /**
     * apply select data filter for load method
     * 
     * @return string
     */
    protected function _applySelectFilter()
    {
        $select = '';

        if (!empty($this->_filters)) {
            foreach ($this->_filters as $filter) {
                $select .= $filter . ',';
            }
            return rtrim($select, ',');
        }

        return '*';
    }

    /**
     * add limit to main query
     * 
     * @return ResourceAbstract
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
     * @return Model\Object|mixed|null
     */
    public function returnFirstItem($asObject = FALSE)
    {
        return $this->returnRow(0, $asObject);
    }

    /**
     * return last element in collection
     * 
     * @param bool $asObject
     * @return Model\Object|mixed|null
     */
    public function returnLastItem($asObject = FALSE)
    {
        $index = $this->_rows -1;
        return $this->returnRow($index, $asObject);
    }

    /**
     * return whole collection
     * 
     * @return mixed|array
     */
    public function returnCollection()
    {
        if ($this->_dataType === self::DATA_TYPE_COLLECTION) {
            return $this->getData();
        }

        return [];
    }

    /**
     * return row with given index
     * 
     * @param integer $rowIndex
     * @param bool $asObject
     * @return Model\Object|ResourceAbstract|mixed|null
     */
    public function returnRow($rowIndex, $asObject = FALSE)
    {
        if ($this->_dataType === self::DATA_TYPE_COLLECTION) {
            $key = $this->_integerToStringKey($rowIndex);
            /** @var Model\Object $object */
            $object = $this->getData($key);

            if ($object instanceof Model\Object && $asObject) {
                return $object;
            }

            if ($object instanceof Model\Object) {
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
     * @param Mysql $resource
     * @return ResourceAbstract
     */
    protected function _createCollection(Mysql $resource)
    {
        $result         = $resource->fullResult();
        $this->_rows    = $resource->rows;

        if ($resource->rows === 0 || $resource->rows === NULL) {
            return $this;
        }

        if ($resource->rows === 1) {
            $this->setData($result[0]);
            $this->_dataType = self::DATA_TYPE_OBJECT;
        } else {
            $this->_transformRowsToObject($result[0]);
            $this->_dataType = self::DATA_TYPE_COLLECTION;
        }

        return $this;
    }

    /**
     * convert array of dta from database to Model\Object
     * 
     * @param array $result
     * @return ResourceAbstract
     */
    protected function _transformRowsToObject(array $result)
    {
        $this->_collectionCounter = 0;

        foreach ($result as $row) {
            $object = Loader::getClass('Model\Object', ['data' => $row]);
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
     * @return ResourceAbstract|MultiAbstract
     */
    protected function _prepareDeleteQuery()
    {
        $this->_query = 'DELETE FROM ' . $this->_tableName . ' ';
        return $this;
    }

    /**
     * allow to save data from model
     * 
     * @return ResourceAbstract
     * @todo collection handling
     */
    public function save()
    {
        $this->_saveBegin();

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

        return $this->_applyWhere()->_saveEnd();
    }

    /**
     * begin insert/update data
     * 
     * @return ResourceAbstract
     */
    protected function _saveBegin()
    {
        $message = 'save resource:' . $this->_tableName;
        Loader::tracer($message, debug_backtrace(), '000000');
        Loader::callEvent(
            'save_' . $this->_modelType . '_resource_data_before',
            $this
        );

        return $this;
    }

    /**
     * finish insert/update and execute query
     * 
     * @return ResourceAbstract
     */
    protected function _saveEnd()
    {
        try {
            $this->_executeQuery();
            Loader::callEvent(
                'save_' . $this->_modelType . '_resource_data_after',
                $this
            );
        } catch (Exception $e) {
            $this->_hasErrors       = TRUE;
            $this->_errorsList[]    = $e->getMessage();
            Loader::callEvent(
                'save_' . $this->_modelType . '_resource_data_error',
                [$this, $e]
            );
            Loader::exceptions($e, 'save error', 'database');
        }

        return $this;
    }

    /**
     * remove row loaded to object, or some other row with given id
     * 
     * @param null|string|integer $id
     * @param null|string $column
     * @return ResourceAbstract
     */
    public function delete($id = NULL, $column = NULL)
    {
        $this->_deleteBegin($id, $column);

        if ($this->_dataType === self::DATA_TYPE_OBJECT) {
            if (!$id) {
                $id = $this->getData($this->_columnId);
            }
        }

        if ($id && !$column) {
            $this->where($this->_columnId . " = '$id'");
        } else if ($id && $column) {
            $this->where($column . " = '$id'");
        }

        return $this->_prepareDeleteQuery()
            ->_applyWhere()
            ->_deleteEnd();
    }

    /**
     * begin deleting data
     * 
     * @param int|null $id
     * @param string|null $column
     * @return ResourceAbstract
     */
    protected function _deleteBegin(&$id, &$column)
    {
        $message = 'delete resource:' . $this->_tableName;
        Loader::tracer($message, debug_backtrace(), '000000');
        Loader::callEvent(
            'delete_' . $this->_modelType . '_resource_data_before',
            [$this, &$id, &$column]
        );

        return $this;
    }

    /**
     * finish deleting and execute query
     * 
     * @return ResourceAbstract
     */
    protected function _deleteEnd()
    {
        try {
            $this->_executeQuery();
            Loader::callEvent(
                'delete_' . $this->_modelType . '_resource_data_after',
                $this
            );
        } catch (Exception $e) {
            $this->_hasErrors       = TRUE;
            $this->_errorsList[]    = $e->getMessage();
            Loader::callEvent(
                'delete_' . $this->_modelType . '_resource_data_error',
                [$this, $e]
            );
            Loader::exceptions($e, 'delete error', 'database');
        }

        return $this;
    }

    /**
     * add select filter
     *
     * @param string $filter
     * @return ResourceAbstract
     */
    public function addFilter($filter)
    {
        $this->_filters[$filter] = $filter;
        return $this;
    }

    /**
     * list of existing filters
     *
     * @return array
     */
    public function returnFilters()
    {
        return array_keys($this->_filters);
    }

    /**
     * remove set up filter
     *
     * @param string $filter
     * @return ResourceAbstract
     */
    public function removeFilter($filter)
    {
        unset($this->_filters[$filter]);
        return $this;
    }

    /**
     * add order to list
     * 
     * @param string $rule
     * @return ResourceAbstract
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
     * @return ResourceAbstract
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
     * @return ResourceAbstract
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
     * @return ResourceAbstract
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
     * @return ResourceAbstract
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
     * @return ResourceAbstract
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
     * @return ResourceAbstract
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
     * @return Mysql
     * @throws Exception
     */
    protected function _executeQuery()
    {
        /** @var Mysql $result */
        $result         = Loader::getClass('Core\Db\Helper\Pdo\Mysql', $this->_query);
        $errorIsArray   = is_array($result->err);
        $noError        = $errorIsArray && $result->err[0] === '00000';

        if ($result->id) {
            $this->setData($this->_columnId, $result->id);
        }

        $this->_where = '';

        if ($result->err && !$noError) {
            if ($errorIsArray) {
                $error = implode(', ', $result->err);
            } else {
                $error = $result->err;
            }
            throw new Exception('error with resource query: ' . $error);
        }

        return $result;
    }

    /**
     * allow to set page size
     * 
     * @param integer $size
     * @return ResourceAbstract
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
     * @return ResourceAbstract
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
     * @return ResourceAbstract
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
