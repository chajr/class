<?php
/**
 * data table model interface
 *
 * @package     Core
 * @subpackage  Db
 * @author      chajr <chajr@bluetree.pl>
 */
interface Core_Db_Model_Resource_Interface
{
    public function save();
    public function load();
    public function orderBy($rule);
    public function page($page);
    public function pageSize($size);
    public function where($rule);
    public function delete();
}