<?php

/**
 * Description of Dir
 *
 * @author Andriy
 */
class Dir
{

    public static function exists($dirname)
    {
        return file_exists($dirname);
    }

    public static function create($dirname)
    {
        if(!self::exists($dirname))
            mkdir($dirname, 0777, true);
    }

    public static function namedir($route, $dirname)
    {
        $last = substr($route, -1, 1);
        if ($last != '/' && $last != '\\')
            return $route . '/' . $dirname . '/';
        else
            return $route . $dirname . '/';
    }
    public static function realpath($value)
    {
        $res = realpath($value);
        return str_replace("\\",'/', $res);
    }
    public static function cleanDir($dirname)
    {
        $files = glob($dirname . '*'); // get all file names
        foreach ($files as $file)
        { // iterate files
            if (is_file($file) && strpos($file, "Thumbs.db") === false)
                unlink($file); // delete file
        }
    }

    public static function deleteDir($dirPath)
    {
        //echo $dirPath ."\n";
        if (!is_dir($dirPath))
        {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        $last = substr($dirPath, strlen($dirPath) - 1, 1);
        if ($last != '/' && $last != '\\')
        {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file)
        {
            if (is_dir($file))
            {
                self::deleteDir($file);
            }
            else
            {
                @unlink($file);
            }
        }
        @rmdir($dirPath);
    }

}

?>
