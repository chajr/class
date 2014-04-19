<?php
/**
 * base operations on files and directories
 *
 * @package     Core
 * @subpackage  Disc
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Disc_Helper_Common
{
    /**
     * restricted characters for file and directory names
     * @var string
     */
    const RESTRICTED_SYMBOLS = '#[:?*<>"|\\\]#';

    /**
     * remove file or directory with all content
     *
     * @param string $path
     * @return boolean information that operation was successfully, or NULL if path incorrect
     */
    static function delete($path)
    {
        Loader::tracer('delete given path content', debug_backtrace(), '6802cf');
        Loader::callEvent('delete_path_content_before', [&$path]);

        $bool = [];

        if(!Core_Incoming_Model_File::exist($path)){
            return NULL;
        }

        @chmod($path, 0777);

        if (is_dir($path)) {

            $list   = self::readDirectory($path, TRUE);
            $paths  = self::returnPaths($list, TRUE);

            if (isset($paths['file'])) {
                foreach ($paths['file'] as $val) {
                    $bool[] = unlink($val);
                }
            }

            if (isset($paths['dir'])) {
                foreach ($paths['dir'] as $val) {
                    $bool[] = rmdir($val);
                }
            }

            rmdir($path);
        } else {
            $bool[] = @unlink($path);
        }

        if (in_array(FALSE, $bool)) {
            Loader::callEvent('delete_path_content_error', [$bool, $path]);
            return FALSE;
        }

        Loader::callEvent('delete_path_content_after', $path);
        return $bool;
    }

    /**
     * copy file or directory to given source
     * if source directory not exists, create it
     *
     * @param string $path
     * @param string $target
     * @return boolean information that operation was successfully, or NULL if path incorrect
     */
    static function copy($path, $target)
    {
        Loader::tracer('copy given path content', debug_backtrace(), '6802cf');
        Loader::callEvent('copy_path_content_before', [&$path, &$target]);

        $bool = [];

        if (!Core_Incoming_Model_File::exist($path)) {
            return NULL;
        }

        if (is_dir($path)) {

            if (!Core_Incoming_Model_File::exist($target)) {
                $bool[] = mkdir($target);
            }

            $elements   = self::readDirectory($path);
            $paths      = self::returnPaths($elements);

            foreach ($paths['dir'] as $dir) {
                $bool[] = mkdir($dir);
            }

            foreach ($paths['file'] as $file) {
                $bool[] = copy($path . "/$file", $target . "/$file");
            }

        } else {

            if (!$target) {
                $filename   = explode('/', $path);
                $target     = end($filename);
            } else {

                $bool = self::mkdir($target);
                if ($bool) {
                    Loader::callEvent('copy_path_content_error', [$path, $target]);
                    return NULL;
                }
            }

            $bool[] = copy($path, $target);
        }

        if (in_array(FALSE, $bool)) {
            Loader::callEvent('copy_path_content_error', [$bool, $path, $target]);
            return FALSE;
        }

        Loader::callEvent('copy_path_content_after', [$path, $target]);
        return TRUE;
    }

    /**
     * create new directory in given location
     *
     * @param string $path
     * @return boolean
     * @static
     */
    static function mkdir($path)
    {
        Loader::tracer('create directory', debug_backtrace(), '6802cf');
        Loader::callEvent('create_directory_before', [&$path]);

        $bool = preg_match(self::RESTRICTED_SYMBOLS, $path);

        if (!$bool) {
            $bool = mkdir ($path);
            Loader::callEvent('create_directory_after', $path);
            return $bool;
        }

        Loader::callEvent('create_directory_error', $path);
        return FALSE;
    }

    /**
     * create empty file, and optionally put in them some data
     *
     * @param string $path
     * @param string $fileName
     * @param mixed $data
     * @return boolean information that operation was successfully, or NULL if path incorrect
     * @example mkfile('directory/inn', 'file.txt')
     * @example mkfile('directory/inn', 'file.txt', 'Lorem ipsum')
     */
    static function mkfile($path, $fileName, $data = NULL)
    {
        Loader::tracer('create file', debug_backtrace(), '6802cf');
        Loader::callEvent('create_file_before', [&$path, &$fileName, &$data]);

        if (!Core_Incoming_Model_File::exist($path)) {
            self::mkdir($path);
        }

        $bool = preg_match(self::RESTRICTED_SYMBOLS, $fileName);

        if (!$bool) {
            $f = @fopen("$path/$fileName", 'w');
            fclose($f);

            if ($data) {
                $bool = file_put_contents("$path/$fileName", $data);
                Loader::callEvent('create_file_after', [$path, $fileName]);
                return $bool;
            }
        }

        Loader::callEvent('create_file_error', [$path, $fileName]);
        return FALSE;
    }

    /**
     * change name of file/directory
     * also can be used to copy operation
     *
     * @param string $source original path or name
     * @param string $target new path or name
     * @return boolean information that operation was successfully, or NULL if path incorrect
     */
    static function rename($source, $target)
    {
        Loader::tracer('rename file or directory', debug_backtrace(), '6802cf');
        Loader::callEvent('rename_file_or_directory_before', [&$source, &$target]);

        if (!Core_Incoming_Model_File::exist($source)) {
            Loader::callEvent('rename_file_or_directory_error', [$source, 'source']);
            return NULL;
        }

        if (Core_Incoming_Model_File::exist($target)) {
            Loader::callEvent('rename_file_or_directory_error', [$target, 'target']);
            return FALSE;
        }

        $bool = preg_match(self::RESTRICTED_SYMBOLS, $target);

        if (!$bool) {
            $bool = rename($source, $target);
            Loader::callEvent('rename_file_or_directory_after', [$source, $target]);
            return $bool;
        }

        Loader::callEvent('rename_file_or_directory_error', [$source, $target]);
        return FALSE;
    }

    /**
     * move file or directory to given target
     *
     * @param string $source
     * @param string $target
     * @return bool
     */
    static function move($source, $target)
    {
        Loader::tracer('move file or directory', debug_backtrace(), '6802cf');
        Loader::callEvent('move_file_or_directory_before', [&$source, &$target]);
        $bool = self::copy($source, $target);

        if (!$bool) {
            Loader::callEvent('move_file_or_directory_error', [$source, $target]);
            return FALSE;
        }

        $bool = self::delete($source);
        Loader::callEvent('move_file_or_directory_after', [$source, $target, $bool]);

        return $bool;
    }

    /**
     * read directory content, (optionally all sub folders)
     *
     * @param string $path
     * @param boolean $whole
     * @return array|null
     * @example readDirectory('dir/some_dir')
     * @example readDirectory('dir/some_dir', TRUE)
     * @example readDirectory(); - read MAIN_PATH destination
     */
    static function readDirectory($path = NULL, $whole = FALSE)
    {
        Loader::tracer('read directory', debug_backtrace(), '6802cf');
        $list = [];

        if (!$path) {
            $path = MAIN_PATH;
        }

        if (!Core_Incoming_Model_File::exist($path)) {
            return NULL;
        }

        $iterator = new DirectoryIterator($path);

        /** @var DirectoryIterator $element */
        foreach ($iterator as $element) {
            if ($element->isDot()) {
                continue;
            }

            if ($whole && $element->isDir()) {
                $list[$element->getRealPath()] = self::readDirectory(
                    $element->getRealPath(),
                    TRUE
                );
            } else {
                $list[$element->getRealPath()] = $element->getFileInfo();
            }

        }

        return $list;
    }

    /**
     * transform array wit directory/files tree to list of paths grouped on files and directories
     *
     * @param array $array array to transform
     * @param boolean $reverse if TRUE revert array (required for deleting)
     * @internal param string $path base path for elements, if emty use paths from transformed structure
     * @return array array with path list for files and directories
     * @example returnPaths($array, '')
     * @example returnPaths($array, '', TRUE)
     * @example returnPaths($array, 'some_dir/dir', TRUE)
     */
    static function returnPaths(array $array, $reverse = FALSE)
    {
        if ($reverse) {
            $array = array_reverse($array);
        }

        $pathList = [];

        foreach ($array as $path => $fileInfo) {
            if (is_dir($path)) {

                $list = self::returnPaths($fileInfo);
                foreach ($list as $element => $value) {

                    if ($element === 'file') {
                        foreach ($value as $file) {
                            $pathList['file'][] = $file;
                        }
                    }

                    if ($element === 'dir') {
                        foreach ($value as $dir) {
                            $pathList['dir'][] = $dir;
                        }
                    }

                }
                $pathList['dir'][] = $path;

            } else {
                /** @var DirectoryIterator $fileInfo */
                $pathList['file'][] = $fileInfo->getRealPath();
            }
        }

        return $pathList;
    }
}
