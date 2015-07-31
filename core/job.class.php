<?php

class Job {
	const STATUS_EXPIRED = 'expired';
	const STATUS_VALID = 'valid';

	private $_id = null;

	private $_expire_time = null;

	private $_ref = null;

	public function getId() {
		return $this->_id;
	}

	public function setId($id) {
		$this->_id = $id;
		return $this;
	}

	public function getExpireTime() {
		return $this->_expire_time;
	}

	public function setExpireTime($time) {
		$this->_expire_time = $time;
		return $this;
	}

	public function isExpired() {
		return ($this->_expire_time < time());
	}

	public function setReference($reference) {
		$this->_ref = $reference;
		return $this;
	}

	public function getReference() {
		return $this->_ref;
	}

	public function __toString() {
		return serialize($this);
	}

	public function run() {
		if($this->isExpired()) { throw new Exception(__METHOD__.' Expired job',self::STATUS_EXPIRED); }

		if(empty($this->_ref) || count($this->_ref) < 2) { throw new Exception(__METHOD__.' Not a valid reference'); }
		$callerClass = $this->_ref[0];
		$callerMethod = $this->_ref[1];
		$arguments = array_slice($this->_ref,2);
		call_user_func_array(array($callerClass,$callerMethod), $arguments);
	}
}
?>