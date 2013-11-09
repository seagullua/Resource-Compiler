<?php

require_once 'Functions.php';

/**
 * Description of Composer
 *
 * @author Andriy
 */
class Composer
{

    private $_start_dir = '';
    private $_destination_dir = '';
    private $_lang = array();
	private $_translate = array();
    private $_platform = '';
    private $_project = '';
    private $_default_lang = 'en';
	private $_translate_dir = '';

    public function __construct($start_dir, $destination_dir)
    {
        $this->_start_dir = $start_dir;
        $this->_destination_dir = $destination_dir;
    }

    public function run()
    {
        $this->message("Starting...");
        $this->message("From: {$this->_start_dir}");
        $this->message("To: {$this->_destination_dir}");

        if ($this->_platform)
            $this->message("Platform: {$this->_platform}");
        if (count($this->_lang))
            $this->message("Languages: " . implode(', ', $this->_lang));
		if(count($this->_translate))
			$this->message("Translations: " . implode(', ', $this->_translate));

        if (Dir::exists($this->_destination_dir))
            Dir::deleteDir($this->_destination_dir);
        Dir::create($this->_destination_dir);

        $this->analyzeDir('');
    }

    private function analyzeDir($relative_path, $output_path="", $scale=1)
    {
		//echo "AD: {$output_path} \n";
		if(!$output_path)
			$output_path = $relative_path;
		
        $dir_name = File::filename($this->_start_dir, $relative_path);
		$output_dir = File::filename($this->_start_dir, $output_path);

		//echo $output_path ."\n";
		
        $files = scandir($dir_name);
        foreach ($files as $f)
        {
            if ($f != '.' && $f != '..')
            {
                $relative = File::filename($relative_path, $f);
				
				$relative_output = File::filename($output_path, $f);
				//echo "AD2: {$relative_output} \n";
                $absolute = File::filename($this->_start_dir, $relative);

				
				
                if (is_dir($absolute))
                {
					
				
                    $this->analyzeDir($relative, $relative_output, $scale);
                }
                else
                {
                    if (substr($f, -7, 7) == "idx.xml")
                    {
                        $this->message("* {$relative}");
						//echo("..".$output_path."..".$relative_path);
                        $this->processFile($relative, $relative_path, $output_path, $scale);
                    }
                }
            }
        }
    }

    private function processFile($relative, $directory, $output_directory, $scale)
    {
		//echo "PrF: {$output_directory} \n";
        $absolute = File::filename($this->_start_dir, $relative);

        $data = xmlRead($absolute);
        if (!isset($data['directory']))
        {
            $this->message("ERROR: wrong index file {$relative}");
            return;
        }

        $files = array();
        if (isset($data['directory']['file'][0]))
            $files = $data['directory']['file'];
        else
        {
            $files = array($data['directory']['file']);
        }

        foreach ($files as $file)
        {
            $ok_file = true;
            if(isset($file['platform']) && $this->_platform)
            {
                $arr = $file['platform'];
                if(!is_array($arr))
                    $ok_file = ($arr == $this->_platform);
                else
                {
                    $ok_file = false;
                    foreach($arr as $p)
                    {
                        if($p == $this->_platform)
                            $ok_file = true;
                    }
                }
            }
            if($ok_file && isset($file['project']) && $this->_project)
            {
                $arr = $file['project'];
                if(!is_array($arr))
                    $ok_file = ($arr == $this->_project);
                else
                {
                    $ok_file = false;
                    foreach($arr as $p)
                    {
                        if($p == $this->_project)
                            $ok_file = true;
                    }
                }
            }
            if($ok_file)
                $this->processResourceFile($directory, $file, $output_directory, $scale);
        }
    }

    private function processResourceFile($directory, $file, $output_directory, $scale)
    {
		if(isset($file['link']))
		{
			$this->analyzeDir($file['link'], $output_directory, $file['scale']+0);
			return;
		}
        $r_name = File::filename($directory, $file['name']);
        $absolute = File::filename($this->_start_dir, $r_name);
        $this->message("** {$r_name} -> {$scale}");

        $ext = File::extension($r_name);
        if($ext == 'xml')
        {
        
            $data = xmlRead($absolute);
            $key = key($data);

            $lang_array = array();
            if ($file['lang'] == 1)
            {
                $lang_array = array_merge($this->_lang, $this->_translate);
            }
            else
            {
                $lang_array[] = $this->_default_lang;
            }
            foreach ($lang_array as $lang)
            {
				$auto_translate = false;
				if(in_array($lang, $this->_translate))
					$auto_translate = true;
					
                if ($key == 'spritesheet')
                {
                    $sheet = SpriteSheetProcessor::process($absolute, $lang, $auto_translate, $scale);
                    $this->copyResources($output_directory, $sheet->getResources());
                }
                else if($key == 'image')
                {
                    $image = ImageProcessor::process($absolute, $lang, $auto_translate, $scale);
                    $this->copyResources($output_directory, $image->getResources());
                }
                else
                {
                    $this->message("ERROR: wrong file type {$key} {$r_name}");
                }
            }
        }
        else
        {
			$absolute = File::filename($output_directory, $file['name']);
			//die($absolute);
            //Just copy file
			$f_name = $file['name'];
			$images_ext = array('jpg', 'png');
			$ext = File::extension($f_name);
			if(in_array($ext, $images_ext) && $this->_scale != 1)
			{
				$source = File::filename($this->_start_dir, $r_name);
				$last_edit = filemtime($source);
				$tmp_name = File::filename(Config::$_STORAGE_DIR, md5($f_name . $last_edit . $scale).".{$ext}");
				
				if(!File::exists($tmp_name))
				{
					
					ImageMagick::scaleImage($source, $tmp_name, $scale);
				}
				$resource = new Resource(basename($f_name), $tmp_name);
				$this->copyResources($output_directory, array($resource));
			}
			else
			{
				
				$resource = new Resource(basename($f_name), File::filename($this->_start_dir, $r_name));
				$this->copyResources($output_directory, array($resource));
			}
        }
        
    }

    private function copyResources($dir, $res)
    {
        $absolute = File::filename($this->_destination_dir, $dir);
        Dir::create($absolute);
        
        foreach($res as $r)
        {
            $target_name = File::filename($absolute, $r->getName());
			$ext = File::extension($target_name);
			$compress = false;
			$optimize_colors = false;
			if($ext == "png" && $compress)
			{
				$pngout = Config::$_SCRIPT_PATH.'/pngout.exe';
				$command = '"'.$pngout.'" /s3 "'.$r->getLocation().'" "'.$target_name.'"';
				//echo $command . "\n";
				exec ($command, $output, $code);
			}
			else if($ext == "png" && $optimize_colors)
			{
				$command = 'convert "'.$r->getLocation().'" -channel A -level 20,100%,0.85 +channel -background black -alpha background "'.$target_name.'"';
				//$command = 'convert "'.$r->getLocation().'" png8:"'.$target_name.'"';
				//echo $command . "\n";
				exec ($command, $output, $code);
			}
			else
			{
				if(!File::exists($target_name))
					File::copy($r->getLocation(), $target_name);
			}
        }
    }
    
    private function message($text)
    {
        echo $text . "\n";
        flush();
    }

    public function setLanguages($arr)
    {
        $this->_lang = $arr;
    }

    public function setPlatform($platform)
    {
        $this->_platform = $platform;
    }

    public function setProject($project)
    {
        $this->_project = $project;
    }
	
	public function setTranslations($arr)
	{
		$this->_translate = $arr;
	}
	
	public function setTranslationsDir($dir)
	{
		$this->_translate_dir = $dir;
	}

}

?>
