<?php
class Statistics {

	public static function get_hour_doc_prepared($requests) {
		$document = array();
		$browsers = $urls = $countries = array();
		$total = 0;

		foreach ($requests as $request) {
			$total += 1;
			$browser = self::get_browser($request['agnt']);
			$browsers[$browser->browser] = isset($browsers[$browser->browser]) ? ($browsers[$browser->browser]+1) : 1;
			$urls[$request['uri']] = (isset($urls[$request['uri']])) ? ($urls[$request['uri']] + 1) : 1;
			$geoData = @geoip_record_by_name($request['rqst']['ip']);

			if($geoData) {
				$countries[$geoData['country_code']] = (isset($countries[$geoData['country_code']])) ? ($countries[$geoData['country_code']] + 1) : 1;
			}
		}

		$document['browser'] = $browsers;
		$document['urls'] = $urls;
		$document['country'] = $countries;
		$document['total'] = $total;
		return $document;

	}

	public static function get_browser($user_agent) {
		$browser_name = 'Unknown';
		$browser = 'unknown';
		$platform = 'Unknown';
		$ub = 'Unknown';
		$version= "";

		if(preg_match('/linux/i', $user_agent)) {
			$platform = 'linux';
		}
		elseif(preg_match('/macintosh|mac os x/i', $user_agent)) {
			$platform = 'mac';
		}
		elseif(preg_match('/windows|win32/i', $user_agent)) {
			$platform = 'windows';
		}

		if(preg_match('/MSIE/i',$user_agent) && !preg_match('/Opera/i',$user_agent)) {
			$browser_name = 'Internet Explorer';
			$browser = Analytic::BROWSER_IE;
			$ub = "MSIE";
		}
		else if(preg_match('/Firefox/i',$user_agent)) {
			$browser_name = 'Mozilla Firefox';
			$browser = Analytic::BROWSER_FF;
			$ub = "Firefox";
		}
		else if(preg_match('/Chrome/i',$user_agent)) {
			$browser_name = 'Google Chrome';
			$browser = Analytic::BROWSER_CHROME;
			$ub = "Chrome";
		}
		else if(preg_match('/Safari/i',$user_agent)) {
			$browser_name = 'Apple Safari';
			$browser = Analytic::BROWSER_SAFARI;
			$ub = "Safari";
		}
		elseif(preg_match('/Opera/i',$user_agent)) {
			$browser_name = 'Opera';
			$browser = Analytic::BROWSER_OPERA;
			$ub = "Opera";
		}
		elseif(preg_match('/Netscape/i',$user_agent)) {
			$browser_name = 'Netscape';
			$browser = Analytic::BROWSER_NETSCAPE;
			$ub = "Netscape";
		}

		$known = array('Version', $ub, 'other');
		$pattern = '#(?<browser>' . join('|', $known) . ')[/]+(?<version>[0-9.|a-zA-Z.]*)#';

		if (!preg_match_all($pattern, $user_agent, $matches)) {}
		$i = count($matches['browser']);

		if($i != 1) {
			if(strripos($user_agent,"Version") < strripos($user_agent,$ub)){
				$version = isset($matches['version'][0]) ? $matches['version'][0] : 'Unknown';
			}
			else {
				$version = isset($matches['version'][1]) ? $matches['version'][1] : 'Unknown';
			}
		}
		else {
			$version = $matches['version'][0];
		}

		if ($version == null || $version == '') {$version= "?" ;}

		return (object)array(
				'name' => $browser_name,
				'browser' => $browser,
				'version' => $version,
				'platform' => $platform,
		);
	}
}
?>