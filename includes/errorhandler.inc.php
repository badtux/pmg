<?php
	function sb_ErrorHandler($errorNo, $errorString, $errorInFile, $errorOnLine) {
		$errorCodes = array(1=>'E_ERROR',2=>'E_WARNING',4=>'E_PARSE',8=>'E_NOTICE',16=>'E_CORE_ERROR',32=>'E_CORE_WARNING',
                                64=>'E_COMPILE_ERROR',128=>'E_COMPILE_WARNING',256=>'E_USER_ERROR',512=>'E_USER_WARNING',1024=>'E_USER_NOTICE',
                                2048=>'E_STRICT',4096=>'E_RECOVERABLE_ERROR',8192=>'E_DEPRECATED',16384=>'E_USER_DEPRECATED',30719=>'E_ALL');

		if($errorNo != E_NOTICE) {
			if('preg_match()' != substr($errorString,0,12)) {
                Log::write($errorCodes[$errorNo] . ': ' . $errorString . "\n" . 'on line ' . $errorOnLine . ' of ' . $errorInFile . "\n" . sb_parse_backtrace(__FUNCTION__,debug_backtrace(),true));
			}
		}

	    return true;
	}

	function sb_parse_backtrace($handlerFunctionName, $raw,$mode=false) {
    	$i = 0; $output = '<div style="background-color:azure;padding:5px;margin:5px 20px 0px;color:darkcyan"><ul class="">';
    	if($mode) { $output = ''; }
	    foreach($raw as $entry) {
	    	if(($entry['function'] != $handlerFunctionName) && ($entry['function'] != 'trigger_error')) {
	    		$i++;
	    	    if($mode) {
	    	    	$output .= "\t" . @$entry['file'] . ' called function: ' . $entry['function'] . ' on line: ' . @$entry['line'] . "\n";
	    	    }
	    	    else {
	    	    	$output .= '<li>' . $entry['file'] . " called function: <strong>" . $entry['function'] . "</strong> on line: <strong>" . $entry['line'] . '</strong></li>';
	    	    }
	    	}
    	}

    	if($i > 0) {
    		if($mode) { return $output; }
        	return $output .= '</div></ul>';
    	}
    	return '';
	}

	set_error_handler('sb_ErrorHandler');
?>