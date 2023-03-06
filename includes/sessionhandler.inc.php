<?php
	//ini_set('session.save_handler', 'memcache');
	//ini_set('session.save_path',  'tcp://'.cache_memcache_server .':'. cache_memcache_port.'?persistent=1&amp;weight=1&amp;timeout=1&amp;retry_interval=15');
	// session_set_save_handler(
	// 	['Core\Session','open'],
	// 	['Core\Session','close'],
	// 	['Core\Session','read'],
	// 	['Core\Session','write'],
	// 	['Core\Session','destroy'],
	// 	['Core\Session','gc']
	// );

	// Set session save handler
	session_set_save_handler(
		// Session open callback
		function ($save_path, $session_name) {
			return Core\Session::open($save_path, $session_name);
		},
		// Session close callback
		function () {
			return Core\Session::close();
		},
		// Session read callback
		function ($session_id) {
			return Core\Session::read($session_id);
		},
		// Session write callback
		function ($session_id, $session_data) {
			return Core\Session::write($session_id, $session_data);
		},
		// Session destroy callback
		function ($session_id) {
			return Core\Session::destroy($session_id);
		},
		// Session garbage collection callback
		function ($maxlifetime) {
			return Core\Session::gc($maxlifetime);
		}
	);

	//get the token
	$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null;
	if(!is_null($token)) {
		$sessionId = Auth_Session::getSessionIdByToken($token);
		if($sessionId) {
			session_id($sessionId);
		}
	}

	session_start();
	/*
	 * keeps the session id changed every 10 minutes
	* */
	if (!isset($_SESSION['session.created'])) {
		$_SESSION['session.created'] = time();
	}
	else if ((time()-$_SESSION['session.created']) > 600) {
		$ds = Core\Ds::connect(ds_token);
		$cachedId = session_id();
		session_regenerate_id(true);
		try {
			$ds->update(array('snid' => $cachedId),array('$set' => array('snid' => session_id())));
		}
		catch (Exception $e) {
			Log::write('Exception: '.$e->getCode().' '.$e->getMessage());
		}
		$_SESSION['session.created'] = time();
	}
?>