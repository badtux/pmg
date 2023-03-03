<?php
namespace Core;
class Session implements \SessionHandlerInterface {

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
	private $_db;

	/**
	 *
	 * keyname of the cipher
	 * @var string $_keyName
	 */
	private $_keyName;

	/**
	 *
	 * initial value size of the cipher
	 * @var int $_ivSize
	 */
	private $_ivSize;

	/**
	 *
	 * key of the cipher
	 * @var string $_key
	 */
	private $_key ;

	/**
	 *
	 * open the session
	 * @param string $save_path
	 * @param string $session_name
	 */
	public function open($save_path, $session_name):bool {
		$this->_db = $this->getMemcache();
		$this->_ivSize = mcrypt_get_iv_size(Session::CIPHER, Session::CIPHER_MODE);
		self::$_keyName = 'y'.$session_name;

		if (empty($_COOKIE[$this->_keyName])) {
			$keyLength = mcrypt_get_key_size(Session::CIPHER, Session::CIPHER_MODE);
			$this->_key = $this->_randKey($keyLength);
			$cookie_param = session_get_cookie_params();
			setcookie(
			         $this->_keyName,
			         base64_encode($this->_key),
			         $cookie_param['lifetime'],
			         $cookie_param['path'],
			         $cookie_param['domain'],
			         $cookie_param['secure'],
			         $cookie_param['httponly']
			    );
		}
		else {
			$this->_key = base64_decode($_COOKIE[$this->_keyName]);
		}

		return true;
	}

	/**
	 *
	 * read the session
	 * @param string $id the session id
	 */
	public function read($id) {
		$sess_id = 'sess_' . $id;
		self::_setExpire($sess_id);
		$data = self::$_db->get($sess_id);

		if (empty($data)) {
			return false;
		}
		$iv = substr($data, 0, $this->_ivSize);
		$encrypted = substr($data, $this->_ivSize);
		$decrypt = mcrypt_decrypt(Session::CIPHER, $this->_key, $encrypted, Session::CIPHER_MODE, $iv);
		$d = rtrim($decrypt, "�");

		return (string)$d;
	}

	/**
	 *
	 * close the session
	 * trigger on the session_write_close
	 */
	public function close():bool {
		return $this->_db->close();
	}

	/**
	 *
	 * write the session data to memcache
	 * @param string $id id of the session
	 * @param string $session_data session data
	 */
	public function write($id, $session_data):bool {
		$sess_id = 'sess_' . $id;

		if ($this->_db->get($sess_id . '_expire')) {
			$this->_db->replace($sess_id . '_expire', time(), 0);
		}
		else {
			$this->_db->set($sess_id . '_expire', time(), 0);
		}
		$iv = mcrypt_create_iv($this->_ivSize, MCRYPT_RAND);

		if ($session_data) {
			$encrypted = mcrypt_encrypt(Session::CIPHER, $this->_key, $session_data, Session::CIPHER_MODE, $iv);
			if ($this->_db->get($sess_id)) {
				$this->_db->replace($sess_id, $iv . $encrypted);
			}
			else {
				$this->_db->set($sess_id, $iv . $encrypted);
			}

			return true;
		}

		return false;
	}

	/**
	 *
	 * destroy the session. trigger on session_destroy
	 * @param string $id
	 */
	public function destroy($id):bool {
		$ds = Ds::connect(ds_token);
		$sessId = "sess_" . $id;
		$ds->remove(array('snid' => $id));
		$this->_db->delete($sessId . "_expire");
		return $this->_db->delete($sessId);
	}

	/**
	 *
	 * garbage collector. not implemented as that stuff to memcache
	 * @param int $maxLifeTime
	 */
	public function gc($maxLifeTime) {
		return true;
	}

	/**
	 *
	 * generate a random key for the cipher
	 * @param int $keyLength
	 */
	private function _randKey($keyLength = 32) {

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
	private function _setExpire($key) {
		$lifeTime = ini_get("session.gc_maxlifetime");
		$expire = $this->_db->get($key . "_expire");

		if ($expire + $lifeTime < time()) {
			$this->_db->delete($key);
			$this->_db->delete($key . "_expire");
		}
		else {
			$this->_db->replace($key . "_expire", time());
		}
	}

	public function loadSession($sessId) {
		$memcache = $this->getMemcache();
		$data = $memcache->get('sess_'.$sessId);

		if (empty($data)) {
			return false;
		}
		$iv = substr($data, 0, $this->_ivSize);
		$encrypted = substr($data, $this->_ivSize);
		$decrypt = mcrypt_decrypt(Session::CIPHER, $this->_key, $encrypted, Session::CIPHER_MODE, $iv);
		$d = rtrim($decrypt, "�");
		return $d;
	}

	public function getSessionByToken($token) {
		$sessionId = $this->mapToken($token);
		$session = $this->loadSession($sessionId);
		return unserialize($session)  ;
	}

	public function mapToken($token) {
		$memcache = $this->getMemcache();
		$sessionId = $memcache->get($token);
		return $sessionId ? $sessionId : false;
	}	
	
	public function getMemcache() {
		$memcache = new \Memcache();
		$memcache->addServer(cache_memcache_server,cache_memcache_port,0,2,2,10);
		return $memcache;
	}
	
}
?>