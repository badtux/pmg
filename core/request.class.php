<?php
namespace Core;
use Core\Analytic;

class Request {
	private $userAgent  = null;
	private $remoteAddr = null;
	private $remotePort = null;
	private $httpReferer = null;
	private $requestURI = null;
	private $requestMethod = null;
	private $userPassBack = null;
	private $passBack = null;
	private $xmlHttpRequest = null;
	private $route = null;
	private $geo = null;
	private $requestHeaders = null;

	private $v2Support = null;

	private $m = null;
	private $o = null;
	private $a = null;
	private $d = null;
	private $g = null;

	private $G = null;
	private $P = null;
	private $C = null;
	private $S = null;
	private $F = null;

	private $_sanitizer = null;
	private static $_sanitizable = true;

	public static function build() {

		$request = null;
		if(isset($_SESSION['__pmg.request'])) {
			$request = unserialize($_SESSION['__pmg.request']);
		}

		if($request instanceof Request) {
			if(self::$_sanitizable) { $request->setSanitizer(Sanitizer::getInstance()); }
			$request->process();
			return $request;
		}
		else {
			return new Request(true);
		}
	}

	public function setSanitizer(Sanitizer $s) {
		$this->_sanitizer = $s;
	}

	public function __destruct() {
		if($this->v2Support) {
			$_SESSION['__pmg.request'] = serialize($this);
		}
		/* Log the request */
		Log::logRequest($this);
	}

	public function __sleep() {
		if($this->v2Support) {
			return array('passBack','v2Support','remoteAddr','geo');
		}
	}

	public function __construct($v2Support=false) {

		$this->v2Support = $v2Support;
		if(self::$_sanitizable) { $this->_sanitizer = Sanitizer::getInstance(); }
		$this->process();
	}

	private function geoFix() {
		$thisIp = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];

		//$thisIp = '184.73.10.147'; // VA Ashburn
		//$thisIp = '174.120.13.154'; //houston
		//$thisIp = '8.8.8.8'; // mountainview
		//$thisIp = '203.115.0.23'; //srilanka
		//$thisIp = '68.71.131.135'; //denver
		//$thisIp = '198.171.79.36'; //englewood
		//$thisIp = '24.56.33.0'; // phonix AZ

