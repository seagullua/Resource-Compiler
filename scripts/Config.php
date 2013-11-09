<?php
require_once 'Dir.php';

/**
 * Description of Config
 *
 * @author Andriy
 */
class Config
{
    public static $_TMP_DIR = '';
    public static $_STORAGE_DIR = '';
    public static $_SCRIPT_PATH = '';
    
    public static $_TEXTURE_PACKER = '';
    
    public static function setBaseDir($dir)
    {
        self::$_SCRIPT_PATH = __DIR__;
        self::$_TMP_DIR = Dir::namedir($dir, 'tmp');
        self::$_STORAGE_DIR = Dir::namedir($dir, 'storage');
    }
}

?>
