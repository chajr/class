<?php
/**
 * establish connection with database and put reference to it into special array
 *
 * @package     Core
 * @subpackage  Db
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Db\Helper\Connection;
use PDO as Driver;
use Loader;
use Exception;
class Pdo extends Driver implements ConnectionInterface
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
    static $connections = [];

    /**
     * default charset
     * @var string
     */
    static $defaultCharset = self::DEFAULT_CHARSET;

    /**
     * @var array
     */
    protected $_options = [
        'host'              => '',
        'username'          => '',
        'pass'              => '',
        'db_name'           => '',
        'port'              => NULL,
        'connection_name'   => NULL,
        'charset'           => self::DEFAULT_CHARSET,
    ];

    /**
     * creates instance of pdo object and connect to database
     * name default is used for default connection to database !!!!
     *
     * @param array $options (host, username, pass, db_name, port, connection_name, charset)
     */
    public function __construct(array $options)
    {
        $this->_options         = array_merge($this->_options, $options);
        self::$defaultCharset   = $this->_options['charset'];

        if (empty($this->_options['host'])) {
            return;
        }

        if (isset($options) && !empty($options)) {

            $dsn = $options['type']
                . ':dbname='
                . $options['db_name']
                . ';host='
                . $options['host']
                . ';port='
                . $options['port'];

            try {
                parent::__construct($dsn, $options['username'], $options['pass']);
                $this->logConnection($options);
            } catch (Exception $e) {
                $this->err = $e->getMessage();
                $this->logConnection($options, $e->getMessage());
                return;
            }

            $errorInfo = $this->errorInfo();
            if ($errorInfo[0] || $errorInfo[1] || $errorInfo[2]) {
                $this->err = implode(':', $errorInfo);
                $this->logConnection($options, $this->err);
                return;
            }

            $this->query("SET NAMES '{$this->_options['charset']}'");
        }

        $isSetConnection = !isset($this->_options['connection_name']);
        $isConnection    = !$this->_options['connection_name'];

        if ($isSetConnection || $isConnection) {
            $this->_options['connection_name'] = self::DEFAULT_CONNECTION_NAME;
        }

        self::$connections[$options['connection_name']] = $this;
    }

    /**
     * save log information about connection
     *
     * @param array $options
     * @param mixed $error
     */
    public function logConnection(array $options, $error = NULL)
    {
        if (Loader::getConfiguration()->getCoreDb()->getLogConnections() !== 'enabled') {
            return;
        }

        $options = implode(', ', $options);
        $options .= "\n" . __CLASS__;

        if ($error) {
            $options .= "\n[ERROR] " . $error;
        }

        Loader::log('database_connections', $options, 'connection');
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
