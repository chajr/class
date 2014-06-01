<?php
/**
 * handling of MySQL database
 *
 * @category    BlueFramework
 * @package     Core
 * @subpackage  Db
 * @author      Michał Adamiak    <chajr@bluetree.pl>
 * @copyright   chajr/bluetree
 */
class Core_Db_Helper_Pdo_Mysql extends Core_Db_Helper_Mysql
{
    /**
     * default constructor options
     * 
     * @var array
     */
    protected $_options = [
        'sql'           => '',
        'connection'    => NULL,
        'type'          => self::CONNECTION_PDO,
        'charset'       => NULL
    ];

    /**
     * @var PDOStatement
     */
    protected $_result;

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

                while ($array = $this->_result->fetchAll()) {

                    if (!$array) {
                        return NULL;
                    }

                    $arr[] = $array;
                }

            } else {
                $arr = $this->_result->fetchAll();
            }

            return $arr;
        }

        return NULL;
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

        /** @var Core_Db_Helper_Connection_Pdo $connection */
        $connection = $lib::$connections[$this->_connection];
        $bool       = $connection->query($sql);
        $error      = $bool->errorInfo();

        if ($error[0] !== '00000') {
            $this->err = $bool->errorInfo();
            $this->logQuery($sql);
            return;
        }

        if ($connection->lastInsertId()) {
            $this->id = $connection->lastInsertId();
        }

        if (!is_bool($bool) && !is_integer($bool)) {
            $this->rows = $bool->rowCount();
        }

        $this->_result = $bool;
        $this->logQuery($sql);
    }
}
