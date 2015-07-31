<?php
namespace Core;
class Cipher {
	const CIPHER = MCRYPT_RIJNDAEL_256;
	const KEY = '181E27535DEE6BF8225A046D935779540AD65C2980057E57DD0693EAA';
	const MODE = MCRYPT_MODE_CBC;

	public static function encrypt($text,$key=null,$cipher=null,$mode=null) {
		$cipher = !is_null($cipher) ? $cipher : self::CIPHER;
		$key = !is_null($key) ? $key : pack('H*', self::KEY	);
		$mode = !is_null($mode) ? $mode : self::MODE;

		if(function_exists('mcrypt_encrypt')) {
			$ivSize = mcrypt_get_iv_size($cipher, $mode);
			$iv = mcrypt_create_iv($ivSize,MCRYPT_RAND);
			$cipherText = mcrypt_encrypt($cipher, $key, $text, $mode,$iv);
			$cipherText = $iv.$cipherText;
			return base64_encode($cipherText);
		}
	}

	public static function decrypt($text,$key=null,$cipher=null,$mode=null) {
		$cipher = !is_null($cipher) ? $cipher : self::CIPHER;
		$key = !is_null($key) ? $key : pack('H*', self::KEY	);
		$mode = !is_null($mode) ? $mode : self::MODE;

		if(function_exists('mcrypt_decrypt')) {
			$text_baseDecoded = base64_decode($text);
			$ivSize = self::getIvSize($cipher, $mode);
			$iv_decrypt = substr($text_baseDecoded, 0,$ivSize);
			$decryptable = substr($text_baseDecoded,$ivSize);
			$decrypted = mcrypt_decrypt($cipher,$key,$decryptable,$mode,$iv_decrypt);
			return $decrypted;
		}
	}

	public static function getIvSize($cipher, $mode) {
		return mcrypt_get_iv_size($cipher, $mode);
	}
}
?>