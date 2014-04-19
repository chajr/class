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
            $bool = Core_Disc_Helper_Common::delete($this->_getFullPath());

            if (!$bool) {
                Loader::callEvent('delete_file_object_instance_error', $this);
                throw new Exception ('unable to remove file: ' . $this->_getFullPath());
            }
        }

        $this->unsetData();
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

        @chmod($this->_getFullPath(), $this->getPermissions());
        $this->_updateFileInfo();

        if (!$bool) {
            Loader::callEvent('save_file_object_instance_error', $this);
            throw new Exception ('unable to save file: ' . $this->_getFullPath());
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
        Loader::tracer('load file into object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('load_file_object_instance_before', $this);

        if (!Core_Incoming_Model_File::exist($this->_getFullPath())) {
            Loader::callEvent('load_file_object_instance_error', $this);
            throw new Exception ('file not exists: ' . $this->_getFullPath());
        }

        $content = file_get_contents($this->_getFullPath());
        $this->_updateFileInfo();
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
        $mainPath = rtrim($this->getMainPath(), '/');
        return $mainPath . '/' . $this->_getFullName();
    }

    /**
     * return file name with extension
     * 
     * @return string
     */
    protected function _getFullName()
    {
        if ($this->getExtension()) {
            return $this->getName() . '.' . $this->getExtension();
        }

        return $this->getName();
    }

    /**
     * move file to other location
     *
     * @param string $destination
     * @throws Exception
     * @return Core_Disc_Model_File
     */
    public function move($destination){
        Loader::tracer('move file object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('move_file_object_instance_before', [$this, &$destination]);

        if (Core_Incoming_Model_File::exist($this->_getFullPath())) {
            $targetPath = $destination . $this->_getFullName();
            $bool       = Core_Disc_Helper_Common::move(
                $this->_getFullPath(),
                $targetPath
            );
        } else {
            $bool = Core_Disc_Helper_Common::mkfile(
                $destination,
                $this->_getFullName(),
                $this->getContent()
            );
        }

        if (!$bool) {
            Loader::callEvent('move_file_object_instance_error', [$this, $destination]);
            throw new Exception (
                'unable to move file:'
                . $this->_getFullPath()
                . ' -> '
                . $destination
            );
        }

        $this->setMainPath($destination);
        $this->_updateFileInfo();
        $this->replaceDataArrays();

        Loader::callEvent('move_file_object_instance_after', $this);
        return $this;
    }

    /**
     * copy file to other location
     * !!!! AFTER COPY RETURN INSTANCE OF COPIED FILE, NOT BASE FILE !!!!
     * 
     * @param string $destination
     * @return Core_Disc_Model_File
     * @throws Exception
     */
    public function copy($destination)
    {
        Loader::tracer('copy file object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('copy_file_object_instance_before', [$this, &$destination]);

        if (Core_Incoming_Model_File::exist($this->_getFullPath())) {
            $targetPath = $destination . $this->_getFullName();
            $bool       = Core_Disc_Helper_Common::copy(
                $this->_getFullPath(),
                $targetPath
            );
        } else {
            $bool = Core_Disc_Helper_Common::mkfile(
                $destination,
                $this->_getFullName(),
                $this->getContent()
            );
        }

        if (!$bool) {
            Loader::callEvent('copy_file_object_instance_error', [$this, $destination]);
            throw new Exception (
                'unable to copy file:'
                . $this->_getFullPath()
                . ' -> '
                . $destination
            );
        }

        $data               = $this->getData();
        $data['main_path']  = $destination;
        $this->_updateFileInfo();

        Loader::callEvent('copy_file_object_instance_after', [$this]);
        return Loader::getClass('Core_Disc_Model_File', $data)->load();
    }

    /**
     * rename file
     * 
     * @param string $name
     * @param null|string $extension
     * @return Core_Disc_Model_File
     * @throws Exception
     */
    public function rename($name, $extension = NULL)
    {
        Loader::tracer('rename file object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('rename_file_object_instance_before', [$this, &$name, &$extension]);

        $bool = TRUE;

        if (Core_Incoming_Model_File::exist($this->_getFullPath())) {
            $bool = Core_Disc_Helper_Common::move(
                $this->_getFullPath(),
                $name . '.' . $extension
            );
        }

        $this->setName($name);
        $this->setExtension($extension);

        if (!$bool) {
            Loader::callEvent('rename_file_object_instance_error', [$this, $name, $extension]);
            throw new Exception (
                'unable to rename file:'
                . $this->_getFullPath()
                . ' -> '
                . $name . '.' . $extension
            );
        }

        $this->_updateFileInfo();
        $this->replaceDataArrays();

        Loader::callEvent('rename_file_object_instance_after', $this);
        return $this;
    }

    /**
     * set file information at real existing file
     * 
     * @return Core_Disc_Model_File
     */
    protected function _updateFileInfo()
    {
        $info = new SplFileInfo($this->_getFullPath());
        $this->setAtTime($info->getATime());
        $this->setMtTime($info->getMTime());
        $this->setCtTime($info->getCTime());
        $this->setSize($info->getSize());
        $this->setPermissions($info->getPerms());

        return $this;
    }

    /**
     * return content size in bytes
     * 
     * @return integer
     */
    public function getSize()
    {
        $size = mb_strlen($this->getContent(), '8bit');
        $this->setSize($size);
        return $size;
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
