<?php
/**
 * base class to create data models from many tables
 * one model == couple of table merged with join clause
 * 
 * @package     Core
 * @subpackage  Db
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Db\Model\Resource\Multi;
use Core\Db\Model\Resource\ResourceAbstract;
abstract class MultiAbstract extends ResourceAbstract
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
     * @return MultiAbstract
     */
    public function load($id = NULL, $column = NULL)
    {
        $this->_loadBegin($id, $column);
        $this->_applyMainTable();

        if ($id && !$column) {
            $this->where(self::MAIN_TABLE . '.' . $this->_columnId . " = '$id'");
        } else if ($id && $column) {
            $this->where($column . " = '$id'");
        }

        return $this->_applyJoin()
            ->_applyWhere()
            ->_applyOrder()
            ->_applyPageSize()
            ->_loadEnd($id);
    }

    /**
     * apply join instructions
     * 
     * @return MultiAbstract
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

    /**
     * remove data from all joined tables
     * 
     * @param null|int $id
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
            $this->where(self::MAIN_TABLE . '.' . $this->_columnId . " = '$id'");
        } else if ($id && $column) {
            $this->where($column . " = '$id'");
        }

        return $this->_prepareDeleteQuery()
            ->_applyMainTable(TRUE)
            ->_applyJoin()
            ->_applyWhere()
            ->_deleteEnd();
    }

    /**
     * prepare delete query
     *
     * @return ResourceAbstract|MultiAbstract
     */
    protected function _prepareDeleteQuery()
    {
        $this->_query = 'DELETE FROM '
            . self::MAIN_TABLE
            . ' USING '
            . $this->_tableName
            . ' ';

        return $this;
    }

    /**
     * apply main table alias name
     * 
     * @return MultiAbstract
     */
    protected function _applyMainTable()
    {
        $this->_query .= ' AS ' . self::MAIN_TABLE;
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
//        $this->_saveBegin();
//
//        if ($this->_dataType === self::DATA_TYPE_OBJECT) {
//            $id = $this->getData($this->_columnId);
//            if ($id) {
//                $this->_query = $this->_transformStructureToUpdate();
//                $this->where($this->_columnId . " = '$id'");
//            } else {
//                $this->where('');
//                $this->_query = $this->_transformStructureToInsert();
//            }
//        }
//
//        return $this->_applyWhere()->_saveEnd();
    }
}
