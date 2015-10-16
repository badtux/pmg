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

	public static function get_video_info($videoId){
		$youtubeAPI = '//gdata.youtube.com/feeds/api/videos/';
		$youtubeimageServer = '//img.youtube.com/vi/';
		$youtubeUrl = '//www.youtube.com/watch?v=';

		if(strlen($videoId) == 11){
			$init = curl_init();
			curl_setopt($init, CURLOPT_URL, 'https://gdata.youtube.com/feeds/api/videos/'.$videoId);
			curl_setopt($init, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($init);
			$xml = simplexml_load_string($output);
			if($xml instanceof SimpleXMLElement && $xml !== false) {
				return (object)array(
					'title' => (string)$xml->title,
					'pic' => $youtubeimageServer . $videoId .'/default.jpg',
					'url' => $youtubeUrl.$videoId,
					'id' => $videoId,
					'iframe' => '<iframe width="640" height="360" src="//www.youtube.com/embed/'.$videoId.'?wmode=transparent&showinfo=0&rel=0" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>'
				);
			}
		}
		else if(strlen($videoId) == 8){
			$init = curl_init();
			curl_setopt($init, CURLOPT_URL, 'https://vimeo.com/api/v2/video/'.$videoId.'.xml');
			curl_setopt($init, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($init);
			$xml = simplexml_load_string($output);
			if($xml instanceof SimpleXMLElement && $xml !== false) {
				return (object)array(
					'title' => (string)$xml->video->title,
					'pic' => (string)$xml->video->thumbnail_small,
					'url' => (string)$xml->video->url,
					'id' => (string)$xml->video->id,
					'iframe' => '<iframe src="//player.vimeo.com/video/'.(string)$xml->video->id.'?title=0&byline=0&portrait=0&color=#000" width="640" height="360" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>'
				);
			}
		}

		return false;
	}

	public static function get_qrcode($stringToEncode){
		if(function_exists('ImageCreate')) {
			require_once pmg_root . '3rdparty' . DIRECTORY_SEPARATOR . 'phpqrcode/qrlib.php';
			ob_start(); $stringToEncode = (is_string($stringToEncode))?$stringToEncode:'ERROR';
			QRcode::png($stringToEncode,false,'L',7,2);
			return ob_get_clean();
		}
		else {
			throw new Exception(__METHOD__.' GD is required!');
		}
	}

	public static function get_rsvp_text($code){
		$rsvp_text= array('yes'=>'Going','no'=>'Not Going','maybe'=>'Maybe');
		return array_key_exists($code,$rsvp_text)?$rsvp_text[$code]:null;
	}

	public static function getContinentByCountry($countryCode){
		foreach (self::get_meta_world_continents() as $continentCode => $continent) {
			if(in_array(strtoupper($countryCode), $continent['countries'])){
				return $continentCode;
			}
		}
		return null;
	}

	public static function get_meta_world_continents($continentCode = null){
		$continentList = array(

				'NA' => array('name'=>'NorthAmerica','countries'=> array('AI','AG','AW','BS','BB','BZ','BM','BQ','VG','CA','KY','CR','CU','CW','DM','DO',
					'SV','GL','GD','GP','GT','HT','HN','JM','MQ','MX','MS','AN','NI','PA','PR','BL','KN','LC','MF','PM','VC','SX','TT','TC','VI','US')),

				'SA' => array('name'=>'SouthAmerica','countries'=> array('AR','BO','BR','CL','CO','EC','FK','GF','GY','PY','PE','SR','UY','VE')),


				'EU' => array('name'=>'Europe','countries'=> array('AL','AD','AT','BY','BE','BA','BG','HR','CY','CZ','DK','EE','FO','FI','FR','DE','GI',
					'GR','GG','HU','IS','IE','IM','IT','JE','XK','LV','LI','LT','LU','MK','MT','MD','MC','ME','NL','NO','PL','PT','RO','RU','SM','RS','CS',
					'SK','SI','ES','SJ','SE','CH','UA','GB','VA','AX')),

				'OC' => array('name'=>'Oceania','countries'=> array('AS','AU','CK','TL','FJ','PF','GU','KI','MH','FM','NR','NC','NZ','NU','NF','MP','PW',
					'PG','PN','WS','SB','TK','TO','TV','UM','VU','WF')),

				'AF' => array('name'=>'Africa','countries'=> array('DZ','AO','BJ','BW','BF','BI','CM','CV','CF','TD','KM','CD','CG','DJ','EG','GQ',
					'ER','ET','GA','GM','GH','GN','GW','CI','KE','LS','LR','LY','MG','MW','ML','MR','MU','YT','MA','MZ','NA','NE','NG','RW','RE','SH','SN',
					'SC','SL','SO','ZA','SD','SZ','ST','TZ','TG','TN','UG','EH','ZM','ZW')),

				'AN' => array('name'=>'Antarctica','countries'=> array('AQ','BV','TF','HM','GS')),

				'AS' => array('name'=>'Asia','countries'=> array('AF','AM','AZ','BH','BD','BT','IO','BN','KH','CN','CX','CC','GE','HK','IN','ID',
					'IR','IQ','IL','JP','JO','KZ','KW','KG','LA','LB','MO','MY','MV','MN','MM','NP','KP','OM','PK','PS','PH','QA','SA','SG','KR',
					'LK','SY','TW','TJ','TH','TR','TM','AE','UZ','VN','YE'))

		);
		if(is_null($continentCode)) {
			return $continentList;
		}
		else {
			if(array_key_exists($continentCode = strtoupper($continentCode), $continentList)) {
				return $continentList[$continentCode];
			}
			return null;
		}
	}

	public static function get_meta_us_states($stateCode=null) {
		$stateList = array(
				'AL' => 'Alabama','AK' => 'Alaska','AZ' => 'Arizona','AR' => 'Arkansas','CA' => 'California','CO' => 'Colorado',
				'CT' => 'Connecticut','DE' => 'Delaware','DC' => 'District Of Columbia','FL' => 'Florida','GA' => 'Georgia','HI' => 'Hawaii',
				'ID' => 'Idaho','IL' => 'Illinois','IN' => 'Indiana','IA' => 'Iowa','KS' => 'Kansas','KY' => 'Kentucky',
				'LA' => 'Louisiana','ME' => 'Maine','MD' => 'Maryland','MA' => 'Massachusetts','MI' => 'Michigan','MN' => 'Minnesota',
				'MS' => 'Mississippi','MO' => 'Missouri','MT' => 'Montana','NE' => 'Nebraska','NV'=>'Nevada','NH' => 'New Hampshire',
				'NJ' => 'New Jersey','NM' => 'New Mexico','NY' => 'New York','NC' => 'North Carolina','ND' => 'North Dakota','OH' => 'Ohio',
				'OK' => 'Oklahoma','OR' => 'Oregon','PA' => 'Pennsylvania','RI' => 'Rhode Island','SC' => 'South Carolina','SD' => 'South Dakota',
				'TN' => 'Tennessee','TX' => 'Texas','UT' => 'Utah','VT' => 'Vermont','VA' => 'Virginia','WA' => 'Washington',
				'WV' => 'West Virginia','WI' => 'Wisconsin','WY' => 'Wyoming'
		);

		if(is_null($stateCode)) {
			return $stateList;
		}
		else {
			if(array_key_exists($stateCode= strtoupper($stateCode), $stateList)) {
				return $stateList[$stateCode];
			}
			return null;
		}
	}

	public static function get_meta_world_countries($countryCode=null) {
		$countryList = array(
				'AF'=>'Afghanistan','AX'=>'Aland Islands','AL'=>'Albania','DZ'=>'Algeria','AS'=>'American Samoa','AD'=>'Andorra','AO'=>'Angola',
				'AI'=>'Anguilla','AG'=>'Antigua And Barbuda','AR'=>'Argentina','AM'=>'Armenia','AW'=>'Aruba','AU'=>'Australia','AT'=>'Austria',
				'AZ'=>'Azerbaijan','BS'=>'Bahamas','BH'=>'Bahrain','BD'=>'Bangladesh','BB'=>'Barbados','BY'=>'Belarus','BE'=>'Belgium','BZ'=>'Belize',
				'BJ'=>'Benin','BM'=>'Bermuda','BT'=>'Bhutan','BO'=>'Bolivia','BA'=>'Bosnia And Herzegovina','BW'=>'Botswana','BV'=>'Bouvet Island',
				'BR'=>'Brazil','IO'=>'British Indian Ocean Territory','BN'=>'Brunei Darussalam','BG'=>'Bulgaria','BF'=>'Burkina Faso','BI'=>'Burundi',
				'KH'=>'Cambodia','CM'=>'Cameroon','CA'=>'Canada','CV'=>'Cape Verde','KY'=>'Cayman Islands','CF'=>'Central African Republic',
				'TD'=>'Chad','CL'=>'Chile','CN'=>'China','CX'=>'Christmas Island','CC'=>'Cocos (Keeling) Islands','CO'=>'Colombia','KM'=>'Comoros',
				'CG'=>'Congo','CD'=>'Congo, Democratic Republic','CK'=>'Cook Islands','CR'=>'Costa Rica','CI'=>'Cote D\'Ivoire','HR'=>'Croatia',
				'CU'=>'Cuba','CY'=>'Cyprus','CZ'=>'Czech Republic','DK'=>'Denmark','DJ'=>'Djibouti','DM'=>'Dominica','DO'=>'Dominican Republic',
				'EC'=>'Ecuador','EG'=>'Egypt','SV'=>'El Salvador','GQ'=>'Equatorial Guinea','ER'=>'Eritrea','EE'=>'Estonia','ET'=>'Ethiopia',
				'FK'=>'Falkland Islands (Malvinas)','FO'=>'Faroe Islands','FJ'=>'Fiji','FI'=>'Finland','FR'=>'France','GF'=>'French Guiana',
				'PF'=>'French Polynesia','TF'=>'French Southern Territories','GA'=>'Gabon','GM'=>'Gambia','GE'=>'Georgia','DE'=>'Germany',
				'GH'=>'Ghana','GI'=>'Gibraltar','GR'=>'Greece','GL'=>'Greenland','GD'=>'Grenada','GP'=>'Guadeloupe','GU'=>'Guam','GT'=>'Guatemala',
				/*'GG'=>'Guernsey',*/'GN'=>'Guinea','GW'=>'Guinea-Bissau','GY'=>'Guyana','HT'=>'Haiti','HM'=>'Heard Island & Mcdonald Islands',
				'VA'=>'Holy See (Vatican City State)','HN'=>'Honduras','HK'=>'Hong Kong','HU'=>'Hungary','IS'=>'Iceland','IN'=>'India','ID'=>'Indonesia',
				'IR'=>'Iran, Islamic Republic Of','IQ'=>'Iraq','IE'=>'Ireland',/*'IM'=>'Isle Of Man',*/'IL'=>'Israel','IT'=>'Italy','JM'=>'Jamaica',
				'JP'=>'Japan',/*'JE'=>'Jersey',*/'JO'=>'Jordan','KZ'=>'Kazakhstan','KE'=>'Kenya','KI'=>'Kiribati','KR'=>'Korea','KW'=>'Kuwait','KG'=>'Kyrgyzstan',
				'LA'=>'Lao People\'s Democratic Republic','LV'=>'Latvia','LB'=>'Lebanon','LS'=>'Lesotho','LR'=>'Liberia','LY'=>'Libyan Arab Jamahiriya',
				'LI'=>'Liechtenstein','LT'=>'Lithuania','LU'=>'Luxembourg','MO'=>'Macao','MK'=>'Macedonia','MG'=>'Madagascar','MW'=>'Malawi',
				'MY'=>'Malaysia','MV'=>'Maldives','ML'=>'Mali','MT'=>'Malta','MH'=>'Marshall Islands','MQ'=>'Martinique','MR'=>'Mauritania',
				'MU'=>'Mauritius','YT'=>'Mayotte','MX'=>'Mexico','FM'=>'Micronesia, Federated States Of','MD'=>'Moldova','MC'=>'Monaco',
				'MN'=>'Mongolia','ME'=>'Montenegro','MS'=>'Montserrat','MA'=>'Morocco','MZ'=>'Mozambique','MM'=>'Myanmar','NA'=>'Namibia','NR'=>'Nauru',
				'NP'=>'Nepal','NL'=>'Netherlands','AN'=>'Netherlands Antilles','NC'=>'New Caledonia','NZ'=>'New Zealand','NI'=>'Nicaragua','NE'=>'Niger',
				'NG'=>'Nigeria','NU'=>'Niue','NF'=>'Norfolk Island','MP'=>'Northern Mariana Islands','NO'=>'Norway','OM'=>'Oman','PK'=>'Pakistan',
				'PW'=>'Palau','PS'=>'Palestinian Territory, Occupied','PA'=>'Panama','PG'=>'Papua New Guinea','PY'=>'Paraguay','PE'=>'Peru',
				'PH'=>'Philippines','PN'=>'Pitcairn','PL'=>'Poland','PT'=>'Portugal','PR'=>'Puerto Rico','QA'=>'Qatar','RE'=>'Reunion','RO'=>'Romania',
				'RU'=>'Russian Federation','RW'=>'Rwanda','SH'=>'Saint Helena','KN'=>'Saint Kitts And Nevis','LC'=>'Saint Lucia',
				'PM'=>'Saint Pierre And Miquelon','VC'=>'Saint Vincent And Grenadines','WS'=>'Samoa','SM'=>'San Marino','ST'=>'Sao Tome And Principe',
				'SA'=>'Saudi Arabia','SN'=>'Senegal','RS'=>'Serbia','SC'=>'Seychelles','SL'=>'Sierra Leone','SG'=>'Singapore','SK'=>'Slovakia',
				'SI'=>'Slovenia','SB'=>'Solomon Islands','SO'=>'Somalia','ZA'=>'South Africa','GS'=>'South Georgia And Sandwich Isl.','ES'=>'Spain',
				'LK'=>'Sri Lanka','SD'=>'Sudan','SR'=>'Suriname','SJ'=>'Svalbard And Jan Mayen','SZ'=>'Swaziland','SE'=>'Sweden','CH'=>'Switzerland',
				'SY'=>'Syrian Arab Republic','TW'=>'Taiwan','TJ'=>'Tajikistan','TZ'=>'Tanzania','TH'=>'Thailand','TL'=>'Timor-Leste','TG'=>'Togo',
				'TK'=>'Tokelau','TO'=>'Tonga','TT'=>'Trinidad And Tobago','TN'=>'Tunisia','TR'=>'Turkey','TM'=>'Turkmenistan','TC'=>'Turks And Caicos Islands',
				'TV'=>'Tuvalu','UG'=>'Uganda','UA'=>'Ukraine','AE'=>'United Arab Emirates','GB'=>'United Kingdom','US'=>'United States',
				'UM'=>'United States Outlying Islands','UY'=>'Uruguay','UZ'=>'Uzbekistan','VU'=>'Vanuatu','VE'=>'Venezuela','VN'=>'Viet Nam',
				'VG'=>'Virgin Islands, British','VI'=>'Virgin Islands, U.S.','WF'=>'Wallis And Futuna','EH'=>'Western Sahara','YE'=>'Yemen','ZM'=>'Zambia','ZW'=>'Zimbabwe'
		);

		if(is_null($countryCode)) {
			return $countryList;
		}
		else {
			$countryCode = strtoupper(substr($countryCode, 0,2));
			if(array_key_exists($countryCode, $countryList)) {
				return $countryList[$countryCode];
			}
			return null;
		}
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
	 * @param string $url
	 * @param string $campaign the weekly campain constant
	 */
	public static function getTrackerURL($url,$campaign){
		$urlParts = parse_url($url);
		if(isset($urlParts['query'])) {
			$urlParts['query'] = trim($urlParts['query'],'&').'&utm_source=weekly&utm_medium=email&utm_campaign='.urlencode($campaign);
		}
		else{
			$urlParts['query'] = 'utm_source=weekly&utm_medium=email&utm_campaign='.urlencode($campaign);
		}

		if(isset($urlParts['path'])) {
			$url = $urlParts['scheme'].'://'.$urlParts['host'].$urlParts['path'].'?'.$urlParts['query'];
		}
		else {
			$url = $urlParts['scheme'].'://'.$urlParts['host'].'?'.$urlParts['query'];
		}

		if(isset($urlParts['fragment'])) {
			$url = $url.'#'.$urlParts['fragment'];
		}
		return $url;
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

	public static function shortenUrlV2($batch,$url=null,$shortened=false,$save=false) {
		$ds = Ds::connect(ds_bands);
		$times = 0;
		if($shortened) {
			//find the band and return
			$band = $ds->findOne(array('_id'=>$shortened));
			if($band) {
				return $band;
			}
			else {
				return false;
			}
		}
		if(!is_null($url)) {
			//convert the url to shrten form
			$shortened = self::getShortenForm($url);
			$insert = array('_id' => $shortened,
							'ts' => time(),
							'batch' => $batch,
							'registered' => false);
			try{
				$ds->insert($insert,array('safe' => true));
				return $shortened;
			}
			catch (Exception $e) {
				switch ($e->getCode()) {
					case 11000:
						$times++;
						if($times < 500) {
							self::shortenUrlV2($batch,$url);
						}
						break;
				}
			}
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
		if(($error['type'] == E_ERROR || $error['type'] == E_USER_ERROR) && app_live) {
			try{
				Mailer::submit(app_mail_from_email, array(
						'to' => array('toName' => 'astroanu2004@gmail.com', 'toMail' => 'astroanu2004@gmail.com')
				), 'We have a problem in '.app_name_visible, array(
						'message' => $error['message'],
						'file' => $error['file'],
						'line' => $error['line'],
						'url' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null
				), 'default/tpl/mail.shutdown.php', md5(implode('|', array($error['file'], $error['line'], $error['message']))));
			}
			catch(Exception $e){
				Log::write($e->getMessage());
				Log::write($e->getTraceAsString());
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

	/**
	 *
	 * @param array | MongoCursor $content
	 * If content is array, there should be keys url, avatar, name
	 * @param unknown $max
	 * @return multitype:
	 */
	public static function facePile($content, $max) {
		$count = $content instanceof MongoCursor ? $content->count() : count($content);
		$moreCount = $count - $max;
		$listSliced = $list = array();

		if($moreCount > 0) {
			$listSliced = ($content instanceof MongoCursor) ? $content->limit($max) : array_slice($content, 0,$max);
		}
		else {
			$listSliced = $content;
		}

		foreach ($listSliced as $listItem) {
			array_push($list, array('url' => '/page/'.$listItem['uname'],
									'avatar' => $listItem['avatar'],
									'name' => $listItem['name']
								));
		}

		if($moreCount > 0) { array_push($list, '<span class="count rounded-avatar">+'.self::megaCount(abs($moreCount)).'</span>'); }
		return $list;

	}

	public static function megaCount($count) {
		$unit = null; $offset = 100;
		$dividers = array('U' => 1, 'K' => pow(1000, 1), 'M' => pow(1000, 2), 'G' => pow(1000, 3));

		if($count >= 500 && $count < (1000*$offset)) { $unit = 'K';}
		else if($count >= (1000*$offset) && $count < (1000*1000*$offset)) { $unit = 'M'; }
		else if($count >= (1000*1000*$offset) && $count < (1000*1000*1000*$offset)) { $unit = 'G'; }
		else {$unit = 'U'; }

		$appendableUnit = ($unit == 'U') ? '' : $unit;
		return round(($count/$dividers[$unit]),2).$appendableUnit;
	}

}
?>
