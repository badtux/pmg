<?php
	//ini_set('session.save_handler', 'memcache');
	//ini_set('session.save_path',  'tcp://'.cache_memcache_server .':'. cache_memcache_port.'?persistent=1&amp;weight=1&amp;timeout=1&amp;retry_interval=15');
	
	// session_set_save_handler(
	// 					array('Core\Session','open'),
	// 					array('Core\Session','close'),
	// 					array('Core\Session','read'),
	// 					array('Core\Session','write'),
	// 					array('Core\Session','destroy'),
	// 					array('Core\Session','gc')
	// 					);
	
	$sessionHandler = new Core\Session();

	session_set_save_handler($sessionHandler, true);

	//get the token
	$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null;

	if(!is_null($token)) {
		$sessionId = Auth_Session::getSessionIdByToken($token);
		if($sessionId) { session_id($sessionId); }
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