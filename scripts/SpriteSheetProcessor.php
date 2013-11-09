<?php

require_once 'Functions.php';
/**
 * Description of SpriteSheetProcessor
 *
 * @author Andriy
 */
class SpriteData
{
    private $_res;
    
    public function __construct(Resource $texture, Resource $plist)
    {
        $this->_res = array($texture, $plist);
    }
    
    public function getResources()
    {
        return $this->_res;
    }
}

class SpriteSheetProcessor
{

    private $_info;
    private $_output_folder;
    private $_lang;
    private $_working_dir;
    private $_xml_file;
	private $_auto_translate;
	private $_scale;

    const MODULE = 'spritesheet';

    public static function process($xml_file, $lang, $auto_translate, $scale)
    {
        $pr = new SpriteSheetProcessor($xml_file, $lang, $auto_translate, $scale);
        return $pr->render();
    }
    
    private function __construct($xml_file, $lang, $auto_translate, $scale)
    {
        $this->_auto_translate = $auto_translate;
        $this->_scale = $scale;
        $this->_output_folder = Config::$_STORAGE_DIR;
        $info = xmlRead($xml_file);
        $this->_working_dir = File::dirname($xml_file);
        $this->_xml_file = $xml_file;

        $main_key = key($info);
        if ($main_key != 'spritesheet')
        {
            Log::error("Wrong file", self::MODULE, $xml_file);
        }


        $this->_info = $info['spritesheet'];
        $this->_lang = $lang;
    }

    private function render()
    {
        $resources = array();
        
        $images = array();
        if(!is_array($this->_info['image']))
            $images = array($this->_info['image']);
        else
            $images = $this->_info['image'];
        
        foreach($images as $i)
        {
            $xml_file = File::filename($this->_working_dir, $i);
            $res = ImageProcessor::process($xml_file, $this->_lang, $this->_auto_translate, $this->_scale);
            if(!is_null($res))
            {
                $res_arr = $res->getResources();
                $resources[] = $res_arr[0];
            }
        }
        
        $hash_file = array($this->_xml_file);
        foreach ($resources as $r)
        {
            $hash_file[] = $r->getLocation();
        }
        
        $texture_name = $this->getOutputFileName($this->_info['texture']);
        $plist_name = $this->getOutputFileName($this->_info['sheet']);
        
        $texture_storage_name = $this->getTmpFileName($hash_file, $texture_name);
        $plist_storage_file = $this->getTmpFileName($hash_file, $plist_name);
        
        $texture_file = File::filename(Config::$_STORAGE_DIR, $texture_storage_name);
        
        $plist_file = File::filename(Config::$_STORAGE_DIR, $plist_storage_file);
        
        if(!File::exists($texture_file) || !File::exists($plist_file))
        {
            TexturePacker::createSheet($texture_file, $texture_name, $plist_file, $resources);
        }
        
        $texture = new Resource($texture_name, $texture_file);
        $plist = new Resource($plist_name, $plist_file);
        return new SpriteData($texture, $plist);
    }

    private function error($text)
    {
        Log::error($text, self::MODULE, $this->_xml_file);
        return 0;
    }

    
    
    
    private function getTmpFileName($hash_files, $name)
    {
        $text = '';
        foreach($hash_files as $f)
        {
            $text .= filemtime($f).'-';
        }
		if($this->_scale != 1)
			$name .= $this->_scale;
        return md5($text).'.'.$this->_lang.'.'.$name;
    }
    private function getOutputFileName($base)
    {
        return str_replace('{lang}', $this->_lang, $base);
    }

}

?>