		if($thisIp != $this->remoteAddr) {
			$this->remoteAddr = $thisIp;
			//Log::write(__METHOD__ . ' ' . $this->remoteAddr);
			if(function_exists('geoip_record_by_name')) { 
				// was throw new Exception('method `geoip_record_by_name` does not exists');
				if(($geoData = @geoip_record_by_name($this->remoteAddr)) !== false) {
					$geoData['city_code']=strtolower($geoData['country_code'].'-'.$geoData['region'].'-'.preg_replace('([\s-])','_', $geoData['city']));
					$this->geo = $geoData;
				}
			}
		}
	}

	private function process() {
		//$this->purifier = new HTMLPurifier();
		$this->geoFix();
		//Log::write(__METHOD__);
		$this->G = (object)array_map(array($this,'_clean'),$_GET);

        if(isset($this->G->route)) {
            $this->route = $this->G->route;
            unset($this->G->route);
        }

		$this->P = (object)array_map(array($this,'_clean'),$_POST);
		$this->C = (object)array_map(array($this,'_clean'),$_COOKIE);

		if(isset($_FILES) && (bool)count($_FILES)) {
			foreach ($_FILES as $elementName => $uploadData) {
				if(is_array($uploadData['error'])) {
					$this->F[$elementName] = array();
					foreach ($uploadData['error'] as $fileIndex => $error) {
						if($error==0) {
							array_push($this->F[$elementName],(object)array(
								'name' => $_FILES[$elementName]['name'][$fileIndex],
								'type' => $_FILES[$elementName]['type'][$fileIndex],
								'error' => $_FILES[$elementName]['error'][$fileIndex],
								'tmp_name' => $_FILES[$elementName]['tmp_name'][$fileIndex],
								'size' => $_FILES[$elementName]['size'][$fileIndex]
							));
						}
					}
				}
				else if($uploadData['error'] == 0) {
					$this->F[$elementName] = (object)$uploadData;
				}
			}
		}

		$this->g = (object)(array)$this->G;
		$this->d = (object)array_merge((array)$this->G,(array)$this->P,(array)$this->C,(array)$this->F);

		$this->userAgent  = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        //$this->remoteAddr = $_SERVER['REMOTE_ADDR'];
        $this->remotePort = $_SERVER['REMOTE_PORT'];
        $this->requestURI = $_SERVER['REQUEST_URI'];
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->xmlHttpRequest = ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
        							|| ((isset($this->d->v)) && ($this->d->v == 'r')));

        if(property_exists($this->d, '__pmg_user_pb') || property_exists($this->d, '__pb')) {
        	if(property_exists($this->d, '__pb')) { $pb = $this->d->__pb; } else { $pb = $this->d->__pmg_user_pb; }
			$this->passBack = $this->userPassBack = rtrim(parse_url($pb,PHP_URL_PATH) . '?' . parse_url($pb,PHP_URL_QUERY) . '#' . parse_url($pb,PHP_URL_FRAGMENT),'?#');
			unset($this->d->{'__pmg_user_pb'}); unset($this->d->{'__pb'});
        }

        if(isset($_SERVER['HTTP_REFERER'])) { $this->httpReferer = $_SERVER['HTTP_REFERER']; }
	}

	public function userPassBack(&$passBack) {
		if(is_null($passBack) && !is_null($this->userPassBack)) {
			$passBack = $this->userPassBack;
		}
		else if(!is_null($passBack) && is_null($this->userPassBack)) {
			$this->userPassBack = &$passBack;
		}
	}

	public function __set($property, $value) {
		if(in_array($property,array('m','o','a')) && property_exists(__CLASS__,$property)) {
            $this->{$property} = $this->filter($value);
            return true;
		}
        return false;
	}

	public function hasParam($propertyName) {
		return (property_exists($this->d,$propertyName));
	}

	public function getParam($propertyName) {
		if($this->hasParam($propertyName)) {
			return $this->d->{$propertyName};
		}

		throw new Exception(__METHOD__.' `'.$propertyName.'` not found');
	}

	public function get__PB($part=null) {
		if(is_null($part)) {
			return $this->passBack;
		}
		else {
			$d = parse_url($this->passBack); parse_str($d['query'],$d);
			return array_key_exists($part, $d) ? $d[$part] : null;
		}
		return null;
	}

	public function __get($property) {
        if(in_array($property,array('m','o','a','d','g','route')) && property_exists(__CLASS__,$property)) {
            return $this->{$property};
        }

        if($property == 'is_xmlHttpRequest') { return $this->xmlHttpRequest; }
		if($property == 'is_post') { return (strtoupper($this->requestMethod) == 'POST'); }
		if($property == 'is_get') { return (strtoupper($this->requestMethod) == 'GET');}
		if($property == 'is_delete') {return (strtoupper($this->requestMethod) == 'DELETE');}
		if($property == '__geo') { return !is_null($this->geo)?(object)$this->geo:null; }
        if($property == '__method') { return strtoupper($this->requestMethod); }
        if($property == '__uri') { return $this->requestURI; }
        if($property == '__pb') { $passBack = $this->passBack; $this->passBack = null; return $passBack; }
		if($property == '__passback') { $passBack = $this->userPassBack; $this->userPassBack = null; return $passBack; }
        if($property == '__referer') {
        	if(app_domain == substr(parse_url($this->httpReferer,PHP_URL_HOST),-strlen(app_domain))) {
				return (string)(parse_url($this->httpReferer,PHP_URL_PATH));
        	}
        	else {
				return (string)$this->httpReferer;
        	}
        }
        return false;
	}

	/**
	 * @deprecated
	 * @param scalar $data
	 * @return Ambigous <Purified, string, s, boolean>
	 */
	private function filter($data) {
		if(is_array($data)) {
			array_map(array($this,'filter'), $data);
		}
		else {
			$data = $this->_sanitize ? $this->_sanitizer->clean($data) : $data;
			return (mb_check_encoding($data, 'UTF-8')) ? $data : $this->filter(utf8_encode($data));
		}

	}

	private function _sanitize($data) {
		$data = self::$_sanitizable ? $this->_sanitizer->clean($data) : $data;
		return (mb_check_encoding($data, 'UTF-8')) ? $data : $this->_sanitize(utf8_encode($data));
	}

	private function _clean($data) {
		$cleaned = is_array($data) ? array_map(array($this,'_clean'), $data) : $this->_sanitize($data);
		return $cleaned;
	}

	public function getLoggableRequest() {
		return Analytic::get_loggable_request($this);
	}

	public function getUserAgent() {
		return $this->userAgent;
	}

	public function getRemoteAddress() {
		return $this->remoteAddr;
	}

	public function getRemotePort() {
		return $this->remotePort;
	}

	public function getSession() {
		if(isset($_SESSION['session.owner'])) {
			$session =  (object)$_SESSION['session.owner'];
			$session->id = session_id();
			return $session;
		}
		return null;
	}

	public function getHeader($name=null) {
		if(!function_exists('getallheaders')) { return null; }
		$headers = getallheaders();

		if(!is_null($name) && array_key_exists($name,$headers )) {
			return $headers[$name];
		}
		return $headers;
	}
}
?>