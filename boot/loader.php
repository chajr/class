<?php
/**
 * main class autoloader loader
 * 
 * @package     boot
 * @author      chajr <chajr@bluetree.pl>
 */
namespace boot;
use Composer;
class loader
{
    public function __construct()
    {
        //check that composer autoload exists
        //firs search classes in lib directory
        //next in bin directory (by composer autoloader)
        //at end in dev directory

        /** @var Composer\Autoload\ClassLoader $loader */
        $loader = require_once 'bin/autoload.php';
    }
}