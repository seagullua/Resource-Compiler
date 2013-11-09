<?php

class Log
{
    public static function error($message, $module="core", $task="")
    {
        self::text("ERROR", $message, $module, $task);
        
        exit(1000);
    }
    
    public static function warning($message, $module="core", $task="")
    {
        self::text("WARNING", $message, $module, $task);
    }
    
    private static function text($level, $message, $module="core", $task="")
    {
        file_put_contents('php://stderr', "$level: [$module] $message ($task)\n");
        flush();
    }
}
?>
