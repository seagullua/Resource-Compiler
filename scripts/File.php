<?php

class File
{
    public static function exists($name)
    {
        return file_exists($name);
    }
    
    public static function filename($dir, $file)
    {
        $file_first = substr($file, 0, 1);
        if($file_first == '/' || $file_first == '\\')
        {
            $file = substr($file, 1, strlen($file)-1);
        }
        
        $last = substr($dir, -1, 1);
        if($last != '/' && $last != '\\')
            return $dir . '/' . $file;
        else
            return $dir.$file;
    }
    
    public static function copy($source, $destination)
    {
        copy($source, $destination);
    }
    public static function extension($file)
    {
        return pathinfo($file, PATHINFO_EXTENSION);
    }
    public static function dirname($file)
    {
        $path = pathinfo($file);
        return $path['dirname'];
    }
}
?>
