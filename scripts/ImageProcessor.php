<?php

require_once 'Functions.php';
require_once 'Translation.php';
class ImageData
{

    private $_res;

    public function __construct($real_name, $storage_file)
    {
        $this->_res = new Resource($real_name, $storage_file);
    }

    public function getResources()
    {
        return array($this->_res);
    }

}

class ImageProcessor
{

    private $_info;
    private $_output_folder;
    private $_lang;
    private $_working_dir;
    private $_xml_file;
	private $_layer = 0;
	private $_scale = 1;

    const MODULE = 'image';

    public static function process($xml_file, $lang, $auto_translate, $scale)
    {
        
        
        $pr = new ImageProcessor($xml_file, $lang, $auto_translate, $scale);
        return $pr->renderImage();
    }

    private function __construct($xml_file, $lang, $auto_translate, $scale)
    {
		$this->_auto_translate = $auto_translate;
        $this->_output_folder = Config::$_STORAGE_DIR;
        
        $this->_working_dir = File::dirname($xml_file);
        $this->_xml_file = $xml_file;

        $this->_lang = $lang;
		$this->_scale = $scale;
        
    }

    private function renderImage()
    {
		if($this->_scale != 1)
		{
			$orig = self::process($this->_xml_file, $this->_lang, $this->_auto_translate, 1);
			$data = $orig->getResources();
			$data = $data[0];
			
			$name = $data->getName();
			$location = $data->getLocation();
			$ext = File::extension($name);
			$new_name = md5($location.$this->_scale).".".$ext;
			$new_dir = File::filename($this->_output_folder, $new_name);
			
			if(!File::exists($new_dir))
				ImageMagick::scaleImage($data->getLocation(), $new_dir, $this->_scale);
			return new ImageData($name, $new_dir);
		}
		
        $ext = substr($this->_xml_file, -3, 3);
        if($ext != 'xml')
        {
            $file_name = basename($this->_xml_file);
            if(!File::exists($this->_xml_file))
            {
                $this->error ("File not found {$this->_xml_file}");
                return null;
            }
            return new ImageData($file_name, $this->_xml_file);
        }
        
        $info = xmlRead($this->_xml_file);
        $main_key = key($info);
        if ($main_key != 'image')
        {
            Log::error("Wrong file", self::MODULE, $this->_xml_file);
        }


        $this->_info = $info['image'];
        
        
        
        if(isset($this->_info['file']))
        {
            return new ImageData($this->_info['name'], File::filename($this->_working_dir, $this->_info['file']));
        }
        return $this->processLabel();
    }

    private function error($text)
    {
        Log::error($text, self::MODULE, $this->_xml_file);
        return 0;
    }

    private function processLayer($layer_info)
    {
        $lang = $this->_lang;

        $foreground_info = array();
        
        if (is_array($layer_info[$lang]))
            $foreground_info = $layer_info[$lang];
		else if($this->_auto_translate && is_array($layer_info['uu']))
			$foreground_info = $layer_info['uu'];
        else
            $foreground_info = $layer_info;

        $foreground_img = '';

        $res_array = array();
        $res_array['x_offset'] = 0;
        $res_array['y_offset'] = 0;
        $res_array['image'] = '';


        if (isset($foreground_info['image']))
        {

            $res_array['x_offset'] = $foreground_info['image']['x'] + 0;
            $res_array['y_offset'] = $foreground_info['image']['y'] + 0;
            $res_array['image'] = File::filename($this->_working_dir, $foreground_info['image']['file']);
        }
        else if (isset($foreground_info['text']))
        {
            $res_array['image'] = $this->renderText($foreground_info);

            $res_array['x_offset'] = $foreground_info['text']['x'] + 0;
            $res_array['y_offset'] = $foreground_info['text']['y'] + 0;
        }
        else
        {
            echo "Lang: {$lang}"."\n";
            var_dump($layer_info);
            var_dump($lang);
            $this->error("Wrong layer");
        }

        if (!File::exists($res_array['image']))
            return $this->error("Foreground not found {$foreground_img}");
        return $res_array;
    }

    private function processLabel()
    {
        $hash_files = array($this->_xml_file);

        $background = File::filename($this->_working_dir, $this->_info['background']);


        if (!File::exists($background))
            return $this->error("Background not found {$background}");

        $hash_files[] = $background;

        $lang = $this->_lang;

        $layer_images = array();

        $layers = array();
        if (isset($this->_info['layer'][0]))
            $layers = $this->_info['layer'];
        else
            $layers[] = $this->_info['layer'];

        foreach ($layers as $layer)
        {
            $img = $this->processLayer($layer);
            $hash_files[] = $img['image'];
            $layer_images[] = $img;
        }



        $output_name = $this->getOutputFileName();
        $file_name = $this->getTmpFileName($hash_files, $output_name);

        $output_img = File::filename($this->_output_folder, $file_name);

        if (!file_exists($output_img))
        {
            ImageMagick::combineImages($background, $output_img, $layer_images);
        }

        return new ImageData($output_name, $output_img);
    }

    private function renderText($foreground_info)
    {
        $info = $foreground_info['text'];
        
        $text_image_name = md5($this->_layer . $this->_lang . $this->_working_dir . $this->_xml_file.Trans::tr($info['value'], $this->_lang)) . ".png";
		$this->_layer++;
        $text_image_name = $this->getTmpFileName(array($this->_xml_file), $text_image_name);

        

        $text_image = File::filename(Config::$_STORAGE_DIR, $text_image_name);

        if (!file_exists($text_image))
        {
            ImageMagick::renderText($text_image, Trans::tr($info['value'], $this->_lang), $info['width'], $info['height'], $info['color'], Trans::tr($info['font'], $this->_lang));
        }

        return $text_image;
    }

    private function getTmpFileName($hash_files, $name)
    {
        $text = '';
        foreach ($hash_files as $f)
        {
            $text .= filemtime($f) . '-';
        }
        return md5($text . $name) . '.' . $this->_lang . '.' . $name;
    }

    private function getOutputFileName()
    {
        return str_replace('{lang}', $this->_lang, $this->_info['name']);
    }

}

