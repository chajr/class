<?php
/**
 * interfaces for all database connectors
 *
 * @package     Core
 * @subpackage  Db
 * @author      chajr <chajr@bluetree.pl>
 */
interface Core_Db_Helper_Connection_Interface
{
    const DEFAULT_CHARSET           = 'UTF8';
    const DEFAULT_CONNECTION_NAME   = 'default_connection';

    public function __construct(array $options);
    public function __destruct();
    public function logConnection(array $options, $error = NULL);
    static function destroyConnection($connectionList = NULL);
}