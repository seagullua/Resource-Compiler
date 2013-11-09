<?php

require_once 'Config.php';
require_once 'Dir.php';
require_once 'File.php';
/**
 * Description of TexturePacker
 *
 * @author Andriy
 */
class TexturePacker
{
    public static function createSheet($texture_file, $textrute_real_name, $plist_file, $resources)
    {
        $tmp = Config::$_TMP_DIR;
        $assets = Dir::namedir($tmp, 'assets');
        
        if(Dir::exists($assets))
            Dir::cleanDir ($assets);
        else
            Dir::create ($assets);
        
        $texture_put = File::filename($tmp, $textrute_real_name);
        
        foreach($resources as $r)
        {
            $in_assets_name = File::filename($assets, $r->getName());
            File::copy($r->getLocation(), $in_assets_name);
        }
        $res = self::generateAndRunTps($texture_put, $plist_file, $assets);
        File::copy($texture_put, $texture_file);
        return $res;
    }
    
    private static function generateAndRunTps($texture, $plist, $assets)
    {
        $exe = Config::$_TEXTURE_PACKER;
        $command = "\"{$exe}\" --algorithm Basic --trim-mode None --format cocos2d --data \"{$plist}\" --sheet \"{$texture}\" \"{$assets}\"";
        
        
        exec($command, $output, $code);
        if($code != 0)
        {
            Log::error(implode("\n", $output), 'TexturePacker', $command);
        }
        return $code == 0;
    }
    
    
}

?>
