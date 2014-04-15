<?php
/**
 * allow to manage file as object
 *
 * @package     Core
 * @subpackage  Disc
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Disc_Model_File extends Core_Blue_Model_Object implements Core_Disc_Model_Interface
{
    /**
     * base configuration for file
     * 
     * @var array
     */
    protected $_fileData = [
        'main_path'             => '',
        'size'                  => 0,
        'to_delete'             => FALSE,
        'permissions'           => 0755,
        'at_time'               => NULL,
        'ct_time'               => NULL,
        'mt_time'               => NULL,
        'owner'                 => '',
        'content'               => '',
        'name'                  => '',
        'extension'             => '',
        'force_save'            => FALSE,
    ];

    /**
     * create file instance
     * 
     * @param array $data
     */
    public function __construct (array $data = [])
    {
        Loader::tracer('create file object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('file_object_instance_before', [&$data]);

        $data = array_merge($this->_fileData, $data);
        parent::__construct($data);

        Loader::callEvent('file_object_instance_after', [&$data]);
    }

    /**
     * remove file, or object data if file not exists
     *
     * @return Core_Disc_Model_File
     * @throws Exception
     */
    public function delete()
    {
        Loader::tracer('delete file object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('delete_file_object_instance_before', $this);

        if (Core_Incoming_Model_File::exist($this->_getFullPath())) {
            $bool  = Core_Disc_Helper_Common::delete($this->_getFullPath());

            if (!$bool) {
                Loader::callEvent('delete_file_object_instance_error', $this);
                throw new Exception ('unable to remove file: ' . $this->_getFullPath());
            }
        }

        $this->setData([]);
        Loader::callEvent('delete_file_object_instance_after', $this);
        return $this;
    }

    /**
     * save file object
     * 
     * @return Core_Disc_Model_File
     * @throws Exception
     */
    public function save()
    {
        Loader::tracer('save file object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('save_file_object_instance_before', $this);

        if (empty($this->_DATA)) {
            return $this;
        }

        if ($this->getToDelete()) {
            $this->_errorsList[] = 'file must be removed, cannot be saved: ' . $this->_getFullPath();
            Loader::callEvent('save_file_object_instance_error', $this);
            return $this;
        }

        $bool = Core_Disc_Helper_Common::mkfile(
            $this->getMainPath(),
            $this->_getFullName(),
            $this->getContent()
        );
        
        //ustawienie czasu modyfikacji itp

        @chmod($this->_getFullPath(), $this->getPermissions());

        if (!$bool) {
            Loader::callEvent('save_file_object_instance_error', $this);
            throw new Exception ('save file: ' . $this->_getFullPath());
        }

        Loader::callEvent('save_file_object_instance_after', $this);
        return $this;
    }

    /**
     * load file into object
     * 
     * @return Core_Disc_Model_File
     * @throws Exception
     */
    public function load()
    {
        Loader::tracer('load file object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('load_file_object_instance_before', $this);

        if (!Core_Incoming_Model_File::exist($this->_getFullPath())) {
            Loader::callEvent('load_file_object_instance_error', $this);
            throw new Exception ('file not exists: ' . $this->_getFullPath());
        }

        $content = file_get_contents($this->_getFullPath());
        $this->setContent($content);

        Loader::callEvent('load_file_object_instance_after', $this);
        return $this;
    }

    /**
     * return full path with file name and extension
     * 
     * @return string
     */
    protected function _getFullPath()
    {
        return $this->getMainPath() . $this->_getFullName();
    }

    /**
     * return file name with extension
     * 
     * @return string
     */
    protected function _getFullName()
    {
        return $this->getName() . '.' . $this->getExtension();
    }

    /**
     * move file to other location
     *
     * @param $destination
     */
    public function move($destination){
        if (Core_Incoming_Model_File::exist($this->_getFullPath())) {
            $targetPath = $destination . $this->_getFullName();
            $bool = Core_Disc_Helper_Common::move($this->_getFullPath(), $targetPath);
        } else {
            $bool = Core_Disc_Helper_Common::mkfile(
                $destination,
                $this->_getFullName(),
                $this->getContent()
            );
        }

        if (!$bool) {
            throw new Exception (
                'unable to move file:'
                . $this->_getFullPath()
                . ' -> '
                . $destination
            );
        }

        $this->_setFileTimes();
        $this->setMainPath($destination);
        $this->replaceDataArrays();

        return $this;
    }

    /**
     * 
     */
    public function copy($destination)
    {
        if (Core_Incoming_Model_File::exist($this->_getFullPath())) {
            $targetPath = $destination . $this->_getFullName();
            Core_Disc_Helper_Common::copy($this->_getFullPath(), $targetPath);
        } else {
            $bool = Core_Disc_Helper_Common::mkfile(
                $destination,
                $this->_getFullName(),
                $this->getContent()
            );
        }

        if ($bool) {
            $data = $this->getData();
            $data['main_path'] = $destination;
            //ustawianie czasu zapisu itp
            /** @var Core_Disc_Model_File $dir */
            return Loader::getClass('Core_Disc_Model_File', $data);
        }
        return FALSE;
    }

    public function putContent()
    {
        
    }

    public function replaceContent()
    {
        
    }

    public function rename($newName)
    {
        
    }

    public function setPermissions($permissions)
    {
        
    }

    /**
     * set file modification time
     * 
     * @return Core_Disc_Model_File
     */
    protected function _setFileTimes()
    {
//        $time = time();
        
        
        //odczytywane po operacji na pliku (realnym save load itp)

//        if ($types & Core_Disc_Model_Interface::ACCESS_TIME) {
//            $this->setAtTime($time);
//        }
//
//        if ($types & Core_Disc_Model_Interface::CHANGE_TIME) {
//            $this->setCtTime($time);
//        }
//
//        if ($types & Core_Disc_Model_Interface::MODIFICATION_TIME) {
//            $this->setMtTime($time);
//        }

        return $this;
    }

    /**
     * destroy file object (can remove or save file)
     */
    public function __destruct()
    {
        try {
            if ($this->getForceSave() && !$this->getToDelete()) {
                $this->save();
            }

            if ($this->getToDelete()) {
                $this->delete();
            }
        } catch (Exception $e) {
            Loader::exceptions($e, 'file io operation');
        }
    }
}
