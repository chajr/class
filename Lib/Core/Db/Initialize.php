<?php
/**
 * Initialize database connections
 *
 * @package     Core
 * @subpackage  Db
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Db_Initialize extends Core_Blue_Model_Initialize_Abstract
{
    /**
     * Initialize database connections
     */
    public function init()
    {
        $configuration      = Loader::getConfiguration()->getCoreDb();
        $mysql              = $configuration->getConnectMysql() === 'enabled';
        $initialize         = !$configuration->getInitialize() === 'disabled';
        $mysqlConnections   = Loader::getConfiguration()->getDatabaseMysql()->getData();

        if ($initialize) {
            return;
        }

        if ($mysql) {
            $this->_connectMysql($mysqlConnections);
        }
    }

    /**
     * initialize mysql connections
     * 
     * @param array $mysqlConnections
     */
    protected function _connectMysql(array $mysqlConnections)
    {
        Loader::callEvent('connect_mysql_before', [$this, &$mysqlConnections]);

        foreach ($mysqlConnections as $connection => $config) {
            try {
                $config['connection_name'] = $connection;

                /** @var Core_Db_Helper_Connection_Mysql $conn */
                $conn = Loader::getObject(
                    'Core_Db_Helper_Connection_Mysql',
                    $config,
                    'connection_mysql_' . $connection
                );

                if ($conn->err) {
                    Loader::callEvent('connect_mysql_exception', $this);
                    throw new Exception($conn->err);
                }
            } catch (Exception $e) {
                Loader::exceptions($e);
            }
        }

        Loader::callEvent('connect_mysql_after', $this);
    }
}
