<?php
/**
 * establish connection with database and put reference to it to special array
 *
 * @author chajr <chajr@bluetree.pl>
 * @category    BlueFramework
 * @package     Core
 * @subpackage  Db
 * @author      Michał Adamiak    <chajr@bluetree.pl>
 * @copyright   chajr/bluetree
 * @version     1.3.0
 *
 * Display <a href="http://sam.zoy.org/wtfpl/COPYING">Do What The Fuck You Want To Public License</a>
 * @license http://sam.zoy.org/wtfpl/COPYING Do What The Fuck You Want To Public License
 */
class Core_Db_Helper_Connection_Mysql extends mysqli
{
    /**
     * information about connection error
     * @var string
     */
    public $err;

    /**
     * contains connection array
     * @var array
     */
    static $connections = array();

    /**
     * default charset
     * @var string
     */
    static $defaultCharset = 'UTF8';

    /**
     * creates instance of mysqli object and connect to database
     * name default is used for default connection to database !!!!
     *
     * @param array $config (host, username, pass, dbName, port, connectionName)
     * @param string $charset (default UTF8)
     * @example new mysql_connection_class(array('localhost', 'user', 'pass', 'db', '3306', 'connection'))
     * @example new mysql_connection_class(array('localhost', 'user', 'pass', 'db'))
     * @example new mysql_connection_class(array('localhost', 'user', 'pass', 'db', '3306'))
     * @example new mysql_connection_class(array('localhost', 'user', 'pass', 'db'), 'LATIN1')
     */
    public function __construct($config, $charset = 'UTF8')
    {
        self::$defaultCharset = $charset;

        if (isset($config) && !empty($config)) {

            parent::__construct(
                $config['host'],
                $config['username'],
                $config['pass'],
                $config['db_name'],
                $config['port']
            );

            if (mysqli_connect_error()) {
                $this->err = mysqli_connect_error();
                return;
            }

            $this->query("SET NAMES '$charset'");
        }

        if (!isset($config['connection_name']) || !$config['connection_name']) {
            $config['connection_name'] = 'default';
        }

        self::$connections[$config['connection_name']] = $this;
    }

    /**
     * destroy all connections
     */
    public function __destruct()
    {
        self::$connections = array();
    }

    /**
     * destroy all, or given connection
     *
     * @param array|string $connectionList array of connections or single connection
     * @example destruct()
     * @example destruct('default')
     * @example destruct(array('connection1', 'connection2'))
     */
    static function destroyConnection($connectionList = NULL)
    {
        if ($connectionList) {

            if (is_array($connectionList)) {

                foreach ($connectionList as $connection) {
                    unset(self::$connections[$connection]);
                }

            } else {
                unset(self::$connections[$connectionList]);
            }

        } else {
            self::$connections = array();
        }
    }
}