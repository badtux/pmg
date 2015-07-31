<?php
class image2 {
	private $path = '';
	private $name = '';
	private $extension = '';

	private $srcImg = array('h'=>'', 'w'=>'', 'resoc'=>'','filename'=>'','path'=>'','extension'=>'');
	private $prcImg = array('h'=>'', 'w'=>'', 'resoc'=>'');
	private $dstImg = array('resoc'=>'');

	public function __construct($uploadId) {
		$this->db = Db::singleton();
		$this->load($uploadId);
	}

	public function getWidth() {
		return $this->srcImg['w'];
	}

	public function getHeight() {
		return $this->srcImg['h'];
	}

	private function load($uploadId) {
		$this->db->prepare("SELECT * FROM uploads WHERE id = @uploadid");
		$this->db->bindData(array('uploadid'=>$uploadId));
		$row = $this->db->fetchArray($this->db->execute());
		$this->srcImg['filename'] = $row['filename'];
		$this->srcImg['path'] = app_upload_path . $row['bucket'];
		$this->srcImg['extension'] = $row['extension'];
		list($this->srcImg['w'], $this->srcImg['h']) = getimagesize($this->srcImg['path'] . $this->srcImg['filename'] . '.' . $this->srcImg['extension']);
		$this->srcImg['resoc'] = imagecreatefromjpeg($this->srcImg['path'] . $this->srcImg['filename'] . '.' . $this->srcImg['extension']);
		return TRUE;
	}

	public function crop($x,$y,$w,$h) {
		$imageHandle = imagecreatetruecolor($w, $h);
		if(!is_resource($this->prcImg['resoc'])) { $this->prcImg = $this->srcImg; $this->prcImg['w'] = $w; $this->prcImg['h'] = $h; }
		imagecopyresampled($imageHandle,$this->prcImg['resoc'],0,0,$x,$y,$w,$h,$w,$h);
		$this->prcImg['resoc'] = $imageHandle;
	}

	public function resize($w,$h) {
		$imageHandle = imagecreatetruecolor($w, $h);
		if(!is_resource($this->prcImg['resoc'])) { $this->prcImg = $this->srcImg;  }
		imagecopyresampled($imageHandle,$this->prcImg['resoc'],0,0,0,0,$w,$h,$this->prcImg['w'],$this->prcImg['h']);
		$this->prcImg['resoc'] = $imageHandle;
	}

	function scale($maxW,$maxH, $fitType=FALSE) {
		if(!is_resource($this->prcImg['resoc'])) { $this->prcImg = $this->srcImg; }
		$w = $this->prcImg['w'];
		$h = $this->prcImg['h'];

		$xRatio = $maxW / $w;
		$yRatio = $maxH / $h;

		if(($w <= $maxW) && ($h <= $maxH) ){
		    $newW = $maxW; $newH = $maxH;
		}
		else if(($xRatio * $h) < $maxH) {
			$newH = ceil($xRatio * $h);
		    $newW = $maxW;
		}
		else {
			$newW = ceil($yRatio * $w);
		    $newH = $maxH;
		}

		if($fitType == 'BOTH') { $newW = $maxW; $newH = $maxH; }
		$imageHandle = imagecreatetruecolor($newW, $newH);
		imagecopyresampled($imageHandle,$this->prcImg['resoc'],0,0,0,0,$newW,$newH,$this->prcImg['w'],$this->prcImg['h']);
		$this->prcImg['resoc'] = $imageHandle;
	}

	public function save($dstImgPath='', $dstImgPrefix = '') {
		if($dstImgPath == '') { $dstImgPath = $this->srcImg['path']; }
		imagejpeg($this->prcImg['resoc'], $dstImgPath . $this->srcImg['filename'] . $dstImgPrefix . '.' . $this->srcImg['extension'], 100);
		$this->prcImg = array('h'=>'', 'w'=>'', 'resoc'=>'');
		return $this;
	}
}
?>