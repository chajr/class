<?php
/**
 * handling of MySQL database
 *
 * @package     Core
 * @subpackage  Db
 * @author      Michał Adamiak    <chajr@bluetree.pl>
 * @copyright   chajr/bluetree
 */
namespace Core\Db\Helper;
use Core\Db\Helper\Connection;
use Loader;
use mysqli_result;
class Mysql extends HelperAbstract
{
    const CONNECTION_MYSQLI = 'Core\Db\Helper\Connection\Mysql';
    const CONNECTION_PDO    = 'Core\Db\Helper\Connection\Pdo';

    /**
     * default constructor options
     * 
     * @var array
     */
    protected $_options = [
        'sql'           => '',
        'connection'    => NULL,
        'type'          => self::CONNECTION_MYSQLI,
        'charset'       => NULL
    ];

    /**
     * @var Connection\Mysql|Connection\Pdo
     */
    protected $_connectionObject;

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

        $this->_connectionObject = Loader::getObject($this->_options['type']);
        $lib = $this->_connectionObject;

        if ($this->_options['connection']) {
            $this->_connection = $this->_options['connection'];
        } else {
            $this->_connection = Connection\ConnectionInterface::DEFAULT_CONNECTION_NAME;
        }

        if ($this->_options['charset']) {
            $this->_setCharset($this->_options['charset']);
        }

        $this->_query($sql);

        if ($this->_options['charset']) {
            $this->_setCharset($lib::$defaultCharset);
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
    protected function _query($sql)
    {
        if (empty($sql)) {
            $this->err = 'Empty query';
            $this->logQuery($sql);
            return;
        }

        $lib = $this->_connectionObject;

        /** @var Connection\Mysql $connection */
        $connection = $lib::$connections[$this->_connection];
        $bool       = $connection->query($sql);

        if (!$bool) {
            $this->err = $connection->error;
            $this->logQuery($sql);
            return;
        }

        if ($connection->insert_id) {
            $this->id = $connection->insert_id;
        }

        if (!is_bool($bool) && !is_integer($bool)) {
            $this->rows = $bool->num_rows;
        }

        $this->_result = $bool;
        $this->logQuery($sql);
    }

    /**
     * change charset for queries
     *
     * @param string $charset
     */
    private function _setCharset($charset)
    {
        $lib = $this->_connectionObject;
        /** @var Connection\Mysql $connection */
        $connection = $lib::$connections[$this->_connection];
        $connection->query("SET NAMES '$charset'");
    }
}
