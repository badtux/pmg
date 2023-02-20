<?php
namespace Core;
class Session {

	/**
	 *
	 * encryption algorithm
	 * @var const CIPHER
	 */
	const CIPHER = MCRYPT_RIJNDAEL_256;

	/**
	 *
	 * encryption mode
	 * @var const CIPHER_MODE
	 */
	const CIPHER_MODE = MCRYPT_MODE_CBC;

	/**
	 *
	 * temp memcache server host
	 * @var const MEMCACHE_SINGLE_TEMP_SERVER
	 */
	const MEMCACHE_SINGLE_TEMP_SERVER = cache_memcache_server;

	/**
	 *
	 * temp memcache server port
	 * @var const MEMCACHE_SINGLE_TEMP_PORT
	 */
	const MEMCACHE_SINGLE_TEMP_PORT = cache_memcache_port;

	/**
	 *
	 * memcache object
	 * @var Memcache $_db
	 */
	private static $_db;

	/**
	 *
	 * keyname of the cipher
	 * @var string $_keyName
	 */
	private static $_keyName;

	/**
	 *
	 * initial value size of the cipher
	 * @var int $_ivSize
	 */
	private static $_ivSize;

	/**
	 *
	 * key of the cipher
	 * @var string $_key
	 */
	private static $_key ;

	/**
	 *
	 * open the session
	 * @param string $save_path
	 * @param string $session_name
	 */
	public static function open($save_path, $session_name) {
		self::$_db = self::getMemcache();
		self::$_ivSize = mcrypt_get_iv_size(self::CIPHER, self::CIPHER_MODE);
		self::$_keyName = 'y'.$session_name;

		if (empty($_COOKIE[self::$_keyName])) {
			$keyLength = mcrypt_get_key_size(self::CIPHER, self::CIPHER_MODE);
			self::$_key = self::_randKey($keyLength);
			$cookie_param = session_get_cookie_params();
			setcookie(
			         self::$_keyName,
			         base64_encode(self::$_key),
			         $cookie_param['lifetime'],
			         $cookie_param['path'],
			         $cookie_param['domain'],
			         $cookie_param['secure'],
			         $cookie_param['httponly']
			    );
		}
		else {
			self::$_key = base64_decode($_COOKIE[self::$_keyName]);
		}

		return true;
	}

	/**
	 *
	 * read the session
	 * @param string $id the session id
	 */
	public static function read($id) {
		$sess_id = 'sess_' . $id;
		self::_setExpire($sess_id);
		$data = self::$_db->get($sess_id);

		if (empty($data)) {
			return false;
		}
		$iv = substr($data, 0, self::$_ivSize);
		$encrypted = substr($data, self::$_ivSize);
		$decrypt = mcrypt_decrypt(self::CIPHER, self::$_key, $encrypted,self::CIPHER_MODE, $iv);
		$d = rtrim($decrypt, "�");
		return $d;
	}

	/**
	 *
	 * close the session
	 * trigger on the session_write_close
	 */
	public static function close() {
		self::$_db->close();
		return true;
	}

	/**
	 *
	 * write the session data to memcache
	 * @param string $id id of the session
	 * @param string $session_data session data
	 */
	public static function write($id, $session_data) {
		$sess_id = 'sess_' . $id;

		if (self::$_db->get($sess_id . '_expire')) {
			self::$_db->replace($sess_id . '_expire', time(), 0);
		}
		else {
			self::$_db->set($sess_id . '_expire', time(), 0);
		}
		$iv = mcrypt_create_iv(self::$_ivSize, MCRYPT_RAND);

		if ($session_data) {
			$encrypted = mcrypt_encrypt(self::CIPHER, self::$_key,$session_data, self::CIPHER_MODE, $iv);
			if (self::$_db->get($sess_id)) {
				self::$_db->replace($sess_id, $iv . $encrypted);
			}
			else {
				self::$_db->set($sess_id, $iv . $encrypted);
			}

			return true;
		}
	}

	/**
	 *
	 * destroy the session. trigger on session_destroy
	 * @param string $id
	 */
	public static function destroy($id) {
		$ds = Ds::connect(ds_token);
		$sessId = "sess_" . $id;
		$ds->remove(array('snid' => $id));
		self::$_db->delete($sessId . "_expire");
		return self::$_db->delete($sessId);
	}

	/**
	 *
	 * garbage collector. not implemented as that stuff to memcache
	 * @param int $maxLifeTime
	 */
	public static function gc($maxLifeTime) {
		return true;
	}

	/**
	 *
	 * generate a random key for the cipher
	 * @param int $keyLength
	 */
	private static function _randKey($keyLength = 32) {

		if (function_exists('openssl_random_pseudo_bytes')) {
			$rnd = openssl_random_pseudo_bytes($keyLength, $strong);

			if ($strong == true) {
				return $rnd;
			}
		}

		for ($i = 0; $i < $keyLength; $i++) {
			$s = sha1(mt_rand());
			$c = mt_rand(0, 30);
			$rnd .= chr(hexdec($s[$c] . $s[$c + 1]));
		}

		return $rnd;
	}

	/**
	 * set expire the memcache object
	 * @param string $key
	 */
	private static function _setExpire($key) {
		$lifeTime = ini_get("session.gc_maxlifetime");
		$expire = self::$_db->get($key . "_expire");

		if ($expire + $lifeTime < time()) {
			self::$_db->delete($key);
			self::$_db->delete($key . "_expire");
		}
		else {
			self::$_db->replace($key . "_expire", time());
		}
	}

	public static function loadSession($sessId) {
		$memcache = self::getMemcache();
		$data = $memcache->get('sess_'.$sessId);

		if (empty($data)) {
			return false;
		}
		$iv = substr($data, 0, self::$_ivSize);
		$encrypted = substr($data, self::$_ivSize);
		$decrypt = mcrypt_decrypt(self::CIPHER, self::$_key, $encrypted,self::CIPHER_MODE, $iv);
		$d = rtrim($decrypt, "�");
		return $d;
	}

	public static function getSessionByToken($token) {
		$sessionId = self::mapToken($token);
		$session = self::loadSession($sessionId);
		return unserialize($session)  ;
	}

	public static function mapToken($token) {
		$memcache = self::getMemcache();
		$sessionId = $memcache->get($token);
		return $sessionId ? $sessionId : false;
	}	
	
	public static function getMemcache() {
		$memcache = new \Memcache();
		$memcache->addServer(cache_memcache_server,cache_memcache_port,0,2,2,10);
		return $memcache;
	}
	
}
?>