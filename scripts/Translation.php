<?php

require_once 'XmlRead.php';
function langToMap($xml_file)
{
	$xml_lang = xmlRead($xml_file);
	$res = array();
	if(is_array($xml_lang['translations']['pair']))
	{
		foreach($xml_lang['translations']['pair'] as $value)
		{
			$key = sha1($value['source']);
			$res[$key] = str_replace('\n', "\n", $value);
		}
	}
	return $res;
}
function preserveFormat($text, $original)
{
	$e = 'UTF-8';
	$letter_n = mb_substr($text, 0, 1, $e);
	$letter_o = mb_substr($original, 0, 1, $e);
	
	if(mb_strtoupper($letter_o, $e) == $letter_o)
	{
		$letter_n = mb_strtoupper($letter_n, $e);
		$text = $letter_n . mb_substr($text, 1, mb_strlen($text, $e)-1, $e);
	}
	return $text;
}

function readGrans($original_map, $gtrans_file)
{
	$res_map = $original_map;
	$contents = file_get_contents($gtrans_file)."\n";
	$contents = str_replace("\r", "", $contents);
	
	$arr = explode("\n*****\n", $contents);
	foreach($arr as $v)
	{
		$lines = explode("\n", $v);
		$size = count ($lines);
		if($size >= 2)
		{
			
			$key = trim($lines[0]);
			$text = implode("\n", array_slice($lines, 1));
			$text = preserveFormat($text, $original_map[$key]['destination']);
			$res_map[$key]['destination'] = $text;
		}
	}
	return $res_map;
}
function langMapToGtrans($lang_map, $gtrans_file)
{
	$text = '';
	foreach($lang_map as $key=>$value)
	{
		$text .= $key."\n";
		$text .= mb_strtolower($value['destination'], 'UTF-8')."\n";
		$text .= "*****\n";
	}
	
	file_put_contents($gtrans_file, $text);
}
function langMapToXML($lang_map, $xml_file)
{
	$text = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<translations>'."\n";
	foreach($lang_map as $key=>$value)
	{
		$text .= "<pair>\n\t<source>{$value['source']}</source>\n\t<destination>".(str_replace("\n", '\n', $value['destination']))."</destination>\n</pair>\n";
	}
	$text .= '</translations>';
	file_put_contents($xml_file, $text);
}

function curl($url, $post, $params = array(),$is_coockie_set = false)
{
 
	if(!$is_coockie_set)
	{
		/* STEP 1. let’s create a cookie file */
		$ckfile = tempnam ("/tmp", "CURLCOOKIE");
		 
		/* STEP 2. visit the homepage to set the cookie properly */
		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec ($ch);
	}
	 
	$str = ''; $str_arr= array();
	foreach($params as $key => $value)
	{
		$str_arr[] = urlencode($key)."=".urlencode($value);
	}
	if(!empty($str_arr))
		$str = '?'.implode('&',$str_arr);
 
	/* STEP 3. visit cookiepage.php */
 
	$Url = $url.$str;
	 
	$ch = curl_init ($Url);
	curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

	$output = curl_exec ($ch);
	return $output;
}
function googleOutputToTranslation($output)
{
	$arr = explode("],[", $output);
	$size = count($arr);
	
	$res = '';
	
	for($i = 0; $i+1 < $size; ++$i)
	{
		$tmp = explode('"', $arr[$i]);
		$res .= $tmp[1];
	}
	$res = str_replace('\n', "\n", $res);
	return $res;
}
function GoogleTranslate($word,$from, $to)
{
	$word = urlencode($word);

	$url = 'http://translate.google.com/translate_a/t?client=t&hl=en&sl='.$from.'&tl='.$to.'&ie=UTF-8&oe=UTF-8&otf=2&pc=1&ssel=0&tsel=0&sc=1';
 

	$name_en = curl($url, 'q='.$word);
 
	file_put_contents("test.txt", $name_en);
	//$name_en = explode('"',$name_en);
	return  googleOutputToTranslation($name_en);
}

class Trans
{
	public static $_TRANS_DIR='';
	private static $_fonts;
	public static function tr($text, $lang)
	{
		if(!is_array($text))
			return $text;
		if(!isset($text['t']))
			return '';
		$code = $text['t'];
		if($code == "font")
			return self::getLangFont($lang);
		return self::translate($code, $lang);
		
	}
	
	private static function getLangFont($lang)
	{
		if(!is_array(self::$_fonts))
		{
			$tmp = xmlRead(self::$_TRANS_DIR.'/fonts.xml');
			self::$_fonts = $tmp['font'];
		}
		
		if(isset(self::$_fonts[$lang]))
			return self::$_fonts[$lang];
		return self::$_fonts['default'];
	}
	
	private static $_lang_map = array();
	private static function translate($code, $lang)
	{
		self::prepareLangMap($lang);
		$hash = sha1($code);
		if(isset(self::$_lang_map[$lang][$hash]))
			return self::$_lang_map[$lang][$hash]['destination'];
		return $code;
	}
	
	private static function prepareLangMap($lang)
	{
		if(!isset(self::$_lang_map[$lang]))
		{
			self::$_lang_map[$lang] = langToMap(self::$_TRANS_DIR.'/'.$lang.'.xml');
		}
	}

}
