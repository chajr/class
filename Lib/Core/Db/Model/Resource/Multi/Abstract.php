<?php
/**
 * base class to create data models from many tables
 * one model == couple of table merged with join clause
 * 
 * @package     Core
 * @subpackage  Db
 * @author      chajr <chajr@bluetree.pl>
 */
abstract class Core_Db_Model_Resource_Multi_Abstract
    extends Core_Db_Model_Resource_Abstract
{
    /**
     * list of tables and types of join
     * 'table_name' => ['join type', 'main.id = other.id']
     * 
     * @var array
     */
    protected $_tables = [];

    /**
     * list of tables structure
     * 
     * @var array
     */
    protected $_tableStructure = [];

    /**
     * type of model
     *
     * @var string
     */
    protected $_modelType = self::MODEL_TYPE_MULTI;

    /**
     * get or set all tables structure from cache
     *
     * @param array|null $structure
     * @return mixed
     */
    protected function _tableStructure($structure = NULL)
    {
        if (isset($structure[self::MAIN_TABLE])) {
            $mainStructure = $structure[self::MAIN_TABLE];
        } else {
            $mainStructure = NULL;
        }

        $tables                         = array_keys($this->_tables);
        $mainTableStructure             = parent::_tableStructure($mainStructure);
        $tablesStructure[self::MAIN_TABLE]  = $this->_checkTableStructure(
            $this->_tableName,
            $mainTableStructure
        );

        foreach ($tables as $table) {
            if (isset($structure[$table])) {
                $structure = $this->_prepareStructure($table, $structure[$table]);
            } else {
                $structure = $this->_prepareStructure($table);
            }

            $tablesStructure[$table] = $this->_checkTableStructure($table, $structure);
        }

        return $tablesStructure;
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
        $this->_loadBegin($id, $column);
        $this->_query .= ' AS ' . self::MAIN_TABLE;

        if ($id && !$column) {
            $this->where(self::MAIN_TABLE . '.' . $this->_columnId . " = '$id'");
        } else if ($id && $column) {
            $this->where($column . " = '$id'");
        }

        $this->_applyJoin()->_applyWhere()->_applyOrder()->_applyPageSize()->_loadEnd($id);

        return $this;
    }

    /**
     * apply join instructions
     * 
     * @return Core_Db_Model_Resource_Abstract
     */
    protected function _applyJoin()
    {
        foreach ($this->_tables as $table => $config) {
            $join = strtoupper($config[0]);
            $this->_query .= ' ' . $join . ' ' . $table . ' ON ' . $config[1];
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
        } else {
            $select = self::MAIN_TABLE . '.*,';

            foreach (array_keys($this->_tables) as $table) {
                $select .= $table . '.*,';
            }
        }

        return rtrim($select, ',');
    }

    
    
    
    
    public function delete($id = NULL, $column = NULL)
    {
        
    }

    public function save()
    {

    }
}
