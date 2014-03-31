<?php
/**
 * handling of MySQL database
 *
 * @category    BlueFramework
 * @package     Core
 * @subpackage  Db
 * @author      Michał Adamiak    <chajr@bluetree.pl>
 * @copyright   chajr/bluetree
 * @version     3.3.2
 *
 * Display <a href="http://sam.zoy.org/wtfpl/COPYING">Do What The Fuck You Want To Public License</a>
 * @license http://sam.zoy.org/wtfpl/COPYING Do What The Fuck You Want To Public License
 */
class Core_Db_Helper_Mysql
    extends Core_Db_Helper_Abstract
{
    /**
     * set default connection and run given query
     * optionally we can give other connection and change charset
     *
     * @param string $sql
     * @param string $connection optionally connection name (default - default)
     * @param string $charset
     * @example new mysql_class('SELECT * FROM table')
     * @example new mysql_class('SELECT * FROM table', 'connection')
     * @example new mysql_class('SELECT * FROM table', 0, 'LATIN1')
     */
    public function __construct($sql, $connection = 'default', $charset = NULL)
    {
        if (!$connection) {
            $this->_connection = 'default';
        } else {
            $this->_connection = $connection;
        }

        if ($charset) {
            $this->_setCharset($charset);
        }

        $this->_query($sql);

        if ($charset) {
            $this->_setCharset(Core_Db_Helper_Connection_Mysql::$defaultCharset);
        }
    }

    /**
     * return data converted to array
     * if $full is TRUE return all data, else as single row
     *
     * @param boolean $full
     * @return array
     */
    public function result($full = FALSE)
    {
        if ($this->rows) {

            if ($full) {
                $arr = array();

                while ($array = $this->_result->fetch_assoc()) {

                    if (!$array) {
                        return NULL;
                    }

                    $arr[] = $array;
                }

            } else {
                $arr = $this->_result->fetch_assoc();
            }

            return $arr;
        }

        return NULL;
    }

    /**
     * return full result from query
     * used $this->result(TRUE); method
     *
     * @return array
     */
    public function fullResult()
    {
        return $this->result(TRUE);
    }

    /**
     * return mysqli_result result object
     *
     * @return mysqli_result
     */
    public function returns()
    {
        return $this->_result;
    }

    /**
     * run query to database
     *
     * @param string $sql
     */
    private function _query($sql)
    {
        /** @var Core_Db_Helper_Connection_Mysql $connection */
        $connection = Core_Db_Helper_Connection_Mysql::$connections[$this->_connection];
        $bool       = $connection->query($sql);

        if (!$bool) {
            $this->err = $connection->error;
            return;
        }

        if ($connection->insert_id) {
            $this->id = $connection->insert_id;
        }

        if (!is_bool($bool) && !is_integer($bool)) {
            $this->rows = $bool->num_rows;
        }

        $this->_result = $bool;
    }

    /**
     * change charset for queries
     *
     * @param string $charset
     */
    private function _setCharset($charset)
    {
        /** @var Core_Db_Helper_Connection_Mysql $connection */
        $connection = Core_Db_Helper_Connection_Mysql::$connections[$this->_connection];
        $connection->query("SET NAMES '$charset'");
    }
}