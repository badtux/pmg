<?php
class Upload {
	private $fileName;
    private $fileType;
    private $fileTmpName;
    private $fileError;
    private $fileSize;
    private $fileExtension;
    private $uploadId;
    private $bucketName;
    private $bucketPath;
    private $acceptFileTypes;

	public function __construct($accept=array()) {
		$this->db = Db::singleton();
		$this->acceptFileTypes = $accept;
	}
	
	public function copy($sourceFile, $destination) {
		$this->bucketPath = app_upload_path . $destination;
		$this->__setUploadId();
		$this->bucketName  = $destination;
		if(is_file($sourceFile)) {
			if(is_readable($sourceFile)) {
				$this->fileTmpName = $sourceFile;
				$this->fileSize    = filesize($sourceFile);
				$this->fileType    = 'image/jpeg';
				if($this->__isFileAcceptable()) {
					if(copy($this->fileTmpName, $this->bucketPath . $this->uploadId . '.' . $this->fileExtension)) {
						return $this->__file();
					}
				}
			}
		}
		
		return FALSE;
	}
	
	public function save($sourceFile, $destination) {
		$this->bucketPath = app_upload_path . $destination;
		$this->__setUploadId();
		$this->bucketName  = $destination;
		
		if(is_array($sourceFile)) {
			if(is_readable($sourceFile['tmp_name'])) {
				if(is_dir($this->bucketPath) && is_readable($sourceFile['tmp_name'])) {
			        $this->fileTmpName = $sourceFile['tmp_name'];
			        $this->fileSize    = filesize($sourceFile['tmp_name']);
					if($this->__isFileAcceptable()) {
						if(move_uploaded_file($this->fileTmpName, $this->bucketPath . $this->uploadId . '.' . $this->fileExtension)){
							return $this->__file();
						}
					}
				}
			}
		}
		
		return FALSE;
	}

	private function __file() {
		$this->db->prepare("INSERT INTO uploads (bucket,filename,extension,mime) VALUES (@bucketname,@uploadid,@uploadfileext,@filetype);");
		$this->db->bindData(array('bucketname'=>$this->bucketName,'uploadid'=>$this->uploadId,'uploadfileext'=>$this->fileExtension,'filetype'=>$this->fileType));
		$this->db->execute();
		return $this->db->getLastId();
	}

	private function __setUploadId() {
		$this->uploadId = rand(1,999) . '-' . dechex(rand(1,9999)) . '-' . rand(10000,99999) . '-' . dechex(rand(1,9999));
		return TRUE;
	}
	
	public function getTempUploadId() {
		return 'TEMP-' . dechex(rand(1,9999)) . '-' . rand(10000,99999) . '-' . dechex(rand(1,9999));
	}
	
	public function getUploadId() {
		return $this->uploadId;
	}
	
	public function getFIle($uploadId) {
		$this->db->prepare("SELECT filename FROM uploads WHERE id=@uploadid");
		$this->db->bindData(array('uploadid'=>$uploadId));
		$row = $this->db->fetchArray($this->db->execute());
		return $row['filename'];
	}

	private function __isFileAcceptable() {
		$acceptable = TRUE;
		switch ($this->fileType) {
			case 'image/jpeg': 			$this->fileExtension = 'jpg'; 	break;
			case 'image/pjpeg': 		$this->fileExtension = 'jpg'; 	break;
			default:					$acceptable = FALSE;		 	break;
		}
		
		if($this->fileSize >= 5242880) { $acceptable = FALSE; }
		return $acceptable;
	}
}
?>