<?php
/**
 * allow to manage directory as object
 *
 * @package     Core
 * @subpackage  Disc
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Disc_Model_Directory extends Core_Blue_Model_Object implements Core_Disc_Model_Interface
{
    //trzymac obiekt w pamieci, tworzy przy wywolaniu save
    //trzyma wszystkie podfoldery jako obiekty i tak samo pliki
    
    protected $_directoryData = [
        'main_path'             => '',
        'child_files'           => [],
        'child_directories'     => [],
        'size'                  => 0,
        'file_count'            => 0,
        'directory_count'       => 0,
        'to_delete'             => [],
        'permisions'            => 0755,
        'at_time'               => NULL,
        'ct_time'               => NULL,
        'mt_time'               => NULL,
        'owner'                 => '',
    ];
    
    /**
     * create or read given directory
     *
     * @param $data
     * @return Core_Disc_Model_Directory
     */
    public function __construct(array $data)
    {
        $data = array_merge($this->_directoryData, $data);
        parent::__construct($data);
    }

    public function save()
    {
        $exists = Core_Incoming_Model_File::exist($this->getMainPath());
        
        if (!$exists) {
            Core_Disc_Helper_Common::mkdir($this->getMainPath());
        } else {
            
        }
    }

    public function addChildFile($name, $content = NULL)
    {
        
    }

    public function addChildDirectory($name)
    {
        
    }

    public function load()
    {
        
    }

    public function delete()
    {
        
    }

    /**
     *
     */
    public function move($destination)
    {

    }

    /**
     *
     */
    public function rename($newName)
    {

    }

    public function copy($destination)
    {
        
    }

    public function _readContent($recursive = FALSE)
    {
        
    }

    public function setPermissions($permissions)
    {

    }
}
