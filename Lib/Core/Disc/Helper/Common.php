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
        $bool = [];

        if(!file_exists($path)){
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
            return FALSE;
        }

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
                    return NULL;
                }
            }

            $bool[] = copy($path, $target);
        }

        if (in_array(FALSE, $bool)) {
            return FALSE;
        }

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
        $bool = preg_match(self::RESTRICTED_SYMBOLS, $path);

        if (!$bool) {
            $bool = mkdir ($path);
            return $bool;
        }

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
        if (!Core_Incoming_Model_File::exist($path)) {
            return NULL;
        }

        $bool = preg_match(self::RESTRICTED_SYMBOLS, $fileName);

        if (!$bool) {
            $f = @fopen("$path/$fileName", 'w');
            fclose($f);

            if ($data) {
                $bool = file_put_contents("$path/$fileName", $data);
                return $bool;
            }
        }

        return FALSE;
    }

    /**
     * change name of file/directory
     * also can be used to copy operation
     *
     * @param string $path original path or name
     * @param string $target new path or name
     * @return boolean information that operation was successfully, or NULL if path incorrect
     */
    static function rename($path, $target)
    {
        if (!file_exists($path)) {
            return NULL;
        }

        if (Core_Incoming_Model_File::exist($target)) {
            return FALSE;
        }

        $bool = preg_match(self::RESTRICTED_SYMBOLS, $target);

        if (!$bool) {
            $bool = rename($path, $target);
            return $bool;
        }

        return FALSE;
    }

    /**
     * move file or directory to given target
     *
     * @param string $path
     * @param string $target
     * @return bool
     */
    static function move($path, $target)
    {
        $bool = self::copy($path, $target);

        if (!$bool) {
            return FALSE;
        }

        return self::delete($path);
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
