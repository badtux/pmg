<?php
namespace Core;
class Utils {
	const MAX_CHARS=6;

	private static $instance;

	public static function getTimeAbbr($unixTime){
		return '<abbr title="'. date('l, d F Y', $unixTime) . ' at ' . date('G:i', $unixTime).'" data-utime="'. $unixTime.'">'. Utils::time_offset($unixTime).'</abbr>';
	}

	public static function get_versioned_resource($path){
		return $path.'?cv='.sha1(app_version);
	}

	public static function is_timestamp($ts){
		return $ts < PHP_INT_MAX && $ts > ~PHP_INT_MAX && strtotime(date('D F Y',(int)$ts));
	}

	public static function ordinal($val){
		$ends = array('th','st','nd','rd','th','th','th','th','th','th');
		if (($val % 100) >= 11 && ($val % 100) <= 13) {
			return $val. 'th';
		}
		else {
			return $val. $ends[$val % 10];
		}
	}

	/**
	 *
	 * @param string $collection Collection name to set increment key
	 * @param string $field the key of the
	 * @return boolean|number
	 */
	public static function getNext($collection,$field='seq',$default=1) {
		$counterCollection = Ds::connect(ds_counter);
		$ret = $counterCollection->findAndModify(
			array('_id' => $collection),
			array('$inc' => array($field => 1,)),
			null,
			array('sort' => array('priority' => -1),
				  'new' => true)
		);

		if($ret != null) {
			return $ret[$field];
		}
		else{
			$counterCollection->insert(array('_id' => $collection,$field => $default));
			return $default;
		}
	}

	public static function getShortenForm($url){
		$ignore = array('I','0','O','1');
		$alphabet = array_merge(range('A','Z'),array_map(function($i){return (string)$i;},range(0,9)));

		$parsing_url = md5($url.'-'.rand().'-'.rand().'-'.microtime(true));
		$parsing_url = substr($parsing_url, strlen($parsing_url)/2-1,strlen($parsing_url)).substr($parsing_url, 0,strlen($parsing_url)/2);
		$shorten = strtoupper(substr(base_convert($parsing_url,16, 36),0,self::MAX_CHARS));

		$cleaned = array_diff($alphabet, $ignore);
		$cleaned = array_values($cleaned);
		$replaceble = array();
		array_walk($ignore,function($val,$key) use (&$replaceble,$cleaned){
			array_push($replaceble,$cleaned[rand(0, count($cleaned)-1)]);
		});
		$returnShorten = str_replace($ignore, $replaceble, $shorten);
		return $returnShorten;
	}

	public static function time_offset($t,$short=false){
		$o = (int)(time() - $t);
		if($short==false){
			switch(true) {
				case ($o <= 5): return 'just now'; break;
				case ($o < 20): return $o . ' seconds ago'; break;
				case ($o < 40): return 'half a minute ago'; break;
				case ($o < 60): return 'less than a minute ago'; break;
				case ($o <= 90): return '1 minute ago'; break;
				case ($o <= 59*60): return round($o / 60) . ' minutes ago'; break;
				case ($o <= 60*60*24): return round($o / 60 / 60) . ' hours ago'; break;
				case ($o <= 60*60*24*1.5): return 'yesterday'; break;
				case ($o <= 60*60*24*6.5): return date('l \a\t h:i A',$t); break;
				case ($o <= 60*60*24*28.5): return date('h:i A M jS',$t); break;
				//case($o < 60*60*24*7): return round($o / 60 / 60 / 24) . " days ago"; break;
				//case($o <= 60*60*24*9): return "1 week ago"; break;
				default: return date('h:i A M jS Y', $t);
			}
		}
		else{
			switch(true) {
				case ($o < 60): return $o.'s'; break;
				case ($o/60 < 60): return floor($o/60).'m'; break;
				case ($o/3600 < 24): return floor($o/3600).'h'; break;
				case ($o/86400 ): return floor($o/86400).'w'; break;
				case ($o/4492800 ): return floor($o/4492800).'y'; break;
				default: return date('h:i A M jS Y', $t);
			}
		}
	}

	public static function get_page_title($title) {
		if(is_array($title)) {
			$title = implode(' - ', $title);
		}

		return  $title . ' - ' . app_name_visible;
	}

