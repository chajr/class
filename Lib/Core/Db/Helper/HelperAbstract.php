<?php
/**
 * main abstract class to handle connection to database
 *
 * @package     Core
 * @subpackage  Db
 * @author      Michał Adamiak    <chajr@bluetree.pl>
 * @copyright   chajr/bluetree
 */
namespace Core\Db\Helper;
use Loader;
use mysqli_result;
abstract class HelperAbstract
{
    /**
     * information about error
     * @var string
     */
    public $err = NULL;

    /**
     * id of last INSERT
     * @var integer
     */
    public $id = NULL;

    /**
     * number of returned rows
     * @var integer
     */
    public $rows = NULL;

    /**
     * name of selected connection
     * @var string
     */
    protected $_connection;

    /**
     * contains object with returned from database data
     * @var mysqli_result
     */
    protected $_result;

    /**
     * code content to queries(NUL (ASCII 0), \n, \r, \, ', ", and Control-Z)
     *
     * @param string $content
     * @return string
     */
    static function code($content)
    {
        return mysql_real_escape_string($content);
    }

    /**
     * add escape sequence to some chars (& ' " < >)
     *
     * @param string $content
     * @return string
     */
    static function entities($content)
    {
        return @htmlspecialchars($content);
    }

    /**
     * removes escape sequences from string (& ' " < >)
     *
     * @param string $content
     * @return string
     */
    static function decode($content)
    {
        return @stripcslashes($content);
    }

    /**
     * write complete query to log file
     * 
     * @param string $query
     */
    public function logQuery($query)
    {
        if (Loader::getConfiguration()->getCoreDb()->getLogQueries() !== 'enabled') {
            return;
        }

        $errorIsArray   = is_array($this->err);
        $noError        = $errorIsArray && $this->err[0] === '00000';

        if ($this->err && !$noError) {
            if ($errorIsArray) {
                $error = implode(', ', $this->err);
            } else {
                $error = $this->err;
            }
            $query .= "\n[ERROR] " . $error;
        }

        Loader::log('database_queries', $query, 'query');
    }

    /**
     * set default connection and run given query
     *
     * @param string $sql
     */
    abstract function __construct($sql);

    /**
     * return data converted to array
     */
    abstract function result();

    /**
     * return mysqli_result result object
     */
    abstract function returns();
}
