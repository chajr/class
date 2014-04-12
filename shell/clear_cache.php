<?php
require_once '../Lib/loader.php';
$path =  dirname(__FILE__);
new Loader($path, ['load_config' => FALSE, 'init_modules' => FALSE]);
Loader::getClass('Core_Shell_Helper_Cache');
