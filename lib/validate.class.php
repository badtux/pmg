<?php
class Lib_Validate {
    public static function isWeburl($input) {
    	if(trim($input) != preg_match('~(https?://)?(www\.)?([a-zA-Z0-9_%]*)\b\.[a-z]{2,4}(\.[a-z]{2})?((/[a-zA-Z0-9_%]*)+)?(\.[a-z]*)?~',trim($input))) {
    		return true;
    	}
    	else {
    		return false;
    	}
    }

    public static function weburlValidity($input){
    	$scheme = parse_url($input,PHP_URL_SCHEME);
    	if (!strlen($scheme)){
    		$input = 'http://'.$input;
    	}

    	if (Lib_Validate::isWeburl($input)) {
    		return $input;
    	}
    	else {
    		return false;
    	}
    }

    public static function isUsPhone($input) {
    	if(preg_match('/[(]?\d{3}[)]?\s?-?\s?\d{3}\s?-?\s?\d{4}/', $input)) {
    		return true;
    	}
    	else {
    		return false;
    	}
    }
}
?>