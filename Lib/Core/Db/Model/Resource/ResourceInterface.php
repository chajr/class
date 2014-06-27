<?php
/**
 * data table model interface
 *
 * @package     Core
 * @subpackage  Db
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Db\Model\Resource;
interface ResourceInterface
{
    const TABLE_STRUCTURE_CACHE_PREFIX  = 'table_structure_';
    const DATA_TYPE_COLLECTION          = 'collection';
    const DATA_TYPE_OBJECT              = 'object';
    const MODEL_TYPE_SINGLE             = 'single';
    const MODEL_TYPE_MULTI              = 'multi';
    const MAIN_TABLE                    = 'main_table';

    public function save();
    public function load($id, $column);
    public function orderBy($rule);
    public function page($page);
    public function pageSize($size);
    public function where($rule);
    public function delete();
}
