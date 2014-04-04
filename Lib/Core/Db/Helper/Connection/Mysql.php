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

    protected $_options = [
        'host'              => '',
        'username'          => '',
        'pass'              => '',
        'db_name'           => '',
        'port'              => NULL,
        'connection_name'   => NULL,
        'charset'           => 'UTF8',
    ];

    /**
     * creates instance of mysqli object and connect to database
     * name default is used for default connection to database !!!!
     *
     * @param array $options (host, username, pass, db_name, port, connection_name, charset)
     */
    public function __construct(array $options)
    {
        $this->_options         = array_merge($this->_options, $options);
        self::$defaultCharset   = $this->_options['charset'];

        parent::__construct(
            $this->_options['host'],
            $this->_options['username'],
            $this->_options['pass'],
            $this->_options['db_name'],
            $this->_options['port']
        );

        if (mysqli_connect_error()) {
            $this->err = mysqli_connect_error();
            return;
        }

            $this->query("SET NAMES '{$this->_options['charset']}'");

        $isSetConnection = !isset($this->_options['connection_name']);
        $isConnection    = !$this->_options['connection_name'];

        if ($isSetConnection || $isConnection) {
            $this->_options['connection_name'] = Core_Db_Helper_Mysql::DEFAULT_CONNECTION_NAME;
        }

        self::$connections[$this->_options['connection_name']] = $this;
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