<?php
/**
 * base class to create real data models
 * one model == one table in database
 *
 * @package     Core
 * @subpackage  Db
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Db_Model_Resource_Multi_Abstract
    extends Core_Blue_Model_Object
    implements Core_Db_Model_Resource_Interface
{
    protected $_mainTable;

    protected $_tables = [];

    protected $_tableRelations;
    
    public function save()
    {
        
    }

    public function load()
    {
        
    }

    public function orderBy($rule)
    {
        
    }

    public function page($page)
    {
        
    }

    public function pageSize($size)
    {
        
    }

    public function where($rule)
    {
        
    }

    public function delete()
    {
        
    }
    
    /*
     * struktura
     * 
     * wszystkie tabele do pobrania trzymane w tablicy jako klucz=>kolumny do pobrania
     * w osobnej tabeli relacje miedzy tabelami
     * powyzsze stanowia integralna czesc modelu, czyli sa stale dla danej klasy
     * 
     * 
     * filtry ktore odpowiadaja (za to co bedzie pobrane, lub za relacje)
     */
}
