<?php
/**
 * interface for directory and file objects
 *
 * @package     Core
 * @subpackage  Disc
 * @author      chajr <chajr@bluetree.pl>
 */
interface Core_Disc_Model_Interface
{
    public function load();
    public function save();
    public function delete();
    public function move($destination);
    public function copy($destination);
    public function rename($newName);
    public function setPermissions($permissions);
}