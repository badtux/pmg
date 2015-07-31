<?php
namespace Core;
use Core\Request;

class Analytic {
	const COUNTER_LAST_PROCESSED = 'last_processed';

	const BROWSER_SAFARI = 'sfri';
	const BROWSER_CHROME = 'chrm';
	const BROWSER_FF = 'frfx';
	const BROWSER_IE = 'ie';
	const BROWSER_OPERA = 'opra';
	const BROWSER_NETSCAPE = 'opra';

	public static $_skippingAjaxPaths = array(
			'/d','/d/newsfeed'
			);

	public static $_skippingRegex = array(
			'/^\/signin?__pb=/',
			'/^\/system./'
			);
	public static $_skippingAgents = array(
			'/ScanAlert/','/bingbot/','/Mail.RU_Bot/','/Googlebot/','/Baiduspider/','/Feedly/','/MJ12bot/','/YandexBot/','/Wget/','/Ezooms/',
			'/EasouSpider/','/Twitterbot/','/Java/','/coccoc/','/Sogou/','/Google-HTTP-Java-Client/','/JS-Kit/','/MetaURI/','/TweetmemeBot/',
			'/facebookexternalhit/','/PaperLiBot/','/rogerbot/','/AdsBot-Google/','/Goodzer/','/Embedly/','/archive.org_bot/','/yellowpages/',
			'/msnbot/','/spotinfluence/','/ia_archiver/','/Python-urllib/','/Blog Search/','/XaXaXXaXaX/','/CFNetwork/',
			'/libwww-perl/','/Pinterest/','/EventMachine/','/CompSpyBot/','/AhrefsBot/','/spbot/','/SeznamBot/','/meanpathbot/','/Gigabot/'
			);

	public static function get_loggable_request(Request $request) {
		$user = self::get_user();
		$analytic_cookie = null;

		//if(!$request->hasParam('__ac')) { $analytic_cookie = self::set_analytic_cookie($request,$user); }
		//else { $analytic_cookie = $request->getParam('__ac'); }

		if(in_array(__ROUTER_PATH,self::$_skippingAjaxPaths) && $request->is_xmlHttpRequest) { return null; }

		if(self::filter_skippable_path($request->__uri)) { return null; }

		if(self::filter_skippable_agents($request->getUserAgent())) { return null; }

		$last_path = Utils::getFromSessionStore('last_path');

		if(!is_null($last_path) && $last_path == $request->__uri) { return null; }

		if(is_null($last_path)) {
			$last_path = $request->__uri;
		}
		Utils::addToSessionStore('last_path', $request->__uri);
		return array(
				'uri' => $request->__uri,
				'path' => __ROUTER_PATH,
				'tson' => time(),
				'agnt' => $request->getUserAgent(),
				'refr' => $request->__referer,
				'ajax' => $request->is_xmlHttpRequest,
				'srvr' => array(''),
				'rqst' => array(
						'mthd' => $request->__method,
						'ip' => $request->getRemoteAddress(),
						'port' => $request->getRemotePort(),
						'pb' => $request->get__PB()
				),
				'by' => $user);
	}

	public static function filter_skippable_path($path) {
		foreach (self::$_skippingRegex as $pathPattern) {
			if(preg_match($pathPattern, $path,$matches) == 1) {
				return true;
			}
		}
		return false;
	}

	public static function filter_skippable_agents($agent) {
		foreach (self::$_skippingAgents as $pathPattern) {
			if(preg_match($pathPattern, $agent,$matches) == 1) {

				return true;
			}
		}
		return false;
	}

	public static function set_analytic_cookie(Request &$request,$user) {
		$cookie = Cipher::encrypt(implode('|', array($user, time(), $request->getUserAgent())));
		setcookie('__ac',$cookie,time() + 3600*24*7,'/',app_domain);
		return $cookie;

	}

	public static function get_user() {
		if(isset($_SESSION['session.owner']) && $_SESSION['session.owner']['username']) {
			$user = $_SESSION['session.owner']['username'];
		}
		else {
			$user = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];
		}
		return $user;
	}

	public static function aggregate_logs_per_hour($start) {
		$end = $start + 3600;
		$ds_request = Ds::connect(ds_requests);
		$ds_stat = Ds::connect(ds_stat_counters);
		$doc_id = $start.'-'.$end;
		$hour_doc = array(
						'browser' => array(),
						'country' => array(),
						'total' => 0,
						'urls' => array());
		$requests = $ds_request->find(array('$and' => array(
												array('tson' => array('$gte' => $start)),
												array('tson' => array('$lt' => $end))
											)));
		$hour_doc_prepped = Statistics::get_hour_doc_prepared($requests);
		$storable = array_merge($hour_doc, $hour_doc_prepped);
		try {
			$ds_stat->update(array('_id' => $doc_id),array('$set' => $storable), array('w' => 1,'upsert' => true));
		}
		catch (Exception $e) {

		}

	}
}
?>