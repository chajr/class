<?php
/**
 * convert $_FILE array to blue object
 *
 * @package     Core
 * @subpackage  Incoming
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Incoming\Model;
use Exception;
use Loader;
use Core\Blue\Model;
class File extends ModelAbstract
{
    /**
     * array of uploaded files errors
     *
     * UPLOAD_ERR_OK
     * Value: 0; There is no error, the file uploaded with success.
     * UPLOAD_ERR_INI_SIZE
     * Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
     * UPLOAD_ERR_FORM_SIZE
     * Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
     * UPLOAD_ERR_PARTIAL
     * Value: 3; The uploaded file was only partially uploaded.
     * UPLOAD_ERR_NO_FILE
     * Value: 4; No file was uploaded.
     * UPLOAD_ERR_NO_TMP_DIR
     * Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.
     * UPLOAD_ERR_CANT_WRITE
     * Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.
     * UPLOAD_ERR_EXTENSION
     * Value: 8; File upload stopped by extension. Introduced in PHP 5.2.0.
     *
     * @var array
     */
    protected $_uploadErrors = [];

    /**
     * convert uploaded files info to object
     */
    public function __construct()
    {
        Loader::tracer('start files class', debug_backtrace(), '516E91');
        Loader::callEvent('initialize_file_object_before', $this);

        parent::__construct('file');
        $this->traveler('_checkFile');
        $this->replaceDataArrays();

        Loader::callEvent('initialize_file_object_after', $this);
    }

    /**
     * check that uploaded files has some errors and create object of file data
     * 
     * @param string $key
     * @param array $value
     * @return File
     * @throws Exception
     */
    protected function _checkFile($key, array $value)
    {
        static $uploadedFileSize    = 0;
        $config                     = Loader::getConfiguration()->getSecure();
        $fileMaxSize                = $config->getFileMaxSize();
        $filesMaxSize               = $config->getFilesMaxSize();

        if ($value['size'] > $fileMaxSize) {
            $msg = $value['name'] . 'size is ' . $value['size'] . ', and max is' . $fileMaxSize;
            throw new Exception($msg);
        }

        $uploadedFileSize += $value['size'];

        if ($uploadedFileSize > $filesMaxSize) {
            $msg = 'max files size is: ' . $filesMaxSize;
            throw new Exception($msg);
        }

        $path       = pathinfo($value['name']);
        $value      = array_merge($value, $path);
        $newData    = new Model\Object($value);
        
        $this->setData($key, $newData);

        if ($value['error'] !== UPLOAD_ERR_OK) {
            $this->_uploadErrors[$key]  = $value['error'];
            $this->_hasErrors           = TRUE;
        }

        return $this;
    }

    /**
     * return list of all files errors, or error for given file
     * 
     * @param string $key
     * @return array
     */
    public function returnErrors($key)
    {
        if ($key) {
            return $this->_uploadErrors[$key];
        }

        return $this->_uploadErrors;
    }

    /**
     * move uploaded files to correct place
     * can move single file, or group in given destination or destinations
     *
     * @param string|array $destination
     * @param string|boolean $name form name witch came file if NULL read form name from destinations array
     * @return File
     * 
     * @example move(array('path1', 'path2'), 'form1') - put file to 2 directories
     * @example move(array('form1' => 'path', 'form2' => 'path2'))
     * @example move('some/path', 'form2')
     * @example move('some/path') - move all uploaded files to given path
     */
    public function moveFile($destination, $name = NULL)
    {
        Loader::tracer('move file to given destination', debug_backtrace(), '516E91');
        Loader::callEvent('move_uploaded_file_before', [$this, $destination, $name]);

        if (is_array($destination)) {
            if ($name) {

                foreach ($destination as $path) {
                    $this->_createData($path, $this->getData($name)->getTmpName());
                }
            } else {

                foreach ($destination as $key => $path) {
                    $this->_createData($path, $this->getData($key)->getTmpName());
                }
            }
        } else {
            if ($name) {
                $this->_createData($destination, $this->getData($name)->getTmpName());
            } else {

                foreach ($this->getData() as $key => $val) {
                    if ($key === '_uploadFullSize' || $key === 'uploadErrors') {
                        continue;
                    }
                    $this->_createData($destination, $val->getTmpName());
                }
            }
        }

        Loader::callEvent('move_uploaded_file_after', [$this, $destination, $name]);
        return $this;
    }

    /**
     * check if directory exist and create it if not and put file to directory
     *
     * @param string $path
     * @param string $valueToPut
     * @return File
     */
    protected function _createData($path, $valueToPut)
    {
        Loader::tracer('check if exist and create directory', debug_backtrace(), '516E91');

        if (!self::exist($path)) {
            $bool = mkdir ($path);

            if (!$bool) {
                $this->_uploadErrors['create_directory'][] = $path;
                return $this;
            }
        }

        try {
            $this->_put($valueToPut, $path);
        } catch (Exception $e) {
            Loader::exceptions($e);
        }

        return $this;
    }

    /**
     * move file to given destination
     *
     * @param string $filename name of file in tmp directory
     * @param string $destination
     * @return File
     * @throws Exception
     */
    private function _put($filename, $destination)
    {
        Loader::tracer('move file to destination', debug_backtrace(), '516E91');

        if (self::exist($destination . '/' . $filename)) {
            $this->_uploadErrors['put_file'][] = $destination . '/' . $filename;
            return $this;
        }

        $bool = move_uploaded_file($filename, $destination);
        if (!$bool) {
            throw new Exception(
                $filename . ' => ' . $destination
            );
        }

        return $this;
    }

    /**
     * check that file exists
     *
     * @param string $path
     * @return boolean TRUE if exists, FALSE if not
     */
    static function exist($path)
    {
        if (file_exists($path)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * get data from file, or from all files in object
     *
     * @param string $name
     * @return mixed|array
     * @example read() - return array of files with their content
     * @example read('input_form_form')
     */
    public function readFile($name = NULL)
    {
        Loader::tracer('return data from file', debug_backtrace(), '516E91');
        Loader::callEvent('read_uploaded_file_before', [$this, $name]);

        $data = NULL;

        if ($name) {
            $data = $this->_singleFile($name);
        } else {
            $data = [];

            foreach ($this->getData() as $key => $val) {
                if($key === '_uploadFullSize' || $key === 'uploadErrors') {
                    continue;
                }

                $data[$key] = $this->_singleFile($key);
            }
        }

        Loader::callEvent('read_uploaded_file_after', [$this, $name, &$data]);
        return $data;
    }

    /**
     * return content of single file
     *
     * @param string $file name of input from form
     * @return mixed
     * @throws Exception
     */
    protected function _singleFile($file)
    {
        Loader::tracer('return content of single file', debug_backtrace(), '516E91');

        $name = CORE_TEMP . time() . '.tmp';
        $bool = move_uploaded_file($this->getData($file)->getTmpName(), $name);

        if (!$bool) {
            throw new Exception(
                $this->$file . ' => ' . $name
            );
        }

        $data = file_get_contents($name);
        unlink($name);

        return $data;
    }
}
