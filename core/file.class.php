<?php
namespace Core;
class File{
	private $_name;
	private $_tmpName;
	private $_size;
	private $_type;
	private $_id;
	private $_ext;

	public static $acceptedMimes = array('file' => array('pdf' => array('preg'=>'application\/pdf','mime'=>'application/pdf'),
														'docx' => array('preg'=>'application\/vnd.openxmlformats-officedocument','mime'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
														'doc' => array('preg'=>'application\/msword','mime'=>'application/msword')));

	public function __construct($fileData=null) {
		$data = !is_object($fileData) ? (object)$fileData : $fileData;
		$this->_name = $fileData->name;
		$this->_size = $fileData->size;
		$this->_tmpName = $fileData->tmp_name;
		$this->_type = $fileData->type;
	}

	public function saveIn($bucket) {
		$this->_id = $this->getUniqueFileId();
		$fileName = $this->_id.'.'.$this->_ext;
		if(is_dir($bucket) && is_writable($bucket)) {
			if(copy($this->_tmpName, $bucket.DS.$fileName)) {
				return true;
			}
		}
		else {
			throw new Exception('Bucket is not writable.');
		}
	}

	public function getFileId() {
		return $this->_id;
	}

	public function getFileName() {
		return $this->_name;
	}
	public function getNewFileName() {
		return $this->_id.'.'.$this->_ext;
	}

	public function setExtension($ext) {
		$this->_ext = $ext;
		return $this;
	}

	public static  function getMIME($portion,$type) {
		return isset(self::$acceptedMimes[$portion][$type]['mime']) ? self::$acceptedMimes[$portion][$type]['mime'] : false;

	}
	public static function validateMime($portion,$subject) {
		foreach (self::$acceptedMimes[$portion] as $type=>$format) {
			if(preg_match('/'.$format['preg'].'/', $subject)) {
				return $type;
			}
		}
		throw new Exception('Not a valid file type. We accept only '.implode(',',array_keys(self::$acceptedMimes[$portion])).' types.');
	}

	private function getUniqueFileId() {
		return (dechex(rand(1,9999)) . '-' . rand(10000,99999) . '-' . dechex(rand(1,9999)) . '-' . dechex(time()));
	}
}