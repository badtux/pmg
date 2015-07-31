<?php
class image {
	private $path = '';
	private $name = '';
	private $extension = '';
	private $destinationImageResoc;
	private $sourceImageResoc;
	private $srcImageW;
	private $srcImageH;
	private $uploadId = FALSE;

	public function __construct($uploadId) {
		parent::__construct ();
		$this->uploadId = $uploadId;
		$this->_load();
	}

	public function getWidth() {
		return $this->srcImageW;
	}

	public function getHeight() {
		return $this->srcImageH;
	}

	private function _load() {
		$this->db->prepare("SELECT * FROM uploads WHERE id = @uploadid");
		$this->db->bindData(array('uploadid'=>$this->uploadId));
		$row = $this->db->fetchArray($this->db->execute());
		$this->name = $row['filename'];
		$this->path = app_upload_path . $row['bucket'];
		$this->extension = $row['extension'];
		list($this->srcImageW, $this->srcImageH) = getimagesize($this->path . $this->name . '.' . $this->extension);
	}

	public function crop($x,$y,$w,$h){
		$this->sourceImageResoc = imagecreatefromjpeg($this->path . $this->name . '.' . $this->extension);
		$this->destinationImageResoc = ImageCreateTrueColor($w, $h);
		ImageCopyResampled($this->destinationImageResoc, $this->sourceImageResoc, 0, 0, $x, $y, $w, $h, $w,$h);

		return $this;
	}

	public function resizeLast($width, $height) {
		if (preg_match('/jpg|jpeg/', $this->extension)) {
			$this->sourceImageResoc = $this->destinationImageResoc;
			$sourceWidth  = imagesx($this->destinationImageResoc);
			$sourceHeight = imageSY($this->destinationImageResoc);

			$this->destinationImageResoc = imagecreatetruecolor($width, $height);
			imagecopyresampled($this->destinationImageResoc, $this->sourceImageResoc ,0,0,0,0, $width, $height, $sourceWidth, $sourceHeight);
		}
		return $this;
	}

	public function resize($width, $height) {
		if (preg_match('/jpg|jpeg/', $this->extension)) {
			$this->sourceImageResoc = imagecreatefromjpeg($this->path . $this->name . '.' . $this->extension);
			$sourceWidth  = imagesx($this->sourceImageResoc);
			$sourceHeight = imageSY($this->sourceImageResoc);

			if ($sourceWidth > $sourceHeight) {
				$newWidth  = $width;
				$newHeight = $sourceHeight * ($height / $sourceWidth);
			}

			if ($sourceWidth < $sourceHeight) {
				$newWidth  = $sourceWidth * ($width / $sourceHeight);
				$newHeight = $height;
			}

			if ($sourceWidth == $sourceHeight) {
				$newWidth  = $width;
				$newHeight = $height;
			}

			$this->destinationImageResoc = imagecreatetruecolor($width, $height);
			imagecopyresampled($this->destinationImageResoc, $this->sourceImageResoc ,0,0,0,0, $width, $height, $sourceWidth, $sourceHeight);
		}
		return $this;
	}

	public function save($destinationImagePath='', $destinationImagePrefix = '') {
		if($destinationImagePath == '') { $destinationImagePath = $this->path; }
		imagejpeg($this->destinationImageResoc, $destinationImagePath . $this->name . $destinationImagePrefix . '.' . $this->extension, 100);
		return $this;
	}
}
?>