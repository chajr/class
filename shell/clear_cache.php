<?php
require_once '../Lib/loader.php';

$path               =  dirname(__FILE__);
Loader::$skipEvents = TRUE;

new Loader($path, ['load_config' => FALSE, 'init_modules' => FALSE]);
Loader::getClass('Core\Shell\Helper\Cache');
