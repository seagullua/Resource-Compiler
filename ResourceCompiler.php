<?php
ini_set('html_errors', 0);
set_time_limit(0);
require 'scripts/Composer.php';
require_once 'scripts/Translation.php';

function die_e($mess)
{
    echo $mess ."\n\n";
    echo "Usage: \nResourceCompiler.php ".'"texture_packer=%PACKER_DIR%;platform=android;project=mif;source=%RESOURCE_STORAGE%;destination=%OUTPUT_DIR%;language=en,ru"'."\n\n";
    die();
}

$params = $argv[1];


$font_dir = File::filename(__DIR__, 'fonts');
$process_dir = File::filename(__DIR__, 'process/tmp');
$storage_dir = File::filename(__DIR__, 'process/storage');

Dir::create($process_dir);
Dir::create($storage_dir);


ImageMagick::$_FONT_DIR = Dir::realpath($font_dir);
Config::setBaseDir(Dir::realpath(File::filename(__DIR__, 'process')));

$texture_packer_dir = '';
$platform = '';
$project = '';

$source = '';
$destination = '';
$translation_dir = '';
$lang = array('en');
$translate = array();

$p = explode(';', $params);
foreach ($p as $k)
{
    $t = explode('=', $k);
    $key = trim($t[0]);
    $value = trim($t[1]);
    
    if($key == "texture_packer")
        $texture_packer_dir = $value;
    else if($key == "platform")
        $platform = $value;
    else if($key == "project")
        $project = $value;
    else if($key == "source")
    {
        $source = Dir::realpath($value);
    }
    else if($key == "destination")
    {
        $destination = Dir::realpath($value);
    }
    else if($key == "language")
    {
        $lang = explode(",", $value);
    }
	else if($key == "translate")
	{
		$translate = explode(",", $value);
	}
	else if($key == "translation_dir")
	{
		$translation_dir = Dir::realpath($value);
	}

}

if(!$destination)
    die_e("Error: wrong destination path.");
if(!$source)
    die_e("Error: wrong source path.");

if(!File::exists($texture_packer_dir))
    die_e("TexturePacker is not found!");

Config::$_TEXTURE_PACKER = $texture_packer_dir;
Dir::cleanDir(Config::$_TMP_DIR);


$composer = new Composer($source, $destination);

if($project)
    $composer->setProject($project);
if($lang)
    $composer->setLanguages($lang);
if($platform)
    $composer->setPlatform($platform);
if($translate)
	$composer->setTranslations($translate);
if($translation_dir)
	$composer->setTranslationsDir($translation_dir);

Trans::$_TRANS_DIR = $translation_dir;
	
$composer->run();

?>