	public static function get_meta_title($content){
		return trim(html_entity_decode($content));
	}

	public static function get_meta_description($content){
		return mb_substr(html_entity_decode(str_replace(array("\r", "\n",'  '), array(' ',' ',' '), strip_tags($content))),0,150);
		//return preg_replace('/\r\n?/', "\n", trim(mb_substr(html_entity_decode(strip_tags($content)),0,(strpos($content, '. ')-1)));

		//$content = strip_tags($content);

		preg_match('#<p[^>]*>(.*)</p>#isU', $content, $matches);
		if(isset($matches[1])) {
			return mb_substr($matches[1],0,150).'..';
		}
		print_r($matches);

		exit();
		$content = explode('</p>', $content);
		print_r($content);
		$content = explode('. ', html_entity_decode(strip_tags($content)));
		print_r($content);
		return $content[1];
		//return trim(mb_substr(,0,(strpos($content, '.')-1)));
	}

	public static function plain_to_pretified($text){
		$text = trim(htmlentities($text));
		if(mb_detect_encoding($text, 'UTF-8', true) === false) {
			$text = utf8_encode($text);
	    }
		//return $text;
	    $text = preg_replace('/\r\n?/', "\n", $text);
	    $text = stripslashes(preg_replace('/\s*\S.*?(\n\s*\n|$)/es', '"<p>" . trim("$0") . "</p>"', $text));
	    $text = preg_replace('/\s*\n\s*/', "</p><p>", $text);

	    return $text;
	}

	public static function to_plain_text($text, $stripTags = false) {
		$text = trim($text);
		if(mb_detect_encoding($text, 'UTF-8', true) === false) {
			$text = utf8_encode($text);
	    }

	    return $stripTags?strip_tags($text):$text;
	}

	public static function fixEncoding($text,$replaceNewLines=false) {
		if($replaceNewLines) {
			$text = Utils::nl2PBr($text);
		}

	    if(mb_detect_encoding($text, 'UTF-8', true) === false) {
			$text = utf8_encode($text);
	    }

	    return $text;
	}

	private static function nl2PBr($text) {
	    $text = strip_tags($text);
	    $text = preg_replace('/\r\n?/', "\n", $text);
	    $text = preg_replace('/\s*\S.*?(\n\s*\n|$)/es', '"<p>" . trim("$0") . "</p>"', $text);
	    $text = preg_replace('/\s*\n\s*/', "</p><p>", $text);

	    return $text;
	}

	public static function pretty_phone($phone, $seperator = '-'){
		$ccode = '';
		$phone=preg_replace( '/[^0-9]/', '', $phone);
		if(strlen($phone) > 10){
			$ccode = substr($phone, 0,strlen($phone)-10);
		}
		return $ccode.' '.preg_replace('/^(\d{3})(\d{3})(\d{4})$/i', '($1) $2'.$seperator.'$3', substr($phone, strlen($ccode)));
	}

	public static function pretty_dollar($v,$percentage=false) {
		if($percentage) { $v = (($v/100)*$percentage); }
		return (fmod($v, 1) > 0)? number_format($v,2,'.',','):number_format($v);
	}

	public static function pluralize($count, $singularForm, $pluralForm) {
	    return ($count == 1) ? $singularForm : $pluralForm;
	}

	public static function singleton() {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }

