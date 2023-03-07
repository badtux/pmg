<?php
namespace Core;

class Log {
    public static function write() {
    	$argumentCount = func_num_args();

        if($argumentCount == 2) {
            $caller = (string)func_get_arg(0); $message = func_get_arg(1);
	    }
        else {
            $caller = __CLASS__; $message = func_get_arg(0);
	    }

	    if(isset($_SESSION['session.owner']) && $_SESSION['session.owner']['username']) {
	    	$user = $_SESSION['session.owner']['username'];
	    }
	    else {
	    	$remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
            $user = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $remoteAddress;
	    }

		openlog('PMG-' . strtoupper(app_name), 0, LOG_USER);
		syslog(LOG_WARNING, $user.' '.self::formatOutPut($message));
    }

    private static function formatOutPut($message) {
    	if(is_string($message) || is_numeric($message)) { return $message; }
    	ob_start();
    	if(is_object($message) || is_array($message)) { var_dump($message); }
    	return "\n" . (string)ob_get_clean();
    }
	/**
	 *
	 * @param Request $request
	 */
    public static function logRequest($request) {
		$ds = Ds::connect(ds_requests);
		$loggable = $request->getLoggableRequest();

		if(!is_null($loggable)) {
			$ds->insertOne($loggable);
		}
    }
}
?>