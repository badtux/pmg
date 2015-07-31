<?php
namespace Core;

class Qcurl {
	private static $options = array(
	    CURLOPT_URL => '',
	    CURLOPT_HEADER => 0,
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_POSTFIELDS => '',
	    CURLOPT_POST => false,
	    CURLOPT_SSL_VERIFYPEER => false
	);

	public static function query($url,$postData=false) {
		if(is_array($postData)) {
			self::$options[CURLOPT_POSTFIELDS] = $postData;
			self::$options[CURLOPT_POST] = true;
		}
		self::$options[CURLOPT_URL] = $url;

	    $handle = curl_init();
        if(is_resource($handle)) {
        	curl_setopt_array($handle, self::$options);
            return curl_exec($handle);
        }
        return false;
	}
}
?>