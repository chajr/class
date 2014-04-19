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
    /**
     * base configuration for directory
     *
     * @var array
     */
    protected $_directoryData = [
        'main_path'             => '',
        'child_files'           => [],
        'child_directories'     => [],
        'size'                  => 0,
        'file_count'            => 0,
        'directory_count'       => 0,
        'to_delete'             => [],
        'permissions'           => 0755,
        'at_time'               => NULL,
        'ct_time'               => NULL,
        'mt_time'               => NULL,
    ];

    /**
     * create or read given directory
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        Loader::tracer('create directory object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('directory_object_instance_before', [&$data]);

        $data = array_merge($this->_directoryData, $data);
        parent::__construct($data);

        Loader::callEvent('directory_object_instance_after', [&$data]);
    }

    /**
     * load directory structure into object
     * 
     * @return Core_Disc_Model_Directory
     * @throws Exception
     */
    public function load()
    {
        Loader::tracer('load directory into object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('load_directory_object_instance_before', $this);

        if (!Core_Incoming_Model_File::exist($this->getMainPath())) {
            Loader::callEvent('load_directory_object_instance_error', $this);
            throw new Exception ('directory not exists: ' . $this->getMainPath());
        }

        $iterator        = new DirectoryIterator($this->getMainPath());
        $files           = 0;
        $directories     = 0;
        $totalSize       = 0;

        /** @var DirectoryIterator $element */
        foreach ($iterator as $element) {
            if ($element->isDot()) {
                continue;
            }

            if ($element->isDir()) {
                $this->_createDirectoryInstance($element, $directories, $files, $totalSize);
            } else {
                $this->_createFileInstance($element, $totalSize, $files);
            }
        }

        $this->setFileCount($this->getFileCount() + $files);
        $this->setDirectoryCount($directories);
        $this->setSize($totalSize);

        Loader::callEvent('load_directory_object_instance_after', $this);
        return $this;
    }

    /**
     * create file object instance
     * 
     * @param DirectoryIterator $element
     * @param int $totalSize
     * @param int $files
     * @return Core_Disc_Model_Directory
     */
    protected function _createFileInstance(DirectoryIterator $element, &$totalSize, &$files)
    {
        $name = str_replace(
            '.' . $element->getExtension(), '', $element->getBasename()
        );
        $fileList = $this->getChildFiles();

        /** @var Core_Disc_Model_File $newFile */
        $newFile = Loader::getClass('Core_Disc_Model_File', [
            'main_path'             => $this->getMainPath(),
            'size'                  => $element->getSize(),
            'permissions'           => $element->getPerms(),
            'at_time'               => $element->getATime(),
            'ct_time'               => $element->getCTime(),
            'mt_time'               => $element->getMTime(),
            'name'                  => $name,
            'extension'             => $element->getExtension(),
        ]);

        $newFile->load();
        $totalSize  += $element->getSize();
        $fileList[] = $newFile;
        $this->setChildFiles($fileList);
        $files++;

        return $this;
    }

    /**
     * create directory object instance
     * 
     * @param DirectoryIterator $element
     * @param int $directories
     * @param int $files
     * @param int $totalSize
     * @return Core_Disc_Model_Directory
     */
    protected function _createDirectoryInstance(DirectoryIterator $element, &$directories, &$files, &$totalSize)
    {
        $directoryList = $this->getChildDirectories();

        /** @var Core_Disc_Model_Directory $newDirectory */
        $newDirectory = Loader::getClass('Core_Disc_Model_Directory', [
            'main_path'             => $element->getRealPath(),
        ]);

        $newDirectory->load();
        $directoryList[] = $newDirectory;
        $this->setChildDirectories($directoryList);

        $directories    += $newDirectory->getDirectoryCount() +1;
        $files          += $newDirectory->getFileCount();
        $totalSize      += $newDirectory->getSize();

        return $this;
    }

    /**
     * remove directory, or object data if directory not exists
     * 
     * @return Core_Disc_Model_Directory
     * @throws Exception
     */
    public function delete()
    {
        Loader::tracer('delete directory object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('delete_directory_object_instance_before', $this);

        if (Core_Incoming_Model_File::exist($this->getMainPath())) {
            $bool = Core_Disc_Helper_Common::delete($this->getMainPath());

            if (!$bool) {
                Loader::callEvent('delete_directory_object_instance_error', $this);
                throw new Exception ('unable to remove directory: ' . $this->getMainPath());
            }
        }

        $this->unsetData();
        Loader::callEvent('delete_directory_object_instance_after', $this);
        return $this;
    }

    /**
     * save all files and directories from object
     * 
     * @return Core_Disc_Model_Directory
     * @throws Exception
     */
    public function save()
    {
        Loader::tracer('save directory object instance', debug_backtrace(), '6802cf');
        Loader::callEvent('save_directory_object_instance_before', $this);

        if (empty($this->_DATA)) {
            return $this;
        }

        if ($this->getToDelete()) {
            $this->_errorsList[] = 'directory must be removed, cannot be saved: ' . $this->getMainPath();
            Loader::callEvent('save_directory_object_instance_error', $this);
            return $this;
        }

        $files           = 0;
        $directories     = 0;
        $totalSize       = 0;

        $this->_saveMe();
        $this->_saveDirectories($directories, $files, $totalSize);
        $this->_saveFiles($files, $totalSize);

        $this->setFileCount($files);
        $this->setDirectoryCount($directories);
        $this->setSize($totalSize);

        if ($this->hasErrors()) {
            Loader::log('exception', $this->getObjectError(), 'directory io operation');

            $message = '';
            foreach ($this->getObjectError() as $error) {
                $message .= $error['message'] . ',';
            }
            throw new Exception (rtrim($message, ','));
        }

        Loader::callEvent('save_directory_object_instance_after', $this);
        return $this;
    }

    /**
     * create main directory if not exists
     * 
     * @return Core_Disc_Model_Directory
     * @throws Exception
     */
    protected function _saveMe()
    {
        if (!Core_Incoming_Model_File::exist($this->getMainPath())) {
            $bool = Core_Disc_Helper_Common::mkdir($this->getMainPath());

            if (!$bool) {
                throw new Exception('unable to save main directory: ' . $this->getMainPath());
            }
        }

        return $this;
    }

    /**
     * save directories from list
     * 
     * @param int $directories
     * @param int $files
     * @param int $totalSize
     * @return Core_Disc_Model_Directory
     */
    protected function _saveDirectories(&$directories, &$files, &$totalSize)
    {
        /** @var Core_Disc_Model_Directory $child */
        foreach ($this->getChildDirectories() as $child) {
            try {
                
                //replace mainpath, usunie jesli juz istnieje
                //str_replace();
                
                $child->setMainPath($this->getMainPath() . $child->getMainPath());
                $child->save();
                $directories++;
                $directories    += $child->getDirectoryCount();
                $files          += $child->getFileCount();
                $totalSize      += $child->getSize();
            } catch (Exception $e) {
                $this->_errorsList[$e->getCode()] = [
                    'message'   => $e->getMessage(),
                    'line'      => $e->getLine(),
                    'file'      => $e->getFile(),
                    'trace'     => $e->getTraceAsString(),
                ];
            }
        }

        return $this;
    }

    /**
     * save files from list
     * 
     * @param int $files
     * @param int $totalSize
     * @return Core_Disc_Model_Directory
     */
    protected function _saveFiles(&$files, &$totalSize)
    {
        /** @var Core_Disc_Model_File $child */
        foreach ($this->getChildFiles() as $child) {
            try {
                $child->setMainPath($this->getMainPath());
                $child->save();
                $files++;
                $totalSize += $child->getSize();
            } catch (Exception $e) {
                $this->_errorsList[$e->getCode()] = [
                    'message'   => $e->getMessage(),
                    'line'      => $e->getLine(),
                    'file'      => $e->getFile(),
                    'trace'     => $e->getTraceAsString(),
                ];
            }
        }

        return $this;
    }

    /**
     * create child in object
     * as parameter give Disc object or configuration array
     * if in array name will be set up, will create file
     * 
     * @param Core_Disc_Model_File|Core_Disc_Model_Directory|array $child
     * @return Core_Disc_Model_Directory
     */
    public function addChild($child)
    {
        if (is_array($child)) {
            if (isset($child['name'])) {
                $child = Loader::getClass('Core_Disc_Model_File', $child);
            } else {
                $child = Loader::getClass('Core_Disc_Model_Directory', $child);
            }
        }

        $this->addChildDirectory($child);
        $this->addChildFile($child);

        return $this;
    }

    /**
     * create child file in object
     * as parameter give Disc object or configuration array
     * if in array name will be set up, will create file
     * 
     * @param Core_Disc_Model_File|array $child
     * @return Core_Disc_Model_Directory
     */
    public function addChildFile($child)
    {
        if (is_array($child)) {
            $child = Loader::getClass('Core_Disc_Model_File', $child);
        }

        if (!$child instanceof Core_Disc_Model_File) {
            return $this;
        }

        $child->setMainPath($this->getMainPath());
        $children   = $this->getChildFiles();
        $children[] = $child;
        $this->setChildFiles($children);
        $this->setFileCount($this->getFileCount() +1);
        $this->setSize($this->getSize() + $child->getSize());

        return $this;
    }

    /**
     * create child directory in object
     * as parameter give Disc object or configuration array
     * if in array name will be set up, will create file
     *
     * @param Core_Disc_Model_Directory|array $child
     * @return Core_Disc_Model_Directory
     */
    public function addChildDirectory($child)
    {
        if (is_array($child)) {
            $child = Loader::getClass('Core_Disc_Model_Directory', $child);
        }

        if (!$child instanceof Core_Disc_Model_Directory) {
            return $this;
        }

        $child->setMainPath($this->getMainPath() . $child->getMainPath());
        $children   = $this->getChildDirectories();
        $children[] = $child;
        $this->setChildDirectories($children);
        $this->setDirectoryCount($this->getDirectoryCount() + $child->getDirectoryCount() +1);
        $this->setFileCount($this->getFileCount() + $child->getFileCount());
        $this->setSize($this->getSize() + $child->getSize());

        return $this;
    }

    /**
     *
     */
    public function move($destination)
    {
        //wykonac move fizyczne dla dira
        //rekursywnie zastapic wszystkim elementom mainPath
        //po wszystkim wywolanie remove
    }

    /**
     *
     */
    public function rename($newName)
    {
        //zmienic namepath wszystkim dzieciom (i tu jest rebus :/)
        //albo wywolac rename dla kazdego dira?
    }

    public function copy($destination)
    {
        
    }
}
