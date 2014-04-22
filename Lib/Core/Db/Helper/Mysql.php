<?php
/**
 * handling of MySQL database
 *
 * @category    BlueFramework
 * @package     Core
 * @subpackage  Db
 * @author      Michał Adamiak    <chajr@bluetree.pl>
 * @copyright   chajr/bluetree
 * @version     3.4.0
 *
 * Display <a href="http://sam.zoy.org/wtfpl/COPYING">Do What The Fuck You Want To Public License</a>
 * @license http://sam.zoy.org/wtfpl/COPYING Do What The Fuck You Want To Public License
 */
class Core_Db_Helper_Mysql extends Core_Db_Helper_Abstract
{
    const DEFAULT_CONNECTION_NAME = 'default_connection';

    /**
     * default constructor options
     * 
     * @var array
     */
    protected $_options = [
        'sql'           => '',
        'connection'    => NULL,
        'charset'       => NULL
    ];

    /**
     * set default connection and run given query
     * optionally we can give other connection and change charset
     *
     * @param string|array $options
     * @example new mysql_class('SELECT * FROM table')
     * @example new mysql_class(['sql'=>'SELECT * FROM table', 'connection'=>'name'])
     * @example new mysql_class(['sql'=>'SELECT * FROM table', 'connection'=>NULL, 'charset'=>'LATIN1'])
     */
    public function __construct($options)
    {
        if (is_string($options)) {
            $sql = $options;
        } else {
            $this->_options = array_merge($this->_options, (array)$options);
            $sql            = $this->_options['sql'];
        }

        if ($this->_options['connection']) {
            $this->_connection = $this->_options['connection'];
        } else {
            $this->_connection = self::DEFAULT_CONNECTION_NAME;
        }

        if ($this->_options['charset']) {
            $this->_setCharset($this->_options['charset']);
        }

        $this->_query($sql);

        if ($this->_options['charset']) {
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
        if (empty($sql)) {
            $this->err = 'Empty query';
            return;
        }

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