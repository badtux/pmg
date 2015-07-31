<?php
	class Lib_Image {
		const IMAGE_TO_THUMB = 't';
		const IMAGE_DO_CROP  = 'c';

		private $fileHandle = null;
		private $type = null;
		private $size = null;
		private $tmpName = null;
		private $height = null;
		private $width = null;
		private $ufid = null;
		private $path = null;

		private $image = null;

		public static function tweak($image, $type=false){
			if($type && $type == self::IMAGE_DO_CROP && func_num_args() == 4){
				$image = implode('_', array(self::IMAGE_DO_CROP,$image,implode('|', func_get_arg(2)).'|'.implode('|',func_get_arg(3	))));
			}

			if($type && $type == self::IMAGE_TO_THUMB && func_num_args() == 3){
				$image = implode('_', array(self::IMAGE_TO_THUMB,$image,func_get_arg(2).'x'.func_get_arg(2)));
			}

			return $image.'.jpg';
		}

		public function __construct($uploadedFile=null, $maxFileSize=false) {
			if($maxFileSize !== false && $uploadedFile->size > 8388608) {
				throw new Exception('Image is too large. Max is 8 Mb', 1001);
			}

			if($uploadedFile instanceof stdClass) {
				$this->type = $uploadedFile->type;
				$this->tmpName = $uploadedFile->tmp_name;
				$this->size = $uploadedFile->size;

				if (($this->fileHandle = fopen($this->tmpName, 'rb'))){
					if((($this->image = new Imagick()) instanceof Imagick) && is_resource($this->fileHandle)) {
						rewind($this->fileHandle);
						if($this->image->readImageFile($this->fileHandle)) {
							$this->image->setImageFormat('jpeg');
							$this->image->setInterlaceScheme(Imagick::INTERLACE_PLANE);
	   						//$this->image->gaussianBlurImage(0.05,1);
	   				//$this->image->setImageResolution(150,150);
	   						//$this->image->resampleImage(150,150,imagick::FILTER_UNDEFINED,1);
							$this->image->stripImage();
							$this->image->setImageCompression(imagick::COMPRESSION_JPEG);
	   						$this->image->setImageCompressionQuality(100);
	   						$this->saveOriginal();
						}
					}
					else {
						Log::write('could not open the image or imagick not installed');
						throw new Exception('Service is not available',1001);
					}
				}
				else {
					Log::write('could not open the image');
					throw new Exception('Please select a photo',1001);
				}
			}
			else if(func_num_args() == 1 && is_string(func_get_arg(0))) {
				Log::write(app_upload_path.'og'.DS.'o-'.func_get_arg(0).'.jpg');

				if(is_file(app_upload_path.'og'.DS.'o-'.func_get_arg(0).'.jpg')) {
					$this->ufid = func_get_arg(0);
					Log::write(__METHOD__ . ' has file');
					if((($this->image = new Imagick()) instanceof Imagick) && ($this->fileHandle = fopen(app_upload_path.'og'.DS.'o-'.func_get_arg(0).'.jpg', 'r+b')) && is_resource($this->fileHandle)) {
						rewind($this->fileHandle);
						if($this->image->readImageFile($this->fileHandle)) {
							if($this->image instanceof Imagick) {
								Log::write('yep');
							}
							Log::write('read the file');
						}
					}
				}
				else {
					Log::write(__METHOD__ . ' no has file');
				}
			}
		}

		public function getGeometry() {
			return $this->image->getImageGeometry();
		}

		public function getPath() {
			return '/'.ltrim($this->path,'/');
		}

		public function getWidth(){
			return $this->width;
		}

		public function getHeight(){
			return $this->height;
		}

		public function getUFID($prefix=null) {
			if(!is_null($prefix)) {
				return $prefix.'-'.$this->ufid;
			}
			return $this->ufid;
		}

		private function isDirWritable($dirPath){
			if(is_writable($dirPath) && is_dir($dirPath)) {
				Log::write('can write `'.$dirPath.'`');
				return true;
			}
			else {
				Log::write('can not write `'.$dirPath.'`');
				throw new Exception('Service is not available', 1001);
			}
		}

		private function saveOriginal() {
			if($this->isDirWritable(app_upload_path.'og'.DS)) {
				$this->ufid = $this->getUniqueFileId();
				return $this->image->writeImage(app_upload_path.'og'.DS.$this->getUFID('o') . '.jpg');
			}
		}

		public function saveIn($dataSource, $throwException = true) {
			$this->path = DS.$dataSource->bucket.DS.$this->ufid . '.jpg';
			if($this->isDirWritable($dataSource->ds.$dataSource->bucket.DS) && $this->image->writeImage(rtrim($dataSource->ds,'/').$this->path)) {
				if($throwException) {
					throw new Exception('success', 5001);
				}
				else {
					return true;
				}
			}
		}

		public function fitIn($width,$height) {
			$d = $this->image->getImageGeometry();
			$o_w = $d['width']; $o_h = $d['height'];

			if($o_w < $width || $o_h < $height) {
				throw new Exception('Image is too small! It must be at least '.$width.' pixels wide and '.$height.' pixels tall.',1001);
			}
			else {
				$this->height = $height; $this->width = $width;
				if((($width/$o_w)*$o_h) <= $height) {
					if((($height/$o_h)*$o_w) >= $width) {
						$this->image->scaleImage((($height/$o_h)*$o_w),0);
					}
					else {
						throw new Exception('Image is too small! It must be at least '.$width.' pixels wide and '.$height.' pixels tall.',1001);
					}
				}
				else {
					if((($width/$o_w)*$o_h) > $height) {
						$this->image->scaleImage(0,(($width/$o_w)*$o_h));
					}
					else {
						throw new Exception('Image is too small! It must be at least '.$width.' pixels wide and '.$height.' pixels tall.',1001);
					}
				}

	    		return $this;
			}
		}

		public function scale($width, $height=0) {
			$this->image->scaleImage($width,$height);
			return $this;
		}

		public function crop($cropData,$scaleDownTo, $cropWidth) {
			$oGeo = $this->getGeometry();
			Log::write(json_encode($this->getGeometry()));
			Log::write(json_encode($scaleDownTo));
			Log::write(json_encode($cropData));

			$ratio = ($oGeo['width']/$cropWidth);

			Log::write($oGeo['width']/$cropWidth);
			Log::write(json_encode(array(
				'nx' => ($cropData['x']*$ratio),
				'ny' => ($cropData['y']*$ratio),
				'nh' => ($cropData['h']*$ratio),
				'nw' => ($cropData['w']*$ratio)
			)));

			$this->image->cropImage(($cropData['w']*$ratio),($cropData['h']*$ratio),($cropData['x']*$ratio),($cropData['y']*$ratio));
			return $this;
		}

		private function getUniqueFileId() {
			return (dechex(rand(1,9999)) . '-' . rand(10000,99999) . '-' . dechex(rand(1,9999)) . '-' . dechex(time()));
		}
	}
?>