<?php
/**
 * Initialize database connections
 *
 * @package     Core
 * @subpackage  Db
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Db;
use Core\Blue\Model\Initialize\InitAbstract;
use Core\Db\Helper\Connection;
use Exception;
use Loader;
class Initialize extends InitAbstract
{
    /**
     * Initialize database connections
     */
    public function init()
    {
        $configuration      = Loader::getConfiguration()->getCoreDb();

        $mysql              = $configuration->getConnectMysql() === 'enabled';
        $pdo                = $configuration->getConnectPdo()   === 'enabled';
        $initialize         = !$configuration->getInitialize()  === 'disabled';

        $mysqlConnections   = Loader::getConfiguration()->getDatabaseMysql()->getData();
        $pdoConnections     = Loader::getConfiguration()->getDatabasePdo()->getData();

        if ($initialize) {
            return;
        }

        if ($pdo) {
            $this->_connect($pdoConnections, 'pdo');
        }

        if ($mysql) {
            $this->_connect($mysqlConnections, 'mysql');
        }
    }

    /**
     * initialize correct connection type connections
     *
     * @param array $connections
     * @param string $type
     */
    protected function _connect($connections, $type)
    {
        Loader::callEvent("connect_{$type}_before", [$this, &$connections]);

        foreach ($connections as $connection => $config) {
            try {
                $config['connection_name'] = $connection;

                /** @var Connection\Mysql|Connection\Pdo $conn */
                $conn = Loader::getObject(
                    'Core\Db\Helper\Connection\\' . ucfirst($type),
                    $config,
                    "connect_{$type}_" . $connection
                );

                if ($conn->err) {
                    Loader::callEvent("connect_{$type}_exception", $this);
                    throw new Exception($conn->err);
                }
            } catch (Exception $e) {
                Loader::exceptions($e);
            }
        }

        Loader::callEvent("connect_{$type}_after", $this);
    }

    /**
     * handle lunch initialize in error mode
     */
    public function errorMode(){}
}