        return self::$instance;
    }

	private function __construct() {

	}

	public static function getTimeDiffInSeconds($start, $end=false) {
		if($end) {
			return (strtotime(date('Y-m-d H:i:s',$start)) - strtotime(date('Y-m-d H:i:s',$end)));
		}
		else {
			return (strtotime(date('Y-m-d H:i:s',$start)) - strtotime(date('Y-m-d',$start)));
		}
	}

	public static function time_more_days_to_go(\DateTime $timeInFuture, $timeNow = true) {
		$timeNow = ($timeNow instanceof \DateTime)?$timeNow:new \DateTime();
		$gap = date_diff($timeNow, $timeInFuture,false); $out = array(0,0);

		if ($gap->invert == 1){ return array(0,'days'); }
		switch (true) {
			case ($gap->y > 1):
				$out = array($gap->y,Utils::pluralize($gap->y, 'year', 'years'));
				break;

			case ($gap->days >= 150 && $gap->y <= 1):
				$out = array(($gap->y*12)+$gap->m,Utils::pluralize(($gap->y*12)+$gap->m, 'month', 'months'));
				break;

			default:
				$out = array($gap->days,Utils::pluralize($gap->days, 'day', 'days'));
				break;
		}
		return $out;
	}

	public static function storeFile(stdClass $pgFileObject, $bucket, $fileNameSuffix, $isTemp=false) {
		if($isTemp) { $uniqueFileId = 'TEMP-'; } else { $uniqueFileId = ''; };
		$unqId = $uniqueFileId . dechex(rand(1,9999)) . '-' . rand(10000,99999) . '-' . dechex(rand(1,9999)) . '-' . dechex(time());
		$bucketPath = app_upload_path . strtolower($bucket);

		if(is_dir($bucketPath)) {
			$filePath = $bucketPath . DIRECTORY_SEPARATOR . strtolower($unqId . $fileNameSuffix);
			if(!is_file($filePath)) {
				$image = $pgFileObject;
				$tempFile = fopen($image->tmp_name, 'r'); $storeFile = fopen($filePath, 'w+');
				if(is_resource($tempFile) && is_resource($storeFile)) {
					if(fwrite($storeFile, fread($tempFile,filesize($image->tmp_name)), filesize($image->tmp_name)) !== false) {
						return substr($filePath,strlen(app_upload_path)-1);
					}
					else {
						throw new Exception('failed to write `' . $filePath . '` in ' . __METHOD__);
					}
				}
				else {
					throw new Exception('failed to open `temp` or `store` file path in ' . __METHOD__);
				}
			}
			else {
				return Utils::storeFile($pgFileObject, $bucket, $fileNameSuffix, $isTemp);
			}
		}
		else {
			throw new Exception('bucket `' . $bucketPath . '` does not exist in ' . __METHOD__);
		}
	}

	public static function getHash($string, $salt=null, $saltLength=8) {
        if (is_null($salt)) {
            $salt = substr(md5(uniqid(rand(), true)), 0, $saltLength);
        }
        else {
            $salt = substr($salt, 0, $saltLength);
        }

        return $salt . hash('sha256',$salt . $string);
	}

	public static function parseMe($template, $data) {
		$template = app_core_path . trim($template,'/');
		if(is_string($data)) { $data = unserialize($data); }

		if(is_file($template) && is_array($data)) {
			ob_start();
			extract($data);
			require $template;
			$d = ob_get_contents();
			ob_end_clean();
			return $d;
		}
		else {
			throw new Exception(__METHOD__.' template `'.$template.'` not found or invalid parser data');
		}
	}

	public static function addToSessionStore($key,$value) {

		$serialized = serialize($value);
		if(isset($_SESSION['__store']) && array_key_exists($key, $_SESSION['__store'])) {
			self::updateSessionStore($key, $value);
		}
		else {
			$_SESSION['__store'][$key] = $serialized;
		}
	}

	public static function getFromSessionStore($key) {
		return (isset($_SESSION['__store'][$key])) ? unserialize($_SESSION['__store'][$key]) : null;
	}

	public static function updateSessionStore($key,$value) {
		$serialized = serialize($value);

		if(isset($_SESSION['__store'][$key])) {
			$_SESSION['__store'][$key] = $serialized;
		}
		else {
			throw new Exception('Data not found.');
		}
	}

	public static function popFromSessionStore($key) {
		if(isset($_SESSION['__store'][$key])) {
			$temp = $_SESSION['__store'][$key];
			unset($_SESSION['__store'][$key]);
			return $temp;
		}
		else {
			return null;
		}
	}

	/**
	 *
	 * @param int $duration The durations between time slot. in seconds
	 */
	public static function getTimeSlots($duration){
		$timeSlots = array();
		$i = 0;$timeString = '%s:%s %s';

		while($i<(3600*24)) {
			$h = (($i / 3600) % 24);
			$m = (int)($i % 3600) / 60;
			$ampm = (int)$h >= 12 ? 'PM' : 'AM';
			$h = $h == 0 ? 12 : $h;
			$timeSlots[$i] = sprintf($timeString,str_pad($h>12?$h-12:$h,2,0,STR_PAD_LEFT),str_pad($m,2,0,STR_PAD_RIGHT),$ampm);
			$i += $duration;
		}
		return $timeSlots;
	}

	public static function getTimeSlot($seconds) {
		$h = (($seconds / 3600) % 24);
		$m = (int)($seconds % 3600) / 60;
		$ampm = (int)$h >= 12 ? 'PM' : 'AM';
		$timeString = '%s:%s %s';
		return sprintf($timeString,str_pad($h>12?$h-12:$h,2,0,STR_PAD_LEFT),str_pad($m,2,0,STR_PAD_RIGHT),$ampm);
	}

	public static function shutDown() {		
		$error = error_get_last();

		if(is_array($error) && in_array($error['type'], [E_ERROR, E_USER_ERROR]) && app_live) {
			try {
				Log::write(__METHOD__.' -> '.$error['message']);
				Log::write(__METHOD__.' -> '.$error['file']);
				Log::write(__METHOD__.' -> '.$error['line']);

				$fromMail = array('fromEmail' => app_mail_from_email, 'fromName' => app_name_visible);
				$recipients = array(
					'to' => array(
						array('toName' => 'Support team', 'toMail' => 'support@rype3.com')
					)
				);

				Mailer::submit($fromMail, $recipients, 'We have a problem in '.app_name_visible, array(
						'message' => $error['message'],
						'file' => $error['file'],
						'line' => $error['line'],
						'url' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null
				), 'default/tpl/mail.shutdown.php', md5(implode('|', array($error['file'], $error['line'], $error['message']))));

			}
			catch(Exception $e){
				Log::write(__METHOD__.' '.$e->getMessage());
				Log::write(__METHOD__.' '.$e->getTraceAsString());
			}
		}		
	}

	/**
	 *
	 * @param timestamp $time1
	 * @param timestamp $time2
	 * @param string $format
	 */
	public static function dateDiff($time1,$time2,$format= '%R%a days') {
		$dateFormat = 'm/d/Y';
		$t1 = new \DateTime(date($dateFormat,$time1));
		$t2 = new \DateTime(date($dateFormat,$time2));
		$interval = $t1->diff($t2);
		return $format != false ? $interval->format($format) : $interval;
	}

	public static function time_passed($time) {
		$diff = (int)(time() - $time)/(3600*24);
		if($diff < 1) {
			return 'Today';
		}
		else if($diff < 2) {
			return 'Yesterday';
		}
		else {
			return date('F d,Y',$time);
		}
	}

	public static function removeKey($array, $key){
		$temp = $array;
		array_splice($temp, array_search($key, $array),1);
		return $temp;
	}

	public static function format() {
		$data = func_get_args();
		$string = func_get_arg(0);

		if (is_array(func_get_arg(1))) {
			$data = func_get_arg(1);
		}
		$usedKeys = array();
		$string = preg_replace('/\%\((.*?)\)(.)/e',
				'self::dsprintfMatch(\'$1\',\'$2\',\$data,$usedKeys)',$string);
		$data = array_diff_key($data,$usedKeys);
		return vsprintf($string,$data);
	}

	private static function dsprintfMatch($match_1,$match_2,&$data,&$usedKeys) {
		if (isset($data[$match_1])) {
			$str = $data[$match_1];
			$usedKeys[$match_1] = $match_1;
			return sprintf("%".$match_2,$str);
		}
		else {
			return "%".$match_2;
		}
	}

	public static function prettyImplode($array,$glue) {
		if(count($array) > 1) {
			$last = array_pop($array);
			return implode($glue, $array).' and '.$last;
		}
		else {
			return implode($glue, $array);
		}
	}

	public static function getRequestHeaders($key = false) {
		$headers = getallheaders();
		if($key) {
			if(isset($headers[$key])) {
				return $headers[$key];
			}
			return false;
		}
		return $headers;
	}

	public static function extractUrl($url) {
		$query = parse_url($url,PHP_URL_QUERY);
		parse_str($query,$array);
		return $array;
	}

}
?>
