<?php

require_once 'Log.php';
require_once 'File.php';
require_once 'Config.php';
class ImageMagick
{
    public static $_FONT_DIR = '';
    
//    public static function combineImages($background, $foreground, $output, $x_offset=0, $y_offset=0)
//    {
//        
//        $command = "composite -geometry +{$x_offset}+{$y_offset}  \"{$foreground}\" \"{$background}\"  \"{$output}\"";
//        return self::imageMagickCommand($command);
//    }
    
	public static function scaleImage($input, $output, $scale)
	{
		$command = "convert \"{$input}\" -resize ".($scale*100)."% \"{$output}\"";
		return self::imageMagickCommand($command);
	}
	
    public static function combineImages($background, $output, $layers)
    {
        $command = "convert \"{$background}\" ";
        foreach($layers as $l)
        {
            $command .= "\"{$l['image']}\" -geometry  +{$l['x_offset']}+{$l['y_offset']} -composite ";
        }
        $command .= "\"{$output}\"";
        return self::imageMagickCommand($command);
    }
    
    public static function renderText($output, $text, $width, $height, $color, $font)
    {
        $font_file = File::filename(self::$_FONT_DIR, $font);
        
        $text_file = File::filename(Config::$_TMP_DIR, 'text.utf8');
        file_put_contents($text_file, str_replace("\\n", "\n", $text));
        $command = "convert -background transparent -fill \"{$color}\" -font \"{$font_file}\" -size {$width}x{$height} -gravity center caption:@\"{$text_file}\" \"{$output}\"";
        
        $res = self::imageMagickCommand($command);
        unlink($text_file);
        return $res;
    }
    
    public static function imageMagickCommand($command)
    {
		//die( $command);
		$command = str_replace('/', '\\', $command);
        exec ($command, $output, $code);
        if($code != 0)
        {
            Log::error($output, 'ImageMagick', $command);
            die();
        }
        return $code == 0;
    }
}

?>
