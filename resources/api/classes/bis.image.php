<?php
/**
 * @author SilverBiology
 * @website http://www.silverbiology.com
 */

Class Image {

	public $db, $record;

	public function __construct($db = null) {
		$this->db = $db;
		$this->lg = new LogClass($db);
	}

	public function imageSetFullPath( $file ){
		$parts = explode('/', $file);
		if ( count($parts) == 1 ) {
			$parts = explode('\\', $file);
		}
		$filename = $parts[count($parts) - 1];
		unset($parts[count($parts) - 1]);
		$path = implode('/', $parts) . "/";
		$this->imageSetProperty('filename', $filename);
		$this->imageSetProperty('path', $path);
	}

	public function imageGetName( $field = 'name' ) {
		if ($field == 'name' || $field == 'ext') {
			$path_parts = pathinfo($this->imageGetProperty('filename'));
			return($field == 'name') ? $path_parts['filename'] : $path_parts['extension'];
		} else {
			return($this->$field);
		}
	}

	/**
	 * Set the value to Data
	 * @param mixed $data : input data
	 * @return bool
	 */
	public function imageSetData($data) {
		$this->data = $data;
		return(true);
	}

	/**
	* Returns all the values in the record
	* @return mixed
	*/
	public function imageGetAllProperties() {
		if (isset($this->record)) {
			return( $this->record );
		} else {
			return(false);
		}
	}

	/**
	* Returns a since field value
	* @return mixed
	*/
	public function imageGetProperty( $field ) {
		if (isset($this->record[$field])) {
			return( $this->record[$field] );
		} else {
			return(false);
		}
	}

	/**
	* Set the value to a field
	* @return bool
	*/
	public function imageSetProperty( $field, $value ) {
		$this->record[$field] = $value;
		return(true);
	}

	public function imageLoadByBarcode( $barcode ) {
		if($barcode == '') return(false);
		$query = sprintf("SELECT * FROM `image` WHERE `barcode` = '%s'", mysql_escape_string($barcode) );
		try {
		$ret = $this->db->query_one( $query );
		} catch (Exception $e) {
			trigger_error($e->getMessage(),E_USER_ERROR);
		}
		if ($ret != NULL) {
			foreach( $ret as $field => $value ) {
				$this->imageSetProperty($field, $value);
			}
			return(true);
		} else {
			return(false);
		}
	}

	public function imageLoadById( $imageId ) {
		if($imageId == '') return false;
		$query = sprintf("SELECT * FROM `image` WHERE `imageId` = '%s'", mysql_escape_string($imageId) );
		try {
			$ret = $this->db->query_one( $query );
		} catch (Exception $e) {
			trigger_error($e->getMessage(),E_USER_ERROR);
		}
		if ($ret != NULL) {
			foreach( $ret as $field => $value ) {
				$this->imageSetProperty($field, $value);
			}
			return(true);
		} else {
			return(false);
		}
	}

	public function imageMoveToImages($storageDeviceId, $base100 = false) {
		global $config;
		$storage = new StorageDevice($this->db);
		$device = $storage->storageDeviceGet($storageDeviceId);
		
		$barcode = $this->imageGetName();
		// $tmpPath = $config['path']['images'] . $this->imageBarcodePath( $barcode );
		if ($base100) {
			$tmpPath = rtrim($device['basePath'],'/') . '/' . $this->imageBarcodePath( $barcode );
		} else {
			$tmpPath = rtrim($device['basePath'],'/') . '/' . $barcode . '/';
		}
		$this->imageMkdirRecursive( $tmpPath );
		$flsz = @filesize($this->imageGetProperty('path') . $this->imageGetProperty('filename'));
		if(!$flsz) {
			if(!@rename( $this->imageGetProperty('path') . $this->imageGetProperty('filename'), $config['path']['error'] . $this->imageGetProperty('filename') )) {
				return array('success' => false, 'code' => 140);
			}
			return array('success' => false);
		}
		if(@rename( $this->imageGetProperty('path') . $this->imageGetProperty('filename'), $tmpPath . $this->imageGetProperty('filename') )) {
			$this->imageSetProperty('path',str_replace($device['basePath'],'',$tmpPath));
			return array('success' => true);
		} else {
			return array('success' => false, 'code' => 141);
		}
	}

	public function imageBarcodePath( $barcode ) {
		$id = $barcode;
		if ((strlen($id))>8){
			$loop_flag = true;$i = 0;
			while($loop_flag){
				if(substr($barcode,$i) * 1) {
					$loop_flag = false;
				} else {
					$i++;
				}
				if($i>8) $loop_flag = false;
			}
			$this->prefix = strtolower(substr($id, 0, $i));
			$id= substr($id, $i);
		} else {
			$this->prefix="";
		}
		$destPath  = $this->prefix . "/";
		$destPath .= (int) ($id / 1000000) . "/";
		$destPath .= (int) ( ($id % 1000000) / 10000) . "/";
		$destPath .= (int) ( ($id % 10000) / 100) . "/";
		$destPath .= (int) ( $id % 100 ) . "/";
		return( $destPath );
	}

	public function imageMkdirRecursive( $pathname ) {
		is_dir(dirname($pathname)) || $this->imageMkdirRecursive(dirname($pathname));
		return is_dir($pathname) || @mkdir($pathname, 0775);
	}

	function imageRmdirRecursive($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir."/".$object) == "dir") $this->imageRmdirRecursive($dir."/".$object); else unlink($dir."/".$object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	function imageCreateThumbnail($details,$tmpPath = '',$delFlag = false) {
		global $config;
		# imageLoadById should be called prior to this call
		$storage = new StorageDevice($this->db);
		$device = $storage->storageDeviceGet($this->imageGetProperty('storageDeviceId'));
		
		$postfix = $details['postfix'];
		$type = $this->imageGetName('ext');
		$extension = '.' . $this->imageGetName('ext');
		$name = $this->imageGetName();
		$key = rtrim($this->imageGetProperty('path'),'/') . '/' . $this->imageGetProperty('filename');
		$keyThumb = rtrim($this->imageGetProperty('path'),'/') .  '/' . $name . $details['postfix'] . $extension;
		
		# Getting the source image.
		$image = ($tmpPath != '') ? $tmpPath : $storage->storageDeviceFileDownload($this->imageGetProperty('storageDeviceId'),$key);
		# Creating thumbnails.
		$dtls = @pathinfo($image);
		$imageTmp = $image;
		if($config['image_processing'] == 1) {
			// $destination =  $dtls['dirname'] . '/' . $dtls['filename'] . $details['postfix'] . $extension;
			$destination = ($device['method'] == 'reference') ? rtrim($device['basePath'],'/') . '/' . $name . '/' . $dtls['filename'] . $details['postfix'] . $extension : $dtls['dirname'] . '/' . $dtls['filename'] . $details['postfix'] . $extension;
			$tmp = sprintf("convert -limit memory 16MiB -limit map 32MiB \"%s\" -thumbnail %sx%s \"%s\"", $imageTmp,$details['width'],$details['height'],$destination);
			// $tmp = sprintf("convert -limit memory 16MiB -limit map 32MiB %s -thumbnail %sx%s %s", $imageTmp,$details['width'],$details['height'],$destination);
			$res = exec($tmp);
			$imageTmp = $destination;
			// $destination =  $dtls['dirname'] . '/' . $dtls['filename'] . $details['postfix'] . $extension;
			$destination = ($device['method'] == 'reference') ? rtrim($device['basePath'],'/') . '/' . $name . '/' . $dtls['filename'] . $details['postfix'] . $extension : $dtls['dirname'] . '/' . $dtls['filename'] . $details['postfix'] . $extension;
			$tmp = sprintf("convert \"%s\" \"%s\"",$imageTmp,$destination);
			// $tmp = sprintf("convert %s %s",$imageTmp,$destination);
			$res = exec($tmp);
		} else {
			$func = 'imagecreatefrom' . (@strtolower($dtls['extension']) == 'jpg' ? 'jpeg' : @strtolower($dtls['extension']));
			$im = @$func($imageTmp);
			if($im !== false) {
				// $destination = $dtls['dirname'] . $dtls['filename'] . $details['postfix'] . $extension;
				$destination = ($device['method'] == 'reference') ? rtrim($device['basePath'],'/') . '/' . $name . '/' . $dtls['filename'] . $details['postfix'] . $extension : $dtls['dirname'] . '/' . $dtls['filename'] . $details['postfix'] . $extension;
				$width = imageSX($im);
				$height = imageSY($im);
				// $destination = ($display_flag)?NULL:$destination;
				$this->imageResize($details['width'],$details['height'], $im, $destination, $width, $height);
				ImageDestroy($im); # Remove tmp Image Object
			}
		}
		# Setting the destination
		$storage->storageDeviceFileUpload($this->imageGetProperty('storageDeviceId'),$keyThumb,$destination);

		if($delFlag && strtolower($device['type']) == 's3') {
			#deleting the original tmp image downloaded from s3
			@unlink($image);
			return true;
		}
		return $image;
	}
	
	
/*	
	function imageCreateThumbnail( $tmp_path, $new_width, $new_height, $postfix = '', $display_flag=false ) {
		$extension = '.' . $this->imageGetName('ext');
		$func = 'imagecreatefrom' . (@strtolower($this->imageGetName('ext')) == 'jpg' ? 'jpeg' : @strtolower($this->imageGetName('ext')));
		$im = @$func($tmp_path);
		if($im !== false) {
			$image_file = $this->imageGetProperty("path") . $this->imageGetName() . $postfix . $extension;
			$width = imageSX($im);
			$height = imageSY($im);
			$image_file = ($display_flag)?NULL:$image_file;
			$this->imageResize($new_width, $new_height, $im, $image_file, $width, $height);
			ImageDestroy($im); // Remove tmp Image Object
		}
	}

	function imageCreateThumbnailIMagik( $tmp_path, $new_width, $new_height, $postfix = '' ) {
		$extension = '.' . $this->imageGetName('ext');
		$destination = $this->imageGetProperty("path") . $this->imageGetName() . $postfix . $extension;
		$tmp = sprintf("convert %s -resize %sx%s %s", $tmp_path,$new_width,$new_height,$destination);
		$res = system($tmp);
	}

	function imageCreateThumb( $tmp_path, $new_width, $new_height, $postfix = '', $display_flag=false, $type='jpg') {
		global $config;
		$dtls = @pathinfo($tmp_path);
		$extension = '.' . $dtls['extension'];
		$content_type = 'image/' . ($dtls['extension'] == 'jpg' ? 'jpeg' : $dtls['extension']);
		
		if($config['image_processing'] == 1) {
			$destination =  $dtls['dirname'] . '/' . $dtls['filename'] . $postfix . $extension;
#			$tmp = sprintf("convert %s -thumbnail %sx%s %s", $tmp_path,$new_width,$new_height,$destination);
			$tmp = sprintf("convert -limit memory 16MiB -limit map 32MiB %s -thumbnail %sx%s %s", $tmp_path,$new_width,$new_height,$destination);
			$res = exec($tmp);
			$tmp_path = $destination;
			$extension = '.' . $type;
			$content_type = 'image/' . ($type == 'jpg' ? 'jpeg' : $type);
			$destination =  $dtls['dirname'] . '/' . $dtls['filename'] . $postfix . $extension;

			$tmp = sprintf("convert %s %s",$tmp_path,$destination);
			$res = exec($tmp);
			
			if($display_flag) {
				
				$fp = fopen($destination, 'rb');
				header("Content-Type: $content_type");
				header("Content-Length: " . filesize($destination));
				fpassthru($fp);
				fclose($fp);
				unlink($destination);
				exit;
			}
		} else {
			$func = 'imagecreatefrom' . (@strtolower($dtls['extension']) == 'jpg' ? 'jpeg' : @strtolower($dtls['extension']));
			$im = @$func($tmp_path);
			if($im !== false) {
				$image_file = $dtls['dirname'] . $dtls['filename'] . $postfix . $extension;
				$width = imageSX($im);
				$height = imageSY($im);
				$image_file = ($display_flag)?NULL:$image_file;
				$this->imageResize($new_width, $new_height, $im, $image_file, $width, $height);
				ImageDestroy($im); // Remove tmp Image Object
			}
		}
	}

	function createThumbS3($imageId,$arr,$deleteFlag = true) {
		global $config;
		$_TMP = ($config['path']['tmp'] != '') ? $config['path']['tmp'] : sys_get_temp_dir() . '/';

		if($this->imageLoadById($imageId)) {
			$filName = 'Img_' . time();
			$fname = explode(".", $this->imageGetProperty('filename'));
			$tmpThumbPath = $_TMP . $filName . $arr['postfix'] . '.' . $fname[1];
			$thumbName = $this->imageGetProperty('path') .'/'. $fname[0] . $arr['postfix'] . '.' . $fname[1];
			$thumbName = (substr($thumbName,0,1)=='/')? substr($thumbName,1,strlen($thumbName)-1) : $thumbName;
			$tmpPath = $_TMP . $filName . '.' . $fname[1];

			$fp = fopen($tmpPath, "w+b");

			# getting the image from s3
			$bucket = $arr['s3']['bucket'];
			$key = $this->imageGetProperty('path') .'/'. $this->imageGetProperty('filename');
			$key = (substr($key,0,1)=='/')? substr($key,1,strlen($key)-1) : $key;
			$rr = $arr['obj']->get_object($bucket, $key, array('fileDownload' => $tmpPath));

			$this->imageCreateThumb($tmpPath, $arr['width'], $arr['height'], $arr['postfix']);
 			
			# uploading thumb to s3
			$response = $arr['obj']->create_object ( $bucket, $thumbName, array('fileUpload' => $tmpThumbPath,'acl' => AmazonS3::ACL_PUBLIC,'storage' => AmazonS3::STORAGE_REDUCED) );
			
			@unlink($tmpThumbPath);
			if($deleteFlag) {
				@unlink($tmpPath);
				return true;
			}
			return $tmpPath;
		}
		return false;
	}

	function createFromFileS3($tmpPath,$imageId,$arr,$deleteFlag = false) {
		if(!@file_exists($tmpPath)) return false;
		$dtls = @pathinfo($tmpPath);
		$extension = '.' . $dtls['extension'];
		$tmpThumbPath =  $dtls['dirname'] . '/' . $dtls['filename'] . $arr['postfix'] . $extension;
		$fname = explode(".", $this->imageGetProperty('filename'));
		$thumbName = $this->imageGetProperty('path') . '/' . $fname[0] . $arr['postfix'] . '.' . $fname[1];
		$thumbName = (substr($thumbName,0,1)=='/')? substr($thumbName,1,strlen($thumbName)-1) : $thumbName;

		# uploading thumb to s3
		$this->imageCreateThumb($tmpPath, $arr['width'], $arr['height'],$arr['postfix']);
		$response = $arr['obj']->create_object ( $arr['s3']['bucket'], $thumbName, array('fileUpload' => $tmpThumbPath,'acl' => AmazonS3::ACL_PUBLIC,'storage' => AmazonS3::STORAGE_REDUCED) );

		@unlink($tmpThumbPath);
		if($deleteFlag) {
			@unlink($tmpPath);
			return true;
		}
		return $tmpPath;
	}
*/

    ///////////////////////////////////////////////
    // Type: Function
    // Description:
    //    Recieves original image and resized it to
    //     desired size and save it to assigned path
    // Vars:
    //    x - Desired Max Width
    //    y - Desired Max Height
    //    im - original image
    //     path - path for file to be saved
    ///////////////////////////////////////////////           
    function imageResize($x,$y,$im,$path=NULL,$width,$height) {
        // Ratioi Resizing
        if ($width > $height) {
            $ratio = $height / $width;
            $y *= $ratio;
        } else {
            $ratio = $width / $height;
            $x *= $ratio;
        }

        $newImage=ImageCreateTrueColor($x,$y);
        imagecopyresized($newImage,$im,0,0,0,0,$x,$y,$width,$height);
        imagejpeg($newImage,$path,90);
        ImageDestroy($newImage);
    }


	public function getImage() {
		global $config;
		$_TMP = ($config['path']['tmp'] != '') ? $config['path']['tmp'] : sys_get_temp_dir() . '/';
		$flag = false;
		if($this->data['imageId'] != '') {
			$flag = $this->imageLoadById($this->data['imageId']);
		}
		if(!$flag && $this->data['barcode'] != '') {
			$flag = $this->imageLoadByBarcode($this->data['barcode']);
		}
		if(!$flag) return array('success' => false, 'code' => 135);
		
		$fname = explode(".", $this->imageGetProperty('filename'));
		$ext = ($this->data['type']==''?@strtolower($this->imageGetName('ext')):$this->data['type']);
		$extension = '.' . $ext;
		$func1 = 'image' . ($ext == 'jpg' ? 'jpeg' : $ext);
		$content_type = 'image/' . ($ext == 'jpg' ? 'jpeg' : $ext);
		$size = @strtolower($this->data['size']);
		//$path = $config['path']['images'] . substr($this->imageGetProperty('path'), 1, strlen($this->imageGetProperty('path'))-1);
		//$image =  $path . $fname[0] . $extension;
		$existsFlag = false;
		//$bucket = $config['s3']['bucket'];
		$tmpPath = $_TMP . $this->imageGetProperty('filename');
		
		$storage = new StorageDevice($this->db);
		$device = $storage->storageDeviceGet($this->imageGetProperty('storageDeviceId'));
		$bucket = $device['basePath'];
		$path = $device['basePath'] . $this->imageGetProperty('path');
		$image =  $path .'/'. $this->imageGetProperty('filename');
		
		# checking if exists
		if(in_array(strtolower($size),array('s', 'm', 'l'))) {
			if(strtolower($device['type']) == 's3') {
				$key = $this->imageGetProperty('path') .'/'. $fname[0] . '_' . $size . $extension;
				$key = (substr($key, 0, 1) == '/') ? substr($key, 1, strlen($key)-1) : $key;
				$existsFlag = $this->data['obj']->if_object_exists($bucket,$key);
			} else {
				$existsFlag = @file_exists($path .'/'. $fname[0] . '_' . $size . $extension);
			}
		}
		
		# if exists
		if($existsFlag) {
			if(strtolower($device['type']) == 's3') {
				$fp = fopen($tmpPath, "w+b");
				$this->data['obj']->get_object($bucket, $key, array('fileDownload' => $tmpPath));
				fclose($fp);
			} else {
				$tmpPath = $path .'/'. $fname[0] . '_' . $size . $extension;
			}

			$fp = fopen($tmpPath, 'rb');
// TODO THIS NEED to be the content type based on the data["type"] set
			header("Content-Type: " . $content_type);
			header("Content-Length: " . filesize($tmpPath));
			fpassthru($fp);
			fclose($fp);
			if(strtolower($device['type']) == 's3') {
				@unlink($tmpPath);
			}
			exit;
		}

		$ext = @strtolower($this->imageGetName('ext'));
		$extension = '.' . $ext;
		
		# Image variation does not exist
		if(strtolower($device['type']) == 's3') {
			# downloading original image
			$key = $this->imageGetProperty('path') .'/'. $fname[0] . $extension;
			$key = (substr($key, 0, 1) == '/') ? substr($key, 1, strlen($key)-1) : $key;
			$fp = fopen($tmpPath, "w+b");
			$this->data['obj']->get_object($bucket, $key, array('fileDownload' => $tmpPath));
			fclose($fp);
		} else {
			$tmpPath =  $image;
		}
		if(in_array(strtolower($size),array('s', 'm', 'l'))) {
			switch (strtolower($size)) {
				case 's':
					$this->data['width'] = 100;
					$this->data['height'] = 100;
					break;
				case 'm':
					$this->data['width'] = 275;
					$this->data['height'] = 275;
					break;
				case 'l':
					$this->data['width'] = 800;
					$this->data['height'] = 800;
					break;
			}
		}

		if($this->data['width'] != '' || $this->data['height'] != "") {
			$size = 'custom';
		}
		
		if(in_array(strtolower($size), array('s', 'm', 'l', 'custom'))){
			$dtls = @pathinfo($tmpPath);
			$extension = '.' . $dtls['extension'];
			//$file_name =  $dtls['dirname'] . '/' . $dtls['filename'] . '_' . $size . $extension;
// TODO you need to add the type param at the end of this and add it as an optiona argument in the createThumb
			$type = ($this->data['type']==''?@strtolower($this->imageGetName('ext')):$this->data['type']);
			$extension = '.' . ($type == 'jpg' ? 'jpeg' : $type);
			$file_name =  $dtls['dirname'] . '/' . $dtls['filename'] . '_' . $size . $extension;

			switch($size) {
				case 's':
					$this->imageCreateThumb( $tmpPath, 100, 100, '_s', false, $type);
					break;
				case 'm':
					$this->imageCreateThumb( $tmpPath, 275, 275, "_m", false, $type);
					break;
				case 'l':
					$this->imageCreateThumb( $tmpPath, 800, 800, "_l", false, $type);
					break;
				case 'custom':
					$width = ($this->data['width']!='') ? $this->data['width'] : $this->data['height'];
					$height = ($this->data['height']!='') ? $this->data['height'] : $this->data['width'];
					$this->imageCreateThumb( $tmpPath, $width, $height, 'tmp', true, $type);
					break;
			}
			
			if(strtolower($device['type']) == 's3') {
				# putting the image to s3
				$key = $this->imageGetProperty('path') .'/'. $fname[0] . '_' . $size . $extension;
				$key = (substr($key, 0, 1) == '/') ? substr($key, 1, strlen($key)-1) : $key;
				$response = $this->data['obj']->create_object ( $bucket, $key, array('fileUpload' => $file_name,'acl' => AmazonS3::ACL_PUBLIC,'storage' => AmazonS3::STORAGE_REDUCED) );
				
			}
			
			$fp = fopen($file_name, 'rb');
			header("Content-Type: $content_type");
			header("Content-Length: " . filesize($file_name));
			fpassthru($fp);
			if(strtolower($device['type']) == 's3') {
				@unlink($file_name);
				@unlink($tmpPath);
			}
			exit;
		} else {
			return array('success' => false, 'code' => 138);
		}

	}

	public function imageGetId($filename, $filepath, $storageDeviceId) {
		if($filename == '' || $storageDeviceId == '') return false;
		$query = sprintf("SELECT `imageId` FROM `image` WHERE `originalFilename` = '%s' AND `path` = '%s' AND `storageDeviceId` = '%s';", $filename, $filepath, $storageDeviceId);
		$ret = $this->db->query_one($query);
		if($ret->imageId == NULL) {
			return false;
		} else {
			return $ret->imageId;
		}
	}

	/**
	 * checks whether field exists in image table
	 */
	public function imageFieldExists ($imageId){
		if($imageId == '' || is_null($imageId)) return(false);

		$query = sprintf("SELECT `imageId` FROM `image` WHERE `imageId` = %s;", $imageId );
		$ret = $this->db->query_one( $query );
		if ($ret == NULL) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * checks whether field exists in image table
	 */
	public function imageBarcodeExists ($barcode,$returnFlag = false){
		$query = sprintf("SELECT `imageId` FROM `image` WHERE `barcode` = '%s';", $barcode );
		$ret = $this->db->query_one( $query );
		if ($ret == NULL) {
			return false;
		} else {
			if($returnFlag) {
				return $ret->imageId;
			} else {
				return true;
			}
		}
	}

	public function imageSave() {
		if($this->imageFieldExists($this->imageGetProperty('imageId'))) {
			$query = sprintf("UPDATE `image` SET  `filename` = '%s', `timestampModified` = now(), `barcode` = '%s', `width` = '%s', `height` = '%s', `family` = '%s', `genus` = '%s', `specificEpithet` = '%s', `rank` = '%s', `author` = '%s', `title` = '%s', `description` = '%s', `globalUniqueIdentifier` = '%s', `copyright` = '%s', `characters` = '%s', `flickrPlantId` = '%s', `flickrModified` = '%s', `flickrDetails` = '%s', `picassaPlantId` = '%s', `picassaModified` = '%s', `gTileProcessed` = '%s', `zoomEnabled` = '%s', `processed` = '%s', `boxFlag` = '%s', `ocrFlag` = '%s', `ocrValue` = '%s', `nameGeographyFinderFlag` = '%s', `nameFinderFlag` = '%s', `nameFinderValue` = '%s', `scientificName` = '%s', `collectionCode` = '%s', `tmpfamily` = '%s', `tmpfamilyAccepted` = '%s', `tmpGenus` = '%s', `tmpGenusAccepted` = '%s', `guessFlag` = '%s', `storageDeviceId` = '%s', `path` = '%s', `originalFilename` = '%s', `remoteAccessKey` = '%s', `statusType` = '%s', `rating` = '%s', `rawBarcode` = '%s'  WHERE imageId = '%s' ;"
				, mysql_escape_string($this->imageGetProperty('filename'))
				, mysql_escape_string($this->imageGetProperty('barcode'))
				, mysql_escape_string($this->imageGetProperty('width'))
				, mysql_escape_string($this->imageGetProperty('height'))
				, mysql_escape_string($this->imageGetProperty('family'))
				, mysql_escape_string($this->imageGetProperty('genus'))
				, mysql_escape_string($this->imageGetProperty('specificEpithet'))
				, mysql_escape_string($this->imageGetProperty('rank'))
				, mysql_escape_string($this->imageGetProperty('author'))
				, mysql_escape_string($this->imageGetProperty('title'))
				, mysql_escape_string($this->imageGetProperty('description'))
				, mysql_escape_string($this->imageGetProperty('globalUniqueIdentifier'))
				, mysql_escape_string($this->imageGetProperty('copyright'))
				, mysql_escape_string($this->imageGetProperty('characters'))
				, mysql_escape_string($this->imageGetProperty('flickrPlantId'))
				, mysql_escape_string($this->imageGetProperty('flickrModified'))
				, mysql_escape_string($this->imageGetProperty('flickrDetails'))
				, mysql_escape_string($this->imageGetProperty('picassaPlantId'))
				, mysql_escape_string($this->imageGetProperty('picassaModified'))
				, mysql_escape_string($this->imageGetProperty('gTileProcessed'))
				, mysql_escape_string($this->imageGetProperty('zoomEnabled'))
				, mysql_escape_string($this->imageGetProperty('processed'))
				, mysql_escape_string($this->imageGetProperty('boxFlag'))
				, mysql_escape_string($this->imageGetProperty('ocrFlag'))
				, mysql_escape_string($this->imageGetProperty('ocrValue'))
				, mysql_escape_string($this->imageGetProperty('nameGeographyFinderFlag'))
				, mysql_escape_string($this->imageGetProperty('nameFinderFlag'))
				, mysql_escape_string($this->imageGetProperty('nameFinderValue'))
				, mysql_escape_string($this->imageGetProperty('scientificName'))
				, mysql_escape_string($this->imageGetProperty('collectionCode'))
				, mysql_escape_string($this->imageGetProperty('tmpfamily'))
				, mysql_escape_string($this->imageGetProperty('tmpfamilyAccepted'))
				, mysql_escape_string($this->imageGetProperty('tmpGenus'))
				, mysql_escape_string($this->imageGetProperty('tmpGenusAccepted'))
				, mysql_escape_string($this->imageGetProperty('guessFlag'))
				, mysql_escape_string($this->imageGetProperty('storageDeviceId'))
				, mysql_escape_string($this->imageGetProperty('path'))
				, mysql_escape_string($this->imageGetProperty('originalFilename'))
				, mysql_escape_string($this->imageGetProperty('remoteAccessKey'))
				, mysql_escape_string($this->imageGetProperty('statusType'))
				, mysql_escape_string($this->imageGetProperty('rating'))
				, mysql_escape_string($this->imageGetProperty('rawBarcode'))
				, mysql_escape_string($this->imageGetProperty('imageId'))
			);
		} else {
			$query = sprintf("INSERT IGNORE INTO `image` SET `filename` = '%s', `timestampAdded` = now(), `timestampModified` = now(), `barcode` = '%s', `width` = '%s', `height` = '%s', `family` = '%s', `genus` = '%s', `specificEpithet` = '%s', `rank` = '%s', `author` = '%s', `title` = '%s', `description` = '%s', `globalUniqueIdentifier` = '%s', `copyright` = '%s', `characters` = '%s', `flickrPlantId` = '%s', `flickrModified` = '%s', `flickrDetails` = '%s', `picassaPlantId` = '%s', `picassaModified` = '%s', `gTileProcessed` = '%s', `zoomEnabled` = '%s', `processed` = '%s', `boxFlag` = '%s', `ocrFlag` = '%s', `ocrValue` = '%s', `nameGeographyFinderFlag` = '%s', `nameFinderFlag` = '%s', `nameFinderValue` = '%s', `scientificName` = '%s', `collectionCode` = '%s', `tmpfamily` = '%s', `tmpfamilyAccepted` = '%s', `tmpGenus` = '%s', `tmpGenusAccepted` = '%s', `guessFlag` = '%s', `storageDeviceId` = '%s', `path` = '%s', `originalFilename` = '%s', `remoteAccessKey` = '%s', `statusType` = '%s', `rating` = '%s', `rawBarcode` = '%s' ;"
				, mysql_escape_string($this->imageGetProperty('filename'))
				, mysql_escape_string($this->imageGetProperty('barcode'))
				, mysql_escape_string($this->imageGetProperty('width'))
				, mysql_escape_string($this->imageGetProperty('height'))
				, mysql_escape_string($this->imageGetProperty('family'))
				, mysql_escape_string($this->imageGetProperty('genus'))
				, mysql_escape_string($this->imageGetProperty('specificEpithet'))
				, mysql_escape_string($this->imageGetProperty('rank'))
				, mysql_escape_string($this->imageGetProperty('author'))
				, mysql_escape_string($this->imageGetProperty('title'))
				, mysql_escape_string($this->imageGetProperty('description'))
				, mysql_escape_string($this->imageGetProperty('globalUniqueIdentifier'))
				, mysql_escape_string($this->imageGetProperty('copyright'))
				, mysql_escape_string($this->imageGetProperty('characters'))
				, mysql_escape_string($this->imageGetProperty('flickrPlantId'))
				, mysql_escape_string($this->imageGetProperty('flickrModified'))
				, mysql_escape_string($this->imageGetProperty('flickrDetails'))
				, mysql_escape_string($this->imageGetProperty('picassaPlantId'))
				, mysql_escape_string($this->imageGetProperty('picassaModified'))
				, mysql_escape_string($this->imageGetProperty('gTileProcessed'))
				, mysql_escape_string($this->imageGetProperty('zoomEnabled'))
				, mysql_escape_string($this->imageGetProperty('processed'))
				, mysql_escape_string($this->imageGetProperty('boxFlag'))
				, mysql_escape_string($this->imageGetProperty('ocrFlag'))
				, mysql_escape_string($this->imageGetProperty('ocrValue'))
				, mysql_escape_string($this->imageGetProperty('nameGeographyFinderFlag'))
				, mysql_escape_string($this->imageGetProperty('nameFinderFlag'))
				, mysql_escape_string($this->imageGetProperty('nameFinderValue'))
				, mysql_escape_string($this->imageGetProperty('scientificName'))
				, mysql_escape_string($this->imageGetProperty('collectionCode'))
				, mysql_escape_string($this->imageGetProperty('tmpfamily'))
				, mysql_escape_string($this->imageGetProperty('tmpfamilyAccepted'))
				, mysql_escape_string($this->imageGetProperty('tmpGenus'))
				, mysql_escape_string($this->imageGetProperty('tmpGenusAccepted'))
				, mysql_escape_string($this->imageGetProperty('guessFlag'))
				, mysql_escape_string($this->imageGetProperty('storageDeviceId'))
				, mysql_escape_string($this->imageGetProperty('path'))
				, mysql_escape_string($this->imageGetProperty('originalFilename'))
				, mysql_escape_string($this->imageGetProperty('remoteAccessKey'))
				, mysql_escape_string($this->imageGetProperty('statusType'))
				, mysql_escape_string($this->imageGetProperty('rating'))
				, mysql_escape_string($this->imageGetProperty('rawBarcode'))
			);
		}
// echo '<br> Query : ' . $query;
		if($this->db->query($query)) {
			$this->insert_id = $this->db->insert_id;
			return(true);
		} else {
			return (false);
		}
	}

	public function imageGetNameFinderRecords($filter = '') {
		$query = " SELECT * FROM `image` WHERE ( `nameFinderFlag` = 0 OR `nameFinderFlag` IS NULL ) AND `ocrFlag` = 1 ";
		if(trim($filter['start']) != '' && trim($filter['limit']) != '') {
			$query .= build_limit(trim($filter['start']),trim($filter['limit']));
		}
		$ret = $this->db->query($query);
		return($ret);
	}

	public function imageGetNameGeographyFinderRecords($filter = '') {
		$query = " SELECT * FROM `image` WHERE ( `nameGeographyFinderFlag` = 0 OR `nameGeographyFinderFlag` IS NULL ) AND `ocrFlag` = 1 ";
		if(trim($filter['start']) != '' && trim($filter['limit']) != '') {
			$query .= build_limit(trim($filter['start']),trim($filter['limit']));
		}
		$ret = $this->db->query($query);
		return($ret);
	}

	public function imageGetOcrRecords($filter = '') {
		$query = " SELECT * FROM `image` WHERE ( `ocrFlag` = 0 OR `ocrFlag` IS NULL ) AND `processed` = 1 ";
		if(trim($filter['start']) != '' && trim($filter['limit']) != '') {
			$query .= build_limit(trim($filter['start']),trim($filter['limit']));
		}
		$ret = $this->db->query($query);
		return($ret);
	}

	public function imageGetGuessTaxaRecords($filter = '') {
		$query = " SELECT * FROM `image` WHERE ( `guessFlag` = 0 OR `guessFlag` IS NULL ) AND `processed` = 1 ";
		if(trim($filter['start']) != '' && trim($filter['limit']) != '') {
			$query .= build_limit(trim($filter['start']),trim($filter['limit']));
		}
		$ret = $this->db->query($query);
		return($ret);
	}

	public function imageGetBoxRecords($filter = '') {
		$query = " SELECT * FROM `image` WHERE ( `boxFlag` = 0 OR `boxFlag` IS NULL ) ";
		if(trim($filter['start']) != '' && trim($filter['limit']) != '') {
			$query .= build_limit(trim($filter['start']),trim($filter['limit']));
		}
		$ret = $this->db->query($query);
		return($ret);
	}

	public function imageGetFlickrRecords($filter = '') {
		$query = " SELECT * FROM `image` WHERE `flickrPlantId` = 0 OR `flickrPlantId` IS NULL ";
		if(trim($filter['start']) != '' && trim($filter['limit']) != '') {
			$query .= build_limit(trim($filter['start']),trim($filter['limit']));
		}
		$ret = $this->db->query($query);
		return($ret);
	}

	public function imageGetPicassaRecords($filter = '') {
		$query = " SELECT * FROM `image` WHERE `picassaPlantId` = 0 OR `picassaPlantId` IS NULL ";
		if(trim($filter['start']) != '' && trim($filter['limit']) != '') {
			$query .= build_limit(trim($filter['start']),trim($filter['limit']));
		}
		return ($this->db->query($query));
	}

	/**
	 * Return image records yet to be gTileProcessed
	 * @return mysql resultset
	 */
	public function imageGetGTileRecords($filter='') {
		$query = " SELECT * FROM `image` WHERE `gTileProcessed` = 0 OR `gTileProcessed` IS NULL ";
		if(trim($filter['start']) != '' && trim($filter['limit']) != '') {
			$query .= build_limit(trim($filter['start']),trim($filter['limit']));
		}
		return ($this->db->query($query));
	}

	public function imageGetZoomifyRecords($filter='') {
		$query = " SELECT * FROM `image` WHERE `zoomEnabled` = 0 OR `zoomEnabled` IS NULL ";
		if(trim($filter['start']) != '' && trim($filter['limit']) != '') {
			$query .= build_limit(trim($filter['start']),trim($filter['limit']));
		}
		return ($this->db->query($query));
	}

	public function imgeGetNonProcessedRecords($filter='') {
		$query = " SELECT * FROM `image` WHERE ( `processed` = 0 OR `processed` IS NULL ) ";
		if($filter['collectionCode'] != '') {
			$query .= sprintf(" AND `collectionCode` = '%s' ", $filter['collectionCode']);
		}
		if(trim($filter['start']) != '' && trim($filter['limit']) != '') {
			$query .= build_limit(trim($filter['start']),trim($filter['limit']));
		}
		return ($this->db->query($query));
	}

	/**
	 * Creates the GoogleMap Tiles for the image
	 * @param string barcode
	 */
	public function imageProcessGTile($barcode) {
		global $config;
		if($this->imageLoadByBarcode($barcode)) {

		$ext = @strtolower($this->imageGetName('ext'));
		$func = 'imagecreatefrom' . ($ext == 'jpg' ? 'jpeg' : $ext);
		$func1 = 'image' . ($ext == 'jpg' ? 'jpeg' : $ext);

		$outputPath = $config['path']['images'] . $this->imageBarcodePath( $barcode ) . 'google_tiles/';
		$image = $config['path']['images'] . $this->imageBarcodePath( $barcode ) . $this->imageGetProperty('filename');

// 			$src = imagecreatefromjpeg( $image );
		$src = $func( $image );
		$dest = imagecreatetruecolor(256, 256);

// 2x Zoom
			$zoomfactor = 2;
			$tmp = imagecreatetruecolor( imagesx( $src ) * $zoomfactor, imagesy( $src ) * $zoomfactor );
			imagecopyresized($tmp, $src, 0, 0, 0, 0, imagesx( $src ) * $zoomfactor, imagesy( $src ) * $zoomfactor, imagesx( $src ), imagesy( $src ));
			$src = $tmp;

			for ($k = 0; $k <= 5; $k++) {
				$width = imagesx( $src );
				$height = imagesy( $src );
				if ($k == 0) {
					$sample = $src;
				} else {
			
					$percent = 1 / pow(2, $k);
					$newwidth = $width * $percent;
					$newheight = $height * $percent;
					$sample = imagecreatetruecolor($newwidth, $newheight);
					imagecopyresized($sample, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
					$width = $newwidth;
					$height = $newheight;
				}

				for ($i = 0; $i <= (int) ( $width / 256 ); $i++) {
					for ($j = 0; $j <= (int) ( $height / 256 ); $j++) {
						$x = $i;
						$y = $j;
						$z = 1;

						$this->imageMkdirRecursive($outputPath . $k . '/');
						imagecopy($dest, $sample, 0, 0, ($i * 256), ($j * 256), 256, 256);
// 						imagejpeg($dest, sprintf( $outputPath . '%s/tile_%s_%s_%s.jpg', $k, $z, $x, $y) );
						$func1($dest, sprintf( $outputPath . '%s/tile_%s_%s_%s.' . $ext, $k, $z, $x, $y) );
				
					}
				}
				
			}
			
			imagedestroy($dest);
			imagedestroy($src);
			imagedestroy($sample);

			$this->imageSetProperty('gTileProcessed',1);
			$this->save();

			return true;
		} # if barcode present
		return false;
	}

	/**
	 * Creates the GoogleMap Tiles for the image using IM for S3 mode
	 * @param string barcode
	 * @param mixed s3 details and object
	 */
	public function imageProcessGTileIM($barcode) {
		global $config;
		if($this->imageLoadByBarcode($barcode)) {
			$tilepath = $config['path']['images'] . $this->imageBarcodePath( $barcode ) . 'google_tiles/';
			$filename = $config['path']['images'] . $this->imageBarcodePath( $barcode ) . $this->imageGetProperty('filename');
			$this->imageMkdirRecursive($tilepath);
			# creating tiles using Image Magik
			$gTileRes = $this->imageCreateGTileIM($filename, $tilepath);
			return true;
		}
		return false;
	}

	/**
	 * Creates the GoogleMap Tiles for the image using IM for s3 mode
	 * @param string barcode
	 * @param mixed s3 details and object
	 */
	public function imageProcessGTileIMS3($barcode, $arr) {
		global $config;
		$_TMP = ($config['path']['tmp'] != '') ? $config['path']['tmp'] : sys_get_temp_dir() . '/';		if($this->imageLoadByBarcode($barcode)) {

		$tmpPath = $_TMP . 'tiles/';
		if(!@file_exists($tmpPath)) {
			@mkdir($tmpPath,0775);
		}
		$tilepath = $tmpPath;

		# getting the image from s3
		$filename = $_TMP . $this->imageGetProperty('filename');

		$bucket = $arr['s3']['bucket'];
		$key = $this->imageBarcodePath($barcode) . $this->imageGetProperty('filename');
		$arr['obj']->get_object($bucket, $key, array('fileDownload' => $filename));

		# creating tiles using Image Magik
		$gTileRes = $this->imageCreateGTileIM($filename,$tilepath);

		# uploading to s3 and deleting the files
		$tiles3path = $this->imageBarcodePath($barcode) . 'google_tiles/';
		
		if ($handle = @opendir($tilepath)) {
			while (false !== ($file = @readdir($handle))) {
				if ($file != '.' && $file != '..') {
					$file = $tilepath . $file;
					if(is_dir($file)) {		
						if ($tempHandle = @opendir($file)) {
							while (false !== ($tile = @readdir($tempHandle))) {
								if ($tile != '.' && $tile != '..') {
									$tmpThumbPath = $tilepath . @basename($file) . '/' . @basename($tile);
									$tmpS3Path = $tiles3path . @basename($file) . '/' . @basename($tile);
									$response = $arr['obj']->create_object ( $bucket, $tmpS3Path, array('fileUpload' => $tmpThumbPath,'acl' => AmazonS3::ACL_PUBLIC,'storage' => AmazonS3::STORAGE_REDUCED) );
									@unlink($tilepath . @basename($file) . '/' . @basename($tile));
								} # not . or ..
							} # while tile
							@closedir($tempHandle);
						} # temp handle
						rmdir($file);
					} # is dir
				} # not . or ..
			} # while file
			@closedir($handle);
		} # handle

			@unlink($filename);
			@unlink($tmpPath);
			return true;
		}
		return false;
	}

	/**
	 * Creates gTiles using IM
	 * @param string $filename : input filename
	 * @param string $outputPath : location for creating tiles
	 */
	function imageCreateGTileIM($filename, $outputPath) {
	
		if (!file_exists($filename)) {
			return( array("success" => false, "error" => array("code" => 100, "msg" => "File does not exist.") ) );
		}
	
		$filePath = @dirname($filename) . '/';
		$dimensions = exec('identify -format "%w,%h" ' . $filename);
		list($owidth,$oheight) = explode(',',$dimensions);
		
		if(!file_exists($outputPath)) {
			@mkdir($outputPath, 0777);
		}
	
		$zoomLevels = round(sqrt($oheight / 256));
		for ($z = 0; $z < $zoomLevels; $z++) {
			if ($z == 0) {
				$width = $owidth;
				$height = $oheight;
				$tmpFile = $filename;
			} else {
				$tmpFile = $filePath . $z . "tmp" . @basename($filename);
				$percent = 1 / pow(2, $z);
				$width = $owidth * $percent;
				$height = $oheight * $percent;
				$cmd = sprintf("convert \"%s\" -resize %sx%s \"%s\""
					,	$filename
					,	$width
					,	$height
					,	$tmpFile
				);
				$res = system($cmd);
			}
	
			$iLimit = (int) ( $width / 256 );
			$jLimit = (int) ( $height / 256 );
	
			for ($i = 0; $i <= $iLimit; $i++) {
				for ($j = 0; $j <= $jLimit; $j++) {
				
					$x = $i;
					$y = $j;
	//				$z = 1;
					
					$this->imageMkdirRecursive($outputPath . $z . '/');
		
					$cmd = sprintf("convert \"%s\" -crop %sx%s+%s+%s\! %s%s/tile_%s_%s_%s.jpg"
						, $tmpFile
						,	256
						,	256
						,	($i * 256)
						,	($j * 256)
						,	$outputPath
						,	$z
						,	$z
						,	$x
						,	$y
					);
					$res = system($cmd);
					if($i == $iLimit || $j == $jLimit) {
						$tmpImage = sprintf("%s%s/tile_%s_%s_%s.jpg",$outputPath,$z,$z,$x,$y);
						$cmd = sprintf("convert \"%s\" -background white -extent 256x256 +repage \"%s\"", $tmpImage, $tmpImage);
						$res = system($cmd);
					}
				}
			}
			if($tmpFile != $filename) {
				@unlink($tmpFile);
			}
		}
		return( array("success" => true) );
	}

	/**
	 * Zoomify the image
	 */
	public function imageZoomify($barcode) {
		global $config;
		if($this->imageLoadByBarcode($barcode)) {
			$outputPath = $config['path']['images'] . $this->imageBarcodePath( $barcode ) . 'zoomify/';
			$this->imageMkdirRecursive( $outputPath );
			$image = $config['path']['images'] . $this->imageBarcodePath( $barcode ) . $this->imageGetProperty('filename');
			$script_path =  $config['path']['base'] . 'api/classes/zoomify/ZoomifyFileProcessor.py ';
			passthru('python ' . $script_path . $image);

// 			passthru('/usr/bin/python ' . $script_path . $image);
// 			$str = exec('python ' . $script_path . $image, $ret);
/*			print '<br>' . $str . '<br>';
			var_dump($ret);*/

// 			$this->imageSetProperty('processed',1);
// 			$this->save();
		}
		return false;
	}

	public function imageList($queryFlag = true) {

		if(is_array($this->data['advFilter']) && count($this->data['advFilter'])) {
			$orderBy = '';
			$query = $this->getByCrazyFilter($this->data['advFilter'], true, $this->data['showOCR']);

			$this->query = $query;
			
			// if(($this->data['sort']!='') && ($this->data['dir']!='')) {
				// $orderBy .= sprintf(" ORDER BY i.%s %s ", mysql_escape_string($this->data['sort']),  $this->data['dir']);
			// } else if($this->data['order'] != '' && is_array($this->data['order'])) {
				// $orderBy .= ' ORDER BY ';
				// if(count($this->data['order'])) {
					// $ordArray = array();
					// foreach($this->data['order'] as $order) {
						// $ordArray[] = " i.{$order['field']} {$order['dir']} ";
					// }
					// $orderBy .= implode(',',$ordArray);
				// }
			// }
			
			if(($this->data['sort']!='') && ($this->data['dir']!='')) {
				$this->setOrderFilter('manual');
			} else if ($this->data['gridSort'] != '' && is_array($this->data['gridSort'])) {
				$this->setOrderFilter('gridSort');
			} else {
				$this->setOrderFilter('');
			}
			
			$query = $this->query;
			
			if($this->data['useRating']) {
				$orderBy .= ($orderBy == '') ? ' ORDER BY i.`rating` DESC ' : ', i.`rating` DESC ';
			}
			if($this->data['useStatus']) {
				$orderBy .= ($orderBy == '') ? ' ORDER BY i.`statusType` DESC ' : ', i.`statusType` DESC ';
			}
			$query .= $orderBy;

			if(trim($this->data['start']) != '' && trim($this->data['limit']) != '') {
				$query .= sprintf("  LIMIT %s, %s", mysql_escape_string($this->data['start']),  $this->data['limit']);
			}

			// echo $query;exit;
		
		
			if($queryFlag) {
				$ret = $this->db->query_all($query);
				$this->total = $this->db->query_total();
				return is_null($ret) ? array() : $ret;
			} else {
				$ret = $this->db->query( $query );
				$this->total = $this->db->query_total();
				return $ret;
			}
		}
	
		$characters = $this->data['characters'];
		$browse = $this->data['browse'];

		$this->query = "SELECT I.imageId,I.filename,I.timestampAdded,I.timestampModified, I.barcode, I.width,I.height,I.family,I.genus,I.specificEpithet,I.flickrPlantId, I.flickrModified,I.flickrDetails,I.picassaPlantId,I.picassaModified, I.gTileProcessed,I.zoomEnabled,I.processed,I.boxFlag,I.ocrFlag,I.rating, I.author, I.copyright";
		if($this->data['showOCR']) {
			$this->query .= ',I.ocrValue';
		}
		if($this->data['showBarcode']) {
			$this->query .= ',I.rawBarcode';
		}
		
		# fields for url computation
		$this->query .= ',I.storageDeviceId,I.path';
		
		$this->query .= ",I.nameGeographyFinderFlag,I.nameFinderFlag,I.nameFinderValue,I.scientificName, I.collectionCode, I.globalUniqueIdentifier FROM `image` I ";

		$this->queryCount = ' SELECT count(*) AS sz FROM `image` I ';
		
		if (($characters != '') && ($characters != '[]')) {
			if($this->data['characterType'] == 'ids') {
				$this->query .= ", imageAttrib ia ";
				$this->queryCount .= ", imageAttrib ia ";
			} 
			// $this->query .= " LEFT OUTER JOIN imageAttrib ia ON ia.`imageId` = I.`imageId` LEFT OUTER JOIN imageAttribValue iav ON  ia.`attributeId` = iav.`attributeId` ";
			// $this->queryCount .= " LEFT OUTER JOIN imageAttrib ia ON ia.`imageId` = I.`imageId` LEFT OUTER JOIN imageAttribValue iav ON  ia.`attributeId` = iav.`attributeId` ";
		}

		$this->query .= " WHERE 1=1 AND (";
		$this->queryCount .= " WHERE 1=1 AND (";
		
		$this->setBrowseFilter();
		$this->query .= " AND I.imageId != '' ";
		$this->queryCount .= " AND I.imageId != '' ";
		$this->setAdminCharacterFilter();

		if ($this->data['searchValue'] != '') {
			$this->query .= sprintf(" AND %s LIKE '%s%%' ", $this->data['searchType'], $this->data['searchValue']);
			$this->queryCount .= sprintf(" AND %s LIKE '%s%%' ", $this->data['searchType'], $this->data['searchValue']);
		}

		$where = buildWhere($this->data['filter']);
		if ($where != '') {
			$where = " AND " . $where;
		}
		if($this->data['code'] != '') {
			$codeAr = @explode(',',$this->data['code']);
			foreach($codeAr as $code) {
				$code = mysql_escape_string($code);
			}
			// $where .= sprintf(" AND I.`collectionCode` = '%s' ", mysql_escape_string($this->data['code']));
			$where .= sprintf(" AND I.`collectionCode` IN ('%s') ", @implode("','",$codeAr));
		}
		
		if(is_array($this->data['imageId']) && count($this->data['imageId'])) {
			$where .= sprintf(" AND `imageId` IN (%s) ", implode(',', $this->data['imageId']));
		} else if($this->data['imageId'] != '') {
			$where .= sprintf(" AND `imageId` = '%s' ", mysql_escape_string($this->data['imageId']));
		}
		
		if(is_array($this->data['barcode']) && count($this->data['barcode'])) {
			$where .= sprintf(" AND `barcode` IN ('%s') ", implode("','", $this->data['barcode']));
		} else if($this->data['barcode'] != '') {
			$where .= sprintf(" AND `barcode` = '%s' ", mysql_escape_string($this->data['barcode']));
		}
		
		if(is_array($this->data['filename']) && count($this->data['filename'])) {
			$where .= sprintf(" AND `filename` IN ('%s') ", implode("','", $this->data['filename']));
		} else if($this->data['filename'] != '') {
			$where .= sprintf(" AND `filename` = '%s' ", mysql_escape_string($this->data['filename']));
		}

		if($this->data['value'] != '') {
			switch($this->data['searchFormat']) {
				case 'exact':
					$where .= sprintf(" AND `filename` = '%s' ", mysql_escape_string($this->data['value']));
					break;
				case 'left':
					$where .= sprintf(" AND `filename` LIKE '%s%%' ", mysql_escape_string($this->data['value']));
					break;
				case 'right':
					$where .= sprintf(" AND `filename` LIKE '%%%s' ", mysql_escape_string($this->data['value']));
					break;
				case 'both':
				default:
					$where .= sprintf(" AND `filename` LIKE '%%%s%%' ", mysql_escape_string($this->data['value']));
					break;
			}
		}

		if($this->data['field'] != '' && $this->data['value'] != '') {
			$where .= sprintf(" AND `%s` = '%s' ", mysql_escape_string($this->data['field']), mysql_escape_string($this->data['value']));
		}

		$this->query .= $where;
		$this->queryCount .= $where;
		$this->setGroupFilter();
		if(($this->data['sort']!='') && ($this->data['dir']!='')) {
			$this->setOrderFilter('manual');
		} else if ($this->data['gridSort'] != '' && is_array($this->data['gridSort'])) {
			$this->setOrderFilter('gridSort');
		} else {
			$this->setOrderFilter('');
		}
		$this->setLimitFilter();

		$countRet = $this->db->query_one( $this->queryCount );
		if ($countRet != NULL) {
			$this->total = $countRet->sz;
		}

		if($queryFlag) {
			$ret = $this->db->query_all($this->query);
			return is_null($ret) ? array() : $ret;
		} else {
			$ret = $this->db->query( $this->query );
			return $ret;
		}
	}

	public function imageSequenceCache() {

		$query = 'SELECT SQL_CALC_FOUND_ROWS * FROM `image` WHERE 0=0 ';
		if($this->data['code'] != '') {
			$query .= sprintf(" AND `barcode` LIKE '%s%%' ", mysql_escape_string($this->data['code']));
		}

		$query .= ' ORDER BY `barcode` ';

		$Ret = $this->db->query($query);

		$pre_fix = '';
		$counter = '';
		$strips_array = array();
		$start = '';
		$end = '';
		$preCount = 0;

		if(is_object($Ret)) {
			while ($record = $Ret->fetch_object()) {
				$ar = getBarcodePrefix($record->barcode);
				$barpre = $ar['prefix'];
				$barc = (int) $ar['tail'];

				$tmpStrip = str_replace($barc,'',$ar['tail']);

				$preCount++;

				if($counter == '') {
					$counter = $barc;
					$pre_fix = $barpre;
					$start = $end = $record->barcode;
					$pre = $barpre;
				}

				if($pre_fix != $barpre || $counter != $barc) {

					if($pre_fix == $barpre) {
						$qq = getBarcodePrefix($end);
						$qq_tail = (int) $qq['tail'];
						$lt = $barc - $qq_tail - 1;
						$tmp_start = $barpre . $tmpStrip . ($qq_tail + 1);
						$qq = getBarcodePrefix($record->barcode);
						$qq_tail = (int) $qq['tail'];
						$tmp_end = $barpre . $tmpStrip . ($qq_tail - 1);
						$strips_array[$end][] = array('startRange' => $tmp_start, 'endRange' => $tmp_end, 'prefix' => $pre, 'recordCount' => $lt, 'exist' => 0);
					}

					$strips_array[$start][] = array('startRange' => $start, 'endRange' => $end, 'prefix' => $pre, 'recordCount' => $preCount, 'exist' => 1);
					$preCount = 0;
					$start = $end = $record->barcode;
					$pre = $barpre;
					$counter = $barc;
				}
				$pre_fix = $barpre;
				$end = $record->barcode;
				$counter++;
			} # foreach

			# last record bein the exception and increment not done
			if($preCount == 0) $preCount++;

			$strips_array[$start][] = array('startRange' => $start, 'endRange' => $end, 'prefix' => $pre, 'recordCount' => $preCount, 'exist' => 1);
		} # if count array

		ksort($strips_array);
		$output = array();

		if(count($strips_array) && is_array($strips_array)) {
			foreach($strips_array as $strp) {
				if(count($strp) && is_array($strp)) {
					foreach($strp as $stp) {
						$output[] = $stp;
					}
				}
			}
		}

		return $output;
	}

	public function imageModifyRotate() {
		$pqueue = new ProcessQueue($this->db);
		$barcode = $this->imageGetProperty('barcode');
		$storage = new StorageDevice($this->db);
		$key = rtrim($this->imageGetProperty('path'),'/').'/'.$this->imageGetProperty('filename');
		$imageFile = $storage->storageDeviceFileDownload($this->imageGetProperty('storageDeviceId'), $key);
		if(false !== $imageFile) {
			$cmd = sprintf("convert -limit memory 16MiB -limit map 32MiB \"%s\" -rotate %s \"%s\"", $imageFile, $this->data['degree'], $imageFile);
			system($cmd);
			if(strtolower($storage->storageDeviceGetType($this->imageGetProperty('storageDeviceId'))) == 's3') {
				$storage->storageDeviceFileUpload($this->imageGetProperty('storageDeviceId'), $key, $imageFile);
			}
			$ar = explode('.',$this->imageGetProperty('filename'));
			foreach(array('_s','_m','_l') as $postfix) {
				$storage->storageDeviceDeleteFile($this->imageGetProperty('storageDeviceId'), $ar[0] . $postfix . '.jpg', $this->imageGetProperty('path'));
			}
			$this->imageSetProperty('flickrPlantId', 0);
			$this->imageSetProperty('picassaPlantId', 0);
			$this->imageSetProperty('gTileProcessed', 0);
			$this->imageSetProperty('zoomEnabled', 0);
			$this->imageSetProperty('processed', 0);
			$this->imageSave();

			$pqueue->processQueueSetProperty('imageId',$this->imageGetProperty('imageId'));
			$pqueue->processQueueSetProperty('processType','all');
			$pqueue->processQueueSave();
			return true;
		}
		return false;
	}

	public function imageDelete() {
		$imageId = $this->data['imageId'];
		$storage = new StorageDevice($this->db);
		if($imageId != '' && $this->imageFieldExists($imageId)) {
			$this->imageLoadById($imageId);
			$barcode = $this->imageGetProperty('barcode');
			$device = $storage->storageDeviceGet($this->imageGetProperty('storageDeviceId'));
			$filenameParts = explode('.', $this->imageGetProperty('filename'));
			
			$storage->storageDeviceDeleteFile($this->imageGetProperty('storageDeviceId'), $this->imageGetProperty('filename'), $this->imageGetProperty('path'));

			if(strtolower($device['type'] == 'local')) {
				# Remove empty directories
				$path = $this->imageGetProperty('path');
				$parts = explode('/', $path);
				while(count($parts)>0) {
					if(!rmdir($device['basePath'] . $path . '/')) break;
					$parts = explode('/', $path);
					unset($parts[count($parts)-1]);
					$path = implode('/', $parts);
				}
			}
			
			$query = sprintf("DELETE FROM `imageAttrib` WHERE `imageId` = '%s' ", mysql_escape_string($imageId));
			$this->db->query($query);
			
			$query = sprintf("DELETE FROM `eventImages` WHERE `imageId` = '%s' ", mysql_escape_string($imageId));
			$this->db->query($query);
			
			$query = sprintf("DELETE FROM `processQueue` WHERE `imageId` = '%s' ", mysql_escape_string($imageId));
			$this->db->query($query);
			
			$query = sprintf("DELETE FROM `specimen2Label` WHERE `barcode` = '%s' ", mysql_escape_string($barcode));
			$this->db->query($query);
			
			$delquery = sprintf("DELETE FROM `image` WHERE `imageId` = '%s' ", mysql_escape_string($imageId));
			if($this->db->query($delquery)) {
				return  array('success' => true);
			}
			return array('success' => false, 'code' => 169);
		}
		return array('success' => false, 'code' => 170);
	}


	public function imageGetGeneraList($filter=array()) {
		$query = "SELECT DISTINCT `genus` FROM `image` WHERE `family` = '' AND `genus` != '' ";
		if($filter['limit'] != '') {
			$query .= sprintf(" LIMIT %s ", $filter['limit']);
		}
		$ret = $this->db->query($query);
		return($ret);
	}

	public function imageGetScientificName($genus) {
		$query = sprintf("SELECT DISTINCT `scientificName` FROM `image` WHERE `scientificName` != '' AND `genus` = '%s' ", mysql_escape_string($genus));
		$ret = $this->db->query_one($query);
		return($ret);
	}

	public function imageUpdatefamilyList($genus,$family ) {
		$query = sprintf(" UPDATE `image` SET  `family` = '%s' WHERE `genus` = '%s' AND `family` = '' ", mysql_escape_string($family), mysql_escape_string($genus));
		if($this->db->query($query)) {
			return array('success' => true, 'records' => $this->db->affected_rows);
		} else {
			return array('success' => false);
		}
	}

	public function imageRrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != '.' && $object != '..') {
					if (filetype($dir.'/'.$object) == 'dir') $this->imageRrmdir($dir.'/'.$object); else unlink($dir.'/'.$object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	public function loadBrowse() {
		$ar = array();
		$nodes = array();
		$childFlag = true;
		$mapping = array('family' => 'genus', 'genus' => 'specificEpithet');
		if($this->data['browse'] != '') $this->data['browse'] = json_decode($this->data['browse'],true);

		switch( $this->data['nodeApi'] ) {
			case 'alpha':
				for ($i=65;$i<91;$i++) {
					$tmp = chr($i);
					$nodes[] = array('text' => $tmp, 'nodeApi' => $this->data['nodeValue'], 'nodeValue' => $tmp . '%', 'path' => $this->data['path'], 'filter' => json_decode($this->data['filter'],true) );
				}
				$ret = true;
				break;
			case "family":
			case "genus":
			case "specificEpithet":
				$parent = $this->data['nodeApi'];
				if ($this->data['nodeValue'] != 'null' && $this->data['nodeValue'] != '') {
					if (strpos($this->data['nodeValue'], "%") !== false) {
						$childFlag = false;
					}
				}
				$child = ($childFlag && isset($mapping[$this->data['nodeApi']])) ? $mapping[$this->data['nodeApi']] : $this->data['nodeApi'] ;

				if(in_array($child,array('family','specificEpithet'))) {
					$query = sprintf("SELECT %s as text, count(*) as cnt FROM `image` WHERE 1=1 ", $child);
				} else {
					$query = sprintf("SELECT %s as text, count(*) as cnt FROM `image` WHERE 1=1 AND  %s != '' ", $child, $child);
				}

				$this->data['filter'] = json_decode($this->data['filter'], true);

				if ($this->data['nodeValue'] != 'null' && $this->data['nodeValue'] != '') {
					$condition = '=';
					if (strpos($this->data['nodeValue'], "%") !== false) {
						$condition = 'LIKE';
					}
					$query .= sprintf(" AND %s %s '%s' ", $parent, $condition, mysql_escape_string($this->data['nodeValue']) );
				}

				if (is_array($this->data['filter']) && count($this->data['filter'])) {
					foreach( $this->data['filter'] as $key => $value ) {
						switch( $key ) {
							default:
								$condition = '=';
								if (strpos($value, "%") !== false) {
									$condition = 'LIKE';
								}				
								$query .= sprintf(" AND %s %s '%s' ", mysql_escape_string($key), $condition, mysql_escape_string($value) );
								break;
						}
					} # foreach
				}
		
				$query .= sprintf(" GROUP BY %s ORDER BY %s ", $child, $child);
				$nextChild = $mapping[$this->data['nodeApi']];
				$leaf = true;
				if (isset($nextChild)) {
					$cls = 'file';
					$leaf = false;
				}
				$results = $this->db->query_all( $query );
				if($parent != '') {
					$filter = $this->data['filter'];
					$filter[$parent] = $this->data['nodeValue'];
					$this->data['filter'] = $filter;
				}

				if(count($results)){
					foreach( $results as $record ) {

						if($record->text == '') {
							$text = $nv = "<b>BLANK</b>";
						} else {
							$text = $record->text . " (" . number_format($record->cnt) . ")";
							$nv = $record->text;
						}

						$nodes[] = array('text'=>$record->text . " (" . number_format($record->cnt) . ")", 'specimenCount' => $record->cnt, 'checked' => false, 'leaf' => $leaf, 'nodeApi' => $child, 'nodeValue' => $record->text, 'filter' => $this->data['filter']);
					}
				} else {
					$nodes = array();
				}
				break;
			default:
				break;
		} # switch

		$this->data['time_end'] = microtime(true);
		$time = $this->data['time_end'] - $this->data['time_start'];
		$time = number_format($time,4);
		
		$ar = array();
		$ar['success'] = true;
		$ar['total_execute_time'] = $time;
		$ar['totalCount'] = count($nodes);
		$ar['results'] = $nodes;

		return($ar);
	}

	function imageGetCollectionSpecimenCount() {
		if($this->data['nodeApi'] == '' || $this->data['nodeValue'] == '') return false;
		$condition = '=';
		if (strpos($this->data['nodeValue'], "%") !== false) {
			$condition = 'LIKE';
		}

		$query = sprintf(" SELECT count(*) ct, `collectionCode` FROM `image` WHERE %s %s '%s' GROUP BY `collectionCode` ", $this->data['nodeApi'], $condition, mysql_escape_string($this->data['nodeValue'] ));
		$ret = $this->db->query_all($query);
		return $ret;
	}

	function populateS3Data($response) {
		$recordCount = 0;
		$srchArray = array('_s','_m','_l','_thumb','google_tiles','tile_');

		if(count($response) && is_array($response)) {
			foreach($response as $filePath) {
				$fileDetails = @pathinfo($filePath);
				$count = 0;
				$tmpStr = $fileDetails['basename'];
				str_replace($srchArray,'',$tmpStr,$count);
				if($count == 0) {
					$this->imageSetProperty('filename',$fileDetails['basename']);
					$this->imageSetProperty('barcode',$fileDetails['filename']);
					$this->imageSetProperty('timestampModified',@date('d-m-Y H:i:s',@strtotime($ky->LastModified)));

					if($this->save()) {
						if($this->db->affected_rows == 1) {
							$recordCount++;
						}
					}
				}
			}
		}
		if($recordCount) {
			$ret = array('success' => true, 'recordCount' => $recordCount);
		} else {
			$ret = array('success' => false);
		}
		return $ret;
	}

	private function setFilters() {
		$this->setCharacterFilter();
		$this->setSearchFilter();
		$this->setFilter();
	}

	private function setCharacterFilter() {
	
		if (($this->data['characters'] != '') && ($this->data['characters'] != '[]')) {
			$this->query .= ", count(*) as sz";
		}

		$tstr = ' FROM image I ';
		
		if (($this->data['characters'] != '') && ($this->data['characters'] != '[]')) {
			$tstr .= ', imageAttrib ia ';
		}

		$tstr .= ' WHERE 1=1 AND (';

		$this->query = $this->query . $tstr;
		$this->queryCount .= $tstr;

		$this->setBrowseFilter();

		switch($this->data['imagesType']) {
			case 1:
				$tstr = " AND I.barcode != '' ";
				$this->query .= $tstr;
				$this->queryCount .= $tstr;
				break;
			case 2:
			default:
				$this->query .= '';
				break;
			case 3:
				$tstr = " AND I.barcode = '' ";
				$this->query .= $tstr;
				$this->queryCount .= $tstr;
				break;
		}
		if (($this->data['characters'] != '') && ($this->data['characters'] != '[]')) {
			$this->char_list = '';
			$this->char_count = 0;
			foreach(json_decode($this->data['characters']) as $character) {
				$this->char_list .= $character->node_value . ",";
				$this->char_count++;
			}
			$this->char_list = substr($this->char_list, 0, -1);
			$tstr = " AND ia.imageId = I.imageId AND ia.attributeId IN ( " . $this->char_list . " ) ";
			$this->query .= $tstr;
			$this->queryCount .= $tstr;
		}
	}

	private function setSearchFilter() {
		if($this->data['searchValue'] != '') {
			$tstr = " AND ". $this->data['searchType'] ." LIKE '" .$this->data['searchValue'] ."%' ";
			$this->query .= $tstr;
			$this->queryCount .= $tstr;
		}
	}

	private function setFilter() {
		if($this->data['filter'] != '') {
			$filter = json_decode($this->data['filter'],true);
			if(is_array($filter) && count($filter)) {
				$tstr = '';
				foreach($filter as $field => $value) {
					$tstr .= sprintf(" AND %s = '%s' ", $field, mysql_escape_string($value));
				}
				$this->query .= $tstr;
				$this->queryCount .= $tstr;
			}
		}
	}

	private function setBrowseFilter() {

		if ($this->data['browse'] != '' && $this->data['browse'] != '[]') {
			$tstr = '';
			foreach(json_decode($this->data['browse']) as $character) {
				$this->char_list .= $character->node_value . ",";
				if($character->node_type == 'species') $character->node_type = 'specificEpithet';
				if ($character->node_type == 'species') {
					$tstr .= " (I." . $character->node_type . " = '" . $character->node_value . "' AND I.genus='" . $character->genus . "') OR";
				} else {
					$tstr .= " (I." . $character->node_type . " = '" . $character->node_value . "') OR";
				}
			}
			$this->query .= $tstr;
			$this->queryCount .= $tstr;
			$this->query = substr($this->query, 0, -2) . ")";
			$this->queryCount = substr($this->queryCount, 0, -2) . ")";
		} else {
			$this->query = substr($this->query, 0, -6);
			$this->queryCount = substr($this->queryCount, 0, -6);
		}

	}

	private function setAdminCharacterFilter() {
		$characters = json_decode($this->data['characters'],true);
		$char_list = '';
		if(is_array($characters) && count($characters)) {
			switch($this->data['characterType']) {
				case 'ids':
					foreach($characters as $character) {
						$char_list .= $character->node_value . ",";
					}
					$char_list = substr($this->char_list, 0, -1);
					$this->query .= " AND ia.imageId = I.imageId AND ia.attributeId IN (".$char_list.") ";
					$this->queryCount .= " AND ia.imageId = I.imageId AND ia.attributeId IN (".$char_list.") ";
					// $this->query .= " AND ia.attributeId IN (".$char_list.") ";
					break;
				case 'string':
				default:
					$this->query .= " AND I.`imageId` IN ( SELECT DISTINCT ia.`imageId` FROM `imageAttrib` ia, `imageAttribValue` iav WHERE ia.`attributeId` = iav.`attributeId` AND iav.`name` IN ('".implode("','",$characters)."') ) ";
					$this->queryCount .= " AND I.`imageId` IN ( SELECT DISTINCT ia.`imageId` FROM `imageAttrib` ia, `imageAttribValue` iav WHERE ia.`attributeId` = iav.`attributeId` AND iav.`name` IN ('".implode("','",$characters)."') ) ";
					// $this->query .= " AND ia.`imageId` = I.`imageId` AND ia.`attributeId` = iav.`attributeId` AND iav.`name` IN ('".implode("','",$characters)."') ";
					// $this->query .= " AND iav.`name` IN ('".implode("','",$characters)."') ";
					break;
			}
		}
	}

	private function setGroupFilter() {
		$characters = $this->data['characters'];
		if (($characters != '') && ($characters != '[]')) {
	 		$this->query .= " GROUP BY I.`imageId` ";
		}
	}

	private function setOrderFilter($mode = 'view_images') {
		$orderBy = '';
		switch($mode) {
			case 'view_images':
				$orderBy .= ' ORDER BY I.`family`, I.`genus`, I.`specificEpithet` ';
				break;
			// case 'group':
				// if(is_array($this->data['group']) && count($this->data['group'])) {
					// foreach($this->data['group'] as $group) {
						// if($group[''])
					// }
				// }
				// break;
			case 'manual':
				if(!in_array($this->data['sort'],array('imageId'))) {
					$orderBy .= sprintf(" ORDER BY LOWER(I.%s) %s ", mysql_escape_string($this->data['sort']),  $this->data['dir']);
				} else {
					$orderBy .= sprintf(" ORDER BY I.%s %s ", mysql_escape_string($this->data['sort']),  $this->data['dir']);
				}
				break;
			case 'gridSort':
				$orderBy .= ' ORDER BY ';
				if(count($this->data['gridSort'])) {
					$ordArray = array();
					foreach($this->data['gridSort'] as $order) {
						if(!in_array($order['property'],array('imageId'))) {
							$ordArray[] = " LOWER(I.{$order['property']}) {$order['direction']} ";
						} else {
							$ordArray[] = " I.{$order['property']} {$order['direction']} ";
						}
					}
					$orderBy .= implode(',',$ordArray);
				}
				break;
			default:
				if(($this->data['sort']!='') && ($this->data['dir']!='')) {
					if(!in_array($this->data['sort'],array('imageId'))) {
						$orderBy .= sprintf(" ORDER BY LOWER(I.%s) %s ", mysql_escape_string($this->data['sort']),  $this->data['dir']);
					} else {
						$orderBy .= sprintf(" ORDER BY I.%s %s ", mysql_escape_string($this->data['sort']),  $this->data['dir']);
					}
				} else if($this->data['order'] != '' && is_array($this->data['order'])) {
					$orderBy .= ' ORDER BY ';
					if(count($this->data['order'])) {
						$ordArray = array();
						foreach($this->data['order'] as $order) {
							if(!in_array($order['field'],array('imageId'))) {
								$ordArray[] = " LOWER(I.{$order['field']}) {$order['dir']} ";
							} else {
								$ordArray[] = " I.{$order['field']} {$order['dir']} ";
							}
						}
						$orderBy .= implode(',',$ordArray);
					}
				}
				break;
		}
		if($this->data['useRating']) {
			$orderBy .= ($orderBy == '') ? ' ORDER BY I.`rating` DESC ' : ', I.`rating` DESC ';
		}
		if($this->data['useStatus']) {
			$orderBy .= ($orderBy == '') ? ' ORDER BY I.`statusType` DESC ' : ', I.`statusType` DESC ';
		}
		$this->query .= $orderBy;
		// echo $this->query;
	}

	private function setLimitFilter() {
		$this->query .= sprintf("  LIMIT %s, %s", mysql_escape_string($this->data['start']),  $this->data['limit']);
	}

	# Image Functions

	# Attribute Functions
	
	public function imageGetAttributeDetails($imageId = '') {
		if($imageId == '' || !is_numeric($imageId) ) return false;
		$query = sprintf("SELECT ia.*, iat.`title` category, iav.`name` attribute FROM `imageAttrib` ia LEFT OUTER JOIN `imageAttribType` iat ON ia.`categoryId` = iat.`categoryId` LEFT OUTER JOIN `imageAttribValue` iav ON ia.`attributeId` = iav.`attributeId` WHERE ia.`imageId` = %s", mysql_escape_string($imageId));
		$ret = $this->db->query_all($query);
		return is_null($ret) ? array() : $ret;
	}
	
	public function imageAttributeAdd() {
		// $imageIds = @explode(',', $this->data['imageId']);
		$categoryId = $this->data['categoryId'];
		$attributeId = $this->data['attributeId'];
		
		$this->lg->logSetProperty('table', 'imageAttrib');
		
		if(is_array($this->data['advFilter']) && count($this->data['advFilter'])) {
			$qry = $this->getByCrazyFilter($this->data['advFilter']);
			$query = " INSERT IGNORE INTO imageAttrib(imageId, categoryId, attributeId) SELECT im.imageId, $categoryId, $attributeId FROM ($qry) im ";
			//echo $query;  exit;
			if($this->db->query($query)) {
				$this->lg->logSetProperty('query', $query);
				$this->lg->logSave();
				return true;
			} else {
				return false;
			}
		} else if(is_array($this->data['imageId']) && count($this->data['imageId'])) {
			foreach($this->data['imageId'] as $id) {
				if($this->imageLoadById($id)) {
					$query = sprintf("INSERT IGNORE INTO imageAttrib(imageId, categoryId, attributeId) VALUES(%s, %s, %s);"
						, mysql_escape_string($id)
						, mysql_escape_string($categoryId)
						, mysql_escape_string($attributeId)
					);
					if($this->db->query($query)) {
						$this->lg->logSetProperty('query', $query);
						$this->lg->logSave();
					}

					// $query = sprintf("INSERT INTO `imageLog` (action, imageId, afterDesc, query, dateCreated) VALUES (10, '%s', 'Cat ID: %s, Attrib ID: %s', '%s', NOW());"
						// , mysql_escape_string($id)
						// , mysql_escape_string($categoryId)
						// , mysql_escape_string($attributeId)
						// , mysql_escape_string($query)
					// );
					// $this->db->query($query);
				}
			}
			return true;
		} else if(is_array($this->data['barcode']) && count($this->data['barcode'])) {
			foreach($this->data['barcode'] as $bcode) {
				if($this->imageLoadByBarcode($bcode)) {
					$query = sprintf("INSERT IGNORE INTO imageAttrib(imageId, categoryId, attributeId) VALUES(%s, %s, %s);"
						, mysql_escape_string($this->imageGetProperty('imageId'))
						, mysql_escape_string($categoryId)
						, mysql_escape_string($attributeId)
					);
					if($this->db->query($query)) {
						$this->lg->logSetProperty('query', $query);
						$this->lg->logSave();
					}
				}
			}
			return true;
		} else {
			$query = " INSERT IGNORE INTO imageAttrib(imageId, categoryId, attributeId) SELECT imageId, $categoryId, $attributeId FROM image ";
			// echo $query;
			if($this->db->query($query)) {
				$this->lg->logSetProperty('query', $query);
				$this->lg->logSave();
			}
		}
	}

	public function imageAttributeDelete() {
		// $imageIds = @explode(',', $this->data['imageId']);
		$imageIds = $this->data['imageId'];
		$attributeId = $this->data['attributeId'];
		$this->lg->logSetProperty('table', 'imageAttrib');
		
		if(is_array($this->data['advFilter']) && count($this->data['advFilter'])) {
			$qry = $this->getByCrazyFilter($this->data['advFilter']);
			$query = sprintf(" DELETE FROM `imageAttrib` WHERE attributeId = %s AND imageId IN (SELECT im.imageId FROM ($qry) im) "
							, mysql_escape_string($attributeId));
			//echo $query;  exit;
			if($this->db->query($query)) {
				$this->lg->logSetProperty('query', $query);
				$this->lg->logSave();
				return true;
			} else {
				return false;
			}
		} else if(is_array($imageIds) && count($imageIds)) {
			$query = sprintf("DELETE FROM `imageAttrib` WHERE attributeId = %s AND imageId IN (%s)"
			, mysql_escape_string($attributeId)
			, @implode(',',$imageIds)
			);
			if($this->db->query($query)) {
				$this->lg->logSetProperty('query', $query);
				$this->lg->logSave();
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function imageAttributeDelete1() {
		// $imageIds = @explode(',', $this->data['imageId']);
		$imageIds = $this->data['imageId'];
		$attributeId = $this->data['attributeId'];
		if(is_array($imageIds) && count($imageIds)) {
			$query = sprintf("DELETE FROM `imageAttrib` WHERE attributeId = %s AND imageId IN (%s)"
			, mysql_escape_string($attributeId)
			, @implode(',',$imageIds)
			);
			if($this->db->query($query)) {
				$this->lg->logSetProperty('table', 'imageAttrib');
				$this->lg->logSetProperty('query', $query);
				$this->lg->logSave();
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function crazyFilter1($filter,$clearFlag = false) {
		global $tables, $conditionArray, $fieldsArray, $querybit, $childcount;
		$str = '';
		if($clearFlag) $tables = array('image');
		switch($filter['node']) {
			case 'group':
				$ar =array();
				if(is_array($filter['children']) && count($filter['children'])) {
					foreach($filter['children'] as $child) {
						$dt = $this->crazyFilter($child);
						// ($dt != '' ) ? $ar[] = $dt : '';
						if($dt != '' ) {
							$ar[] = $dt;
							if($filter['logop'] == 'and') {
								$childcount++;
							}
						}
					}
				}
				if(count($ar)) {
					// $str .= ' ( ' . implode($filter['logop'], $ar) . ' ) ';
					$str .= ' ( ' . implode("or", $ar) . ' ) ';
				}
				break;
			case 'condition':
				switch($filter['object']) {
					case 'attribute':
						$tables[] = 'attribute';
						if($filter['key'] != '' && $filter['value'] != '') {
							switch($filter['condition']) {
								case '=':
									$str .= sprintf(" ( at.`categoryId` = %d && at.`attributeId` %s %d ) " , $filter['key'], $filter['condition'], $filter['value']);
									break;
								case '!=':
									// $str .= sprintf(" ( at.`categoryId` = %d && at.`attributeId` %s %d ) " , $filter['key'], $filter['condition'], $filter['value']);
									if($filter['key'] != '' && $filter['value'] != '') {
										$querybit = sprintf(" AND iav.`categoryId` = %s && iav.`attributeId` = %s ", $filter['key'], $filter['value']);
									}
									$str .= " ( at.`categoryId` IS NULL && at.`attributeId` IS NULL ) ";
									break;
								case 'is':
									$str .= sprintf(" ( at.`categoryId` = %d && at.`name` = '%s' ) " , $filter['key'], $filter['value']);
									break;
								case '%s':
								case 's%':
								case '%s%':
									$op = str_replace('%','%%',$filter['condition']);
									$op = str_replace('s','%s',$op);
									$str .= sprintf(" ( at.`categoryId` = %d && at.`name` LIKE '$op' ) " , $filter['key'], $filter['value']);
									break;
								case 'in':
									$str .= sprintf(" ( at.`categoryId` = %d && at.`name` IN (%s) ) " , $filter['key'], $filter['value']);
									break;
							}
						} else if($filter['key'] == '' && $filter['value'] != '') {
							switch($filter['condition']) {
								case '=':
								case '!=':
									$str .= sprintf(" ( at.`attributeId` %s %d ) ", $filter['condition'], $filter['value']);
									break;
								case 'is':
									$str .= sprintf(" ( at.`attributeId` = '%s' ) ", $filter['value']);
									break;
								case '%s':
								case 's%':
								case '%s%':
									$op = str_replace('%','%%',$filter['condition']);
									$op = str_replace('s','%s',$op);
									$str .= sprintf(" ( at.`name` LIKE '$op' ) ", $filter['value']);
									break;
								case 'in':
									$str .= sprintf(" ( at.`name` IN (%s) ) ", $filter['value']);
									break;
							}
						} else if($filter['key'] != '' && $filter['value'] == '') {
							switch($filter['condition']) {
								case '=':
									$str .= " ( i.`imageId` IN ( SELECT ia.imageId FROM imageAttrib ia WHERE ia.categoryId = {$filter['key']} ) ) ";
									break;
								case '!=':
									$str .= " ( i.`imageId` NOT IN ( SELECT ia.imageId FROM imageAttrib ia WHERE ia.categoryId = {$filter['key']} ) ) ";
									break;
							}
						}
						break;
					case 'collection':
						$str .= sprintf(" ( i.`collectionCode` = '%s' ) ", mysql_escape_string($filter['key'])); 
						break;
					case 'clientStation':
						if(in_array($filter['condition'],array('=','!=')) && $filter['value'] != '' && !is_null($filter['value'])) {
							$str .= sprintf(" ( i.`remoteAccessKey` {$filter['condition']} '%s' ) ", $filter['value']);
						}
						break;
					case 'event':
						$tables[] = 'event';
						if($filter['key'] != '' && $filter['value'] != '') {
							switch($filter['condition']) {
								case '=':
								case '!=':
									$str .= sprintf(" ( ev.`eventTypeId` = %d && ev.`eventId` %s %d ) " , $filter['key'], $filter['condition'], $filter['value']);
									break;
								case 'is':
									$str .= sprintf(" ( ev.`eventTypeId` = %d && ev.`title` = '%s' ) " , $filter['key'], $filter['value']);
									break;
								case '%s':
								case 's%':
								case '%s%':
									$op = str_replace('%','%%',$filter['condition']);
									$op = str_replace('s','%s',$op);
									$str .= sprintf(" ( ev.`eventTypeId` = %d && ev.`title` LIKE '$op' ) " , $filter['key'], $filter['value']);
									break;
								case 'in':
									$str .= sprintf(" ( ev.`eventTypeId` = %d && ev.`title` IN (%s) ) " , $filter['key'], $filter['value']);
									break;
							}
						} else if($filter['key'] == '' && $filter['value'] != '') {
							switch($filter['condition']) {
								case '=':
								case '!=':
									$str .= sprintf(" ( ev.`eventId` %s %d ) ", $filter['condition'], $filter['value']);
									break;
								case 'is':
									$str .= sprintf(" ( ev.`title` = '%s' ) ", $filter['value']);
									break;
								case '%s':
								case 's%':
								case '%s%':
									$op = str_replace('%','%%',$filter['condition']);
									$op = str_replace('s','%s',$op);
									$str .= sprintf(" ( ev.`title` LIKE '$op' ) ", $filter['value']);
									break;
								case 'in':
									$str .= sprintf(" ( ev.`title` IN (%s) ) ", $filter['value']);
									break;
							}
						} else if($filter['key'] != '' && $filter['value'] == '') {
							switch($filter['condition']) {
								case '=':
									$str .= " ( i.`imageId` IN ( SELECT ei.imageId FROM eventImages ei, events e WHERE ei.eventId = e.eventId AND e.eventTypeId = {$filter['key']} ) ) ";
									break;
								case '!=':
									$str .= " ( i.`imageId` NOT IN ( SELECT ei.imageId FROM eventImages ei, events e WHERE ei.eventId = e.eventId AND e.eventTypeId = {$filter['key']} ) ) ";
									break;
							}
						}
						break;
					case 'geography':
					
						break;
						
					case 'time':
						$key = (strtolower($filter['key']) == 'added') ? 'i.`timestampAdded`' : 'i.`timestampModified`';
						if(in_array($filter['condition'],array('=','!=','>','<','>=','<=')) && $filter['value'] != '' && !is_null($filter['value'])) {
							$str .= sprintf(" ($key {$filter['condition']} '%s') ", $filter['value']);
						} else if ($filter['condition'] == 'between' && $filter['value2'] != '' && !is_null($filter['value2'])) {
							$str .= sprintf(" ($key BETWEEN '%s' AND '%s') ", $filter['value'], $filter['value2']);
						}
						break;
				}
				break;
		}
		return $str;
	}

	public function getByCrazyFilter1 ($filter, $totalFlag = false, $ocrFlag = false) {
		global $tables, $querybit, $childcount;
		$querybit = '';$childcount = 0;
		$tables = array();
		// $filter = json_decode($filter,true);
		
		$where = $this->crazyFilter1($filter);
		
		$query = ($totalFlag) ? ' SELECT SQL_CALC_FOUND_ROWS ' : ' SELECT ';
		
		$query .= ' i.`imageId`,i.`filename`,i.`timestampAdded`,i.`timestampModified`, i.`barcode`, i.`width`,i.`height`,i.`family`,i.`genus`,i.`specificEpithet`,i.`flickrPlantId`, i.`flickrModified`,i.`flickrDetails`,i.`picassaPlantId`,i.`picassaModified`, i.`gTileProcessed`,i.`zoomEnabled`,i.`processed`,i.`boxFlag`,i.`ocrFlag`,i.`rating`, i.`author`, i.`copyright`';
		// $query .= ' i.`imageId`,i.`filename`';
		if($ocrFlag) {
			$query .= ',i.`ocrValue`';
		}
		# fields for url computation
		$query .= ',i.`storageDeviceId`,i.`path`';
		$query .= ',i.`nameGeographyFinderFlag`,i.`nameFinderFlag`,i.`nameFinderValue`,i.`scientificName`, i.`collectionCode`, i.`globalUniqueIdentifier` ';
		
		# for child count logic
		if($childcount > 1) {
			$query .= ',count(*) ct ';
		}
		
		$query .= ' FROM `image` i ';
		
		$tables = array_unique($tables);
		if(count($tables)) {
			foreach($tables as $table) {
				switch($table) {
					case 'event':
						$query .=' LEFT OUTER JOIN (SELECT ei.imageId,e.eventId,e.eventTypeId,e.title FROM eventImages ei, events e WHERE ei.eventId = e.eventId) ev ON i.imageId = ev.imageId ';
						break;
					case 'attribute':
						$query .= " LEFT OUTER JOIN (SELECT ia.imageId,iav.attributeId,iav.categoryId,iav.name FROM imageAttrib ia, imageAttribValue iav WHERE ia.attributeId = iav.attributeId $querybit ) at ON i.imageId = at.imageId ";
						break;
					case 'geography':
						// $query .=' LEFT OUTER JOIN (SELECT ei.`imageId`, e.`eventId`, e.`geographyId`, g.`country`, g.`countryIso`, g.`admin0`, g.`admin1`, g.`admin2`, g.`admin3` FROM `eventImages` ei, `events` e, `geography` g WHERE ei.`eventId` = e.`eventId` AND e.`geographyId` = g.`geographyId`) geo ON i.imageId = geo.imageId ';
						break;
				}
			}
		}
	
		$where = ($where != '') ? ' WHERE  0=0 AND ' . $where : '';
		$query = $query . $where;
		$query .= ' GROUP BY i.`imageId` ';
		if($childcount > 1) {
			$query .= sprintf(" HAVING ct = %s ", $childcount);
		}
		echo $query;//exit;
		return $query;
	
	}

	public function crazyFilter($filter,$clearFlag = false) {
		$str = '';
		if($clearFlag) $tables = array('image');
		switch($filter['node']) {
			case 'group':
				$ar = array();
				if(is_array($filter['children']) && count($filter['children'])) {
					foreach($filter['children'] as $child) {
						$dt = $this->crazyFilter($child);
						($dt != '' ) ? $ar[] = $dt : '';
					}
				}
				if(count($ar)) {
					$str .= ' ( ' . implode($filter['logop'], $ar) . ' ) ';
				}
				break;
			case 'condition':
				switch($filter['object']) {
					case 'attribute':
						if($filter['key'] != '' && $filter['value'] == '') {
							switch($filter['condition']) {
								case '=':
									$str .= sprintf(" ( find_in_set(%d, categories) > 0 ) " , $filter['key']);
									break;
								case '!=':
									$str .= sprintf(" ( find_in_set(%d, categories) = 0 || find_in_set(%d, categories) is null ) " , $filter['key'] , $filter['key']);
									break;
							}
						} else if($filter['value'] != '') {
							switch($filter['condition']) {
								case '=':
									$str .= sprintf(" ( find_in_set(%d, attributes) > 0 ) " , $filter['value']);
									break;
								case '!=':
									$str .= sprintf(" ( find_in_set(%d, attributes) = 0 || find_in_set(%d, attributes) is null ) " , $filter['value'], $filter['value']);
									break;
							}
						}
						break;
					case 'collection':
						$str .= sprintf(" ( `collectionCode` = '%s' ) ", mysql_escape_string($filter['value'])); 
						break;
					case 'clientStation':
						if(in_array($filter['condition'],array('=','!=')) && $filter['value'] != '' && !is_null($filter['value'])) {
							$str .= sprintf(" ( `remoteAccessKey` {$filter['condition']} '%s' ) ", $filter['value']);
						}
						break;
					case 'event':
						if($filter['value'] != '') {
							switch($filter['condition']) {
								case '=':
									$str .= sprintf(" ( find_in_set(%d, events) > 0 ) " , $filter['value']);
									break;
								case '!=':
									$str .= sprintf(" ( find_in_set(%d, events) = 0 || find_in_set(%d, events) is null ) " , $filter['value'] , $filter['value']);
									break;
							}
						}
						break;
					case 'geography':
					
						break;
						
					case 'time':
						$key = (strtolower($filter['key']) == 'added') ? '`timestampAdded`' : '`timestampModified`';
						if(in_array($filter['condition'],array('=','!=','>','<','>=','<=')) && $filter['value'] != '' && !is_null($filter['value'])) {
							$str .= sprintf(" ($key {$filter['condition']} '%s') ", $filter['value']);
						} else if ($filter['condition'] == 'between' && $filter['value2'] != '' && !is_null($filter['value2'])) {
							$str .= sprintf(" ($key BETWEEN '%s' AND '%s') ", $filter['value'], $filter['value2']);
						}
						break;
				}
				break;
		}
		return $str;
	}

	public function getByCrazyFilter ($filter, $totalFlag = false, $ocrFlag = false) {
		global $tables, $querybit, $childcount;
		$querybit = '';$childcount = 0;
		$tables = array();
		// $filter = json_decode($filter,true);
		
		$where = $this->crazyFilter($filter);
		
		$query = ($totalFlag) ? ' SELECT SQL_CALC_FOUND_ROWS ' : ' SELECT ';
		
		$query .= ' `imageId`,`filename`,`timestampAdded`,`timestampModified`, `barcode`, `width`,`height`,`family`,`genus`,`specificEpithet`,`flickrPlantId`, `flickrModified`,`flickrDetails`,`picassaPlantId`,`picassaModified`, `gTileProcessed`,`zoomEnabled`,`processed`,`boxFlag`,`ocrFlag`,`rating`, `author`, `copyright`';
		// $query .= ' `imageId`,`filename`';
		if($ocrFlag) {
			$query .= ',`ocrValue`';
		}
		# fields for url computation
		$query .= ',`storageDeviceId`,`path`';
		$query .= ',`nameGeographyFinderFlag`,`nameFinderFlag`,`nameFinderValue`,`scientificName`, `collectionCode`, `globalUniqueIdentifier` ';
		
		# for child count logic
		if($childcount > 1) {
			$query .= ',count(*) ct ';
		}
		
		$query .= ' FROM `imageWithAttribEvent` I ';
	
		$where = ($where != '') ? ' WHERE  0=0 AND ' . $where : '';
		$query = $query . $where;
		if($childcount > 1) {
			$query .= sprintf(" HAVING ct = %s ", $childcount);
		}
		//echo $query;//exit;
		return $query;
	
	}
	
	public function imageListAttributes($queryFlag = true) {
		if($this->data['code'] != '') {
			if($this->data['showNames']) {
				$query = sprintf(" SELECT iav.* FROM `imageAttribValue` iav, `image` i, `imageAttrib` ia WHERE 1=1 AND i.`imageId` = ia.`imageId` AND ia.`attributeId` = iav.`attributeId` AND i.`collectionCode` LIKE '%s%%' ", mysql_escape_string($this->data['code']));
			} else {
				$query = sprintf(" SELECT iav.*, iat.title  FROM `imageAttribValue` iav, `image` i, `imageAttrib` ia, `imageAttribType` iat WHERE 1=1 AND i.`imageId` = ia.`imageId` AND ia.`attributeId` = iav.`attributeId` AND iav.`categoryId` = iat.`categoryId` AND i.`collectionCode` LIKE '%s%%' ", mysql_escape_string($this->data['code']));
			}
		} else {
			if($this->data['showNames']) {
				$query = ' SELECT iav.* FROM `imageAttribValue` iav WHERE 1=1 ';
			} else {
				$query = ' SELECT iav.*, iat.title FROM `imageAttribValue` iav, `imageAttribType` iat WHERE iav.`categoryId` = iat.`categoryId` ';
			}
		}
		if(is_array($this->data['categoryId']) && count($this->data['categoryId'])) {
			$query .= sprintf(" AND iav.`categoryId` IN (%s) ", implode(',',$this->data['categoryId']));
		} else if($this->data['categoryId'] != '' && is_numeric($this->data['categoryId'])) {
			$query .= sprintf(" AND iav.`categoryId` = %s ", mysql_escape_string($this->data['categoryId']));
		}
		
		if($this->data['value'] != '') {
			switch($this->data['searchFormat']) {
				case 'exact':
					$query .= sprintf(" AND iav.`name` = '%s' ", mysql_escape_string($this->data['value']));
					break;
				case 'left':
					$query .= sprintf(" AND iav.`name` LIKE '%s%%' ", mysql_escape_string($this->data['value']));
					break;
				case 'right':
					$query .= sprintf(" AND iav.`name` LIKE '%%%s' ", mysql_escape_string($this->data['value']));
					break;
				case 'both':
				default:
					$query .= sprintf(" AND iav.`name` LIKE '%%%s%%' ", mysql_escape_string($this->data['value']));
					break;
			}
		}
		$queryCount = str_replace('iav.*', 'count(*) ct', $query);

		$countRet = $this->db->query_one( $queryCount );
		if ($countRet != NULL) {
			$this->total = $countRet->ct;
		}
		
		if($this->data['group'] != '' && in_array($this->data['group'], array('attributeId','name','categoryId','title')) && $this->data['dir'] != '' && !$this->data['showNames']) {
			$query .= build_order( array(array('field' => (($this->data['group'] == 'title') ? 'iat.' . $this->data['group'] :  'iav.' . $this->data['group']), 'dir' => $this->data['dir'])), array('attributeId','categoryId'));
		} else {
			$query .= ' ORDER BY iav.`categoryId`, LOWER(iav.`name`) ';
		}
		
		if($this->data['start'] != '' && $this->data['limit'] != '') {
			$query .= sprintf(" LIMIT %s, %s ", $this->data['start'], $this->data['limit']);
		}
		// die($query);
		if($queryFlag) {
			return $this->db->query($query);
		} else {
			return $this->db->query_all($query);
		}
	}
	
	public function imageGetAttributes($imageId) {
		$query = sprintf("SELECT ia.categoryId iaTID, ia.attributeId iaVID, iat.title iatTitle, iav.name iavValue FROM imageAttrib ia LEFT OUTER JOIN imageAttribType iat ON ( ia.categoryId = iat.categoryId ) JOIN imageAttribValue iav ON (iav.attributeId = ia.attributeId AND ia.imageId = '%s' ) ORDER BY ia.categoryId", mysql_escape_string($imageId));
		$records = $this->db->query_all($query);
		if(count($records)) {
			$prevID = 0;
			foreach($records as $record) {
				if($prevID != $record->iaTID) {
					$prevID = $record->iaTID;
					if(isset($tmpArray3)) {
						$tmpArray1['values'] = $tmpArray3;
						$data[] = $tmpArray1;
						unset($tmpArray3);
					}
					$tmpArray1['id'] = $record->iaTID;
					$tmpArray1['key'] = $record->iatTitle;
				}
				$tmpArray2['id'] = $record->iaVID;
				$tmpArray2['value'] = $record->iavValue;
				$tmpArray3[] = $tmpArray2;
			}
			$tmpArray1['values'] = $tmpArray3;
			$data[] = $tmpArray1;
			return $data;
		} else {
			return false;
		}
	}

	public function imageLoadNodeCharacters() {
		unset($this->records);
		$this->nodes = array();
		$this->query = '';
		$this->cache = false;
	
		if(isset($this->data['nodeApi'])) {
			switch(@strtolower($this->data['nodeApi'])) {
			case "root":
				$parent = '';
	
				$this->query = "SELECT DISTINCT  it.categoryId, it.title, iv.attributeId, iv.name FROM imageAttribType it, imageAttribValue iv, imageAttrib ia WHERE it.categoryId = iv.categoryId AND ia.attributeId = iv.attributeId ORDER BY it.title, LOWER(iv.name);";
				$records = $this->db->query_all($this->query);
				if(count($records)) {
					foreach($records as $record) {
						if ($parent != $record->title && $parent != '') {
						$this->nodes[] = array('text'=>$old_title, 'nodeApi'=>'cateogry', 'iconCls'=>'icon_folder_picture', 'cls'=>'tree_panel', 'nodeValue'=>$record->categoryId, 'children'=>$children);
						$children = '';
						}
						$children[] = array('id'=>'char_' . $record->attributeId, 'id'=>'char_' . $record->attributeId, 'text'=>$record->name, 'nodeApi'=>'character', 'checked'=>false, 'leaf'=>true, 'nodeValue'=>$record->attributeId);
						
						if ($parent != $record->title) {
							$parent = $record->title;
						}
						
						$old_title = $record->title;
					}
				}
				$this->nodes[] = array('text'=>$old_title, 'nodeApi'=>'cateogry', 'iconCls'=>'icon_folder_picture', 'cls'=>'tree_panel', 'nodeValue'=>$record->categoryId, 'children'=>$children);
		
				break;
			}
			return $this->nodes;
		} else {
			return false;
		}
	}

	public function imageLoadNodeImages() {
	
		unset($this->records);
		$this->nodes = array();
		$this->query = '';
		if(isset($this->data['nodeApi'])) {
		switch(@strtolower($this->data['nodeApi'])) {
	
		case "root":
			$children='';
			for ($i=65;$i<91;$i++) {
				$tmp = chr($i);
				$children[] = array('text'=>$tmp, 'iconCls'=>'icon_folder_picture', 'nodeApi'=>'families', 'nodeValue'=>$tmp);
			}
			
			$this->nodes[] = array('text'=>"by family", 'iconCls'=>'icon_folder_picture', 'expanded'=>false, 'nodeApi'=>'alpha', 'nodeValue'=>'alpha', 'children'=> $children);
	
			$children='';
			for ($i=65;$i<91;$i++) {
				$tmp = chr($i);
				$children[] = array('text'=>$tmp, 'iconCls'=>'icon_folder_picture', 'nodeApi'=>'genera', 'nodeValue'=>$tmp);
			}
	
			$this->nodes[] = array('text'=>"by genus", 'iconCls'=>'icon_folder_picture', 'expanded'=>false, 'nodeApi'=>'alpha', 'nodeValue'=>'alpha', 'children'=> $children);
	
			break;
	
		case "alpha":
			for ($i=65;$i<91;$i++) {
				$tmp = chr($i);
			}
			$this->nodes[] = array('text'=>$tmp, 'iconCls'=>'icon_folder_picture', 'draggable'=>false, 'nodeApi'=>'families', 'nodeValue'=>$tmp);
			break;
	
		case "families":
	
			$this->query = sprintf( "SELECT family, count(family) as familySize FROM image WHERE family like '%s%%' GROUP by family ORDER by LOWER(family) ", mysql_escape_string($this->data['nodeValue']) );
	
			try {
				$records = $this->db->query_all($this->query);
			} catch (Exception $e) {
				trigger_error($e->getMessage(),E_USER_ERROR);
			}
	
			if(count($records)) {
				foreach($records as $record) {
					$this->nodes[] = array('text'=>$record->family . " (" . number_format($record->familySize) . ")", 'imageCount' => $record->familySize, 'family'=>$record->family, 'iconCls'=>'icon_picture', 'checked'=>false, 'nodeApi'=>'family', 'nodeValue'=>$record->family);
	
				}
			}
			break;
	
		case "family":
				
			if( trim($this->data['nodeValue']) == '' ) {
				$this->query = "SELECT genus, count(genus) as genusSize FROM image GROUP by genus ORDER by genus";
			} else {
				$this->query = sprintf( "SELECT genus, count(genus) as genusSize FROM image WHERE family = '%s' GROUP by genus ORDER by genus ", mysql_escape_string($this->data['nodeValue']) );
			}
	
			try {
				$records = $this->db->query_all($this->query);
			} catch (Exception $e) {
				trigger_error($e->getMessage(),E_USER_ERROR);
			}
	
			if(count($records)) {
				foreach($records as $record) {
					$this->nodes[] = array('text'=>$record->genus . " (" . $record->genusSize . ")", 'imageCount' => $record->genusSize, 'id'=>$record->genus, 'family'=>$this->data['family'], 'genus'=>$record->genus, 'iconCls'=>'icon_picture', 'checked'=>false, 'draggable'=>false, 'isTarget'=>false, 'nodeApi'=>'genus', 'nodeValue'=>$record->genus);
	
				}
			}
			break;
	
		case "genera":
			$this->query = sprintf( "SELECT genus, count(genus) as genusSize FROM image WHERE genus like '%s%%' GROUP by genus ORDER by genus", mysql_escape_string($this->data['nodeValue']) );
			try {
				$records = $this->db->query_all($this->query);
			} catch (Exception $e) {
				trigger_error($e->getMessage(),E_USER_ERROR);
			}
	
			if(count($records)) {
				foreach($records as $record) {
					$this->nodes[] = array('text'=>$record->genus . " (" . number_format($record->genusSize) . ")", 'imageCount' => $record->genusSize, 'genus'=>$record->genus, 'iconCls'=>'icon_picture', 'checked'=>false, 'nodeApi'=>'genus', 'nodeValue'=>$record->genus);
	
				}
			}
			break;
			
		case "genus":
				
			if( trim($this->data['nodeValue']) == '' ) {
				$this->query = "SELECT specificEpithet, count(specificEpithet) as speciesSize FROM image GROUP by specificEpithet ORDER by specificEpithet";
			} else {
				$this->query = sprintf( "SELECT specificEpithet, count(specificEpithet) as speciesSize FROM image WHERE genus = '%s' GROUP by specificEpithet ORDER by specificEpithet", mysql_escape_string($this->data['nodeValue']) );
			}
	
			try {
				$records = $this->db->query_all($this->query);
			} catch (Exception $e) {
				trigger_error($e->getMessage(),E_USER_ERROR);
			}
	
			if(count($records)) {
				foreach($records as $record) {
					$this->nodes[] = array('text'=>$record->specificEpithet . " (" . $record->speciesSize . ")", 'imageCount' => $record->speciesSize, 'id'=>$record->specificEpithet, 'family'=>$this->data['family'], 'genus'=>$this->data['genus'], 'species'=>$record->specificEpithet, 'iconCls'=>'icon_picture', 'checked'=>false, 'leaf'=>true, 'draggable'=>false, 'isTarget'=>false, 'nodeApi'=>'species', 'nodeValue'=>$record->specificEpithet, 'genus'=>$this->data['nodeValue']);
	
				}
			}
			break;
	
		case 'scientificname':
				
			if( trim($this->data['nodeValue']) == '' ) {
				$this->query = "SELECT concat(genus, ' ', specificEpithet) as name, count(specificEpithet) as sz, family, genus, specificEpithet  FROM image  GROUP by genus, specificEpithet ORDER by genus, specificEpithet";
			} else {
				$this->query = sprintf( "SELECT concat(genus, ' ', specificEpithet) as name, count(specificEpithet) as sz, family, genus, specificEpithet  FROM image WHERE family = '%s'  GROUP by genus, specificEpithet ORDER by genus, specificEpithet", mysql_escape_string($this->data['nodeValue']) );
			}
	
			try {
				$records = $this->db->query_all($this->query);
			} catch (Exception $e) {
				trigger_error($e->getMessage(),E_USER_ERROR);
			}
	
			if(count($records)) {
				foreach($records as $record) {
					$this->nodes[] = array('text'=>$record->name . " (" . $record->sz . ")", 'imageCount' => $record->sz, 'id'=>$record->name, 'family'=>$record->family, 'genus'=>$record->genus, 'species'=>$record->specificEpithet, 'iconCls'=>'icon_picture', 'checked'=>false, 'leaf'=>true, 'draggable'=>false, 'isTarget'=>false, 'nodeApi'=>'scientificName', 'nodeValue'=>$record->name);
				}
			}
	
			break;
	
		}
			return $this->nodes;
		} else {
			return false;
		}
	
	}

	public function imageLoadCharacterList() {
		unset($this->records);
		$this->query = "SELECT I.imageId";
		$this->setFilters();

		if (($this->data['characters'] != '') && ($this->data['characters'] != '[]')) {
			$this->query .= " GROUP BY I.imageId HAVING sz >= " . ( $this->char_count - 1 );
		}

		$this->query = "SELECT attributeId as id FROM imageAttrib t1 INNER JOIN  (" . $this->query . ") AS t2 ON t1.imageId = t2.imageId GROUP BY t1.attributeId ORDER BY t1.attributeId;";
		try {
			$this->records = $this->db->query_all($this->query);
		} catch (Exception $e) {
			trigger_error($e->getMessage(),E_USER_ERROR);
		}

		if(!count($this->records)) {
			$this->records = array();
		}
		return $this->records;
	}

	public function imageDetails() {
		global $config;
		$ret = array();
		unset($this->records);
		$this->nodes = array();
		$this->query = '';
		if($this->imageFieldExists($this->data['imageId'])) {
			$query = sprintf("SELECT IAT.title as attrib, IAV.name as value FROM imageAttrib IA, imageAttribType IAT, imageAttribValue IAV WHERE IA.categoryId = IAT.categoryId AND IA.attributeId = IAV.attributeId AND IA.imageId = %s ORDER BY IAT.title, IAV.name", mysql_escape_string($this->data['imageId']));
	
			try {
				$records = $this->db->query_all($query);
			} catch (Exception $e) {
				trigger_error($e->getMessage(),E_USER_ERROR);
			}
	
			$attrib = '';
			if(count($records)) {
				foreach ($records as $record) {
					if ($attrib != $record->attrib) {
						if ($attrib != '') {
							$attributes[] = array('attrib'=>$attrib, 'value'=> substr($values, 0,-2));
							$values = '';
						}
						$attrib = $record->attrib;
					}
					$values .= $record->value . ", ";
				}
			}
			if ($attrib != '') {
				$attributes[] = array('attrib'=>$attrib, 'value'=> substr($values, 0,-2));
			} else {
				$attributes[] = array('attrib'=>'Note', 'value'=>'This image has not been tagged.');
			}
			unset($record);
	
			$this->imageLoadById($this->data['imageId']);
			$barcode = $this->imageGetName();
			$path = $config['path']['images'] . $this->imageBarcodePath( $barcode ) . $this->imageGetProperty('filename');
			$record = $this->record;
			$record['attributes'] = $attributes;
			$record['exif'] = @exif_read_data( $path );
			$ret['status'] = true;
			$ret['record'] = $record;
		} else {
			$ret['status'] = false;
			$ret['error'] = 170;
		}
		return $ret;
	}
	
	public function imageGetUrl($imageId) {
		$this->imageLoadById($imageId);
		$storage = new StorageDevice($this->db);
		$device = $storage->storageDeviceGet($this->imageGetProperty('storageDeviceId'));
		$url['url'] = $device['baseUrl'];
		switch(strtolower($device['type'])) {
			case 's3':
				$tmp = $this->imageGetProperty('path');
				$tmp = substr($tmp, 0, 1)=='/' ? substr($tmp, 1, strlen($tmp)-1) : $tmp;
				$url['baseUrl'] = $url['url'] . rtrim($tmp,'/') . '/';
				$url['url'].= rtrim($tmp,'/') . '/' . $this->imageGetProperty('filename');
				break;
			case 'local':
				if(substr($url['url'], strlen($url['url'])-1, 1) == '/') {
					$url['url'] = substr($url['url'],0,strlen($url['url'])-1);
				}
				$url['baseUrl'] = $url['url'] . rtrim($this->imageGetProperty('path'),'/') . '/';
				$url['url'].= rtrim($this->imageGetProperty('path'),'/'). '/' .$this->imageGetProperty('filename');
				break;
		}
		$url['filename'] = $this->imageGetProperty('filename');
		return $url;
	}
	
	public function imageExists($storageDeviceId, $imagePath, $filename) {
		$storage = new StorageDevice($this->db);
		$device = $storage->storageDeviceGet($storageDeviceId);
		$path = $device['baseUrl'];
		switch(strtolower($device['type'])) {
			case 's3':
				$tmp = $imagePath;
				$tmp = substr($tmp, 0, 1)=='/' ? substr($tmp, 1, strlen($tmp)-1) : $tmp;
				$path.= rtrim($tmp,"/") . '/' . $filename;
				break;
			case 'local':
				if(substr($path, strlen($path)-1, 1) == '/' ) {
					$path = substr($path, 0, strlen($path)-1);
				}
				$path.= rtrim($imagePath,"/") . '/' . $filename;
				break;
		}
		// $path = str_replace(' ', '+', $path);
		$path = str_replace(' ', '%20', $path);
		$f = @fopen($path, "r");
		if($f) {
			fclose($f);
			return true;
		} else {
			return false;
		}
	}

	public function getNonENProcessedRecords($filter='') {
		if($filter['collection']=='')
			$query = " SELECT * FROM `image` WHERE `barcode` NOT IN (SELECT `barcode` from `specimen2Label`) ";
		else
			$query = sprintf(" SELECT * FROM `image` WHERE `barcode` NOT IN (SELECT `barcode` from `specimen2Label`) AND `collectionCode` = '%s' ", mysql_escape_string($filter['collection']) );
		if(trim($filter['start']) != '' && trim($filter['limit']) != '') {
			$query .= build_limit(trim($filter['start']),trim($filter['limit']));
		}
		return ($this->db->query($query));
	}
	
	public function imageAddRating($imageId = '', $rating = '') {
		if($imageId == '' ||  $rating == '') return false;
		$query = sprintf(" UPDATE `image` SET `rating` = '%s' WHERE `imageId` = '%s'; ", mysql_escape_string($rating), mysql_escape_string($imageId));
		return ($this->db->query($query)) ? true : false;
	}
}

class ImageAttribType
{
	public $db, $record;
	
	public function __construct($db = null) {
		$this->db = $db;
		$this->lg = new LogClass($db);
	}
	
	public function imageCategorySetData($data) {
		$this->data = $data;
		return(true);
	}
	
	public function imageCategoryGetProperty( $field ) {
		if (isset($this->record[$field])) {
			return( $this->record[$field] );
		} else {
			return(false);
		}
	}
	
	public function imageCategorySetProperty( $field, $value ) {
		$this->record[$field] = $value;
		return(true);
	}
	
	public function imageCategoryLoadById( $categoryId ) {
		if($categoryId == '') return false;
		$query = sprintf("SELECT * FROM `imageAttribType` WHERE `categoryId` = %s", mysql_escape_string($categoryId) );
		try {
		$ret = $this->db->query_one( $query );
		} catch (Exception $e) {
			trigger_error($e->getMessage(),E_USER_ERROR);
		}
		if ($ret != NULL) {
			foreach( $ret as $field => $value ) {
				$this->imageCategorySetProperty($field, $value);
			}
			return(true);
		} else {
			return(false);
		}
	}
	
	public function imageCategoryExists($categoryId) {
		$query = sprintf("SELECT * FROM `imageAttribType` WHERE `categoryId` = '%s'", mysql_escape_string($categoryId));
		$records = $this->db->query_all($query);
		if(count($records)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function imageCategoryTitleExists($title) {
		$query = sprintf("SELECT * FROM `imageAttribType` WHERE `title` = '%s'", mysql_escape_string($title));
		$records = $this->db->query_all($query);
		if(count($records)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function imageCategoryAdd() {
		$query = sprintf(" INSERT INTO `imageAttribType` SET `title` = '%s', `description` = '%s', `elementSet` = '%s', `term` = '%s' "
					, mysql_escape_string($this->imageCategoryGetProperty('title'))
					, mysql_escape_string($this->imageCategoryGetProperty('description'))
					, mysql_escape_string($this->imageCategoryGetProperty('elementSet'))
					, mysql_escape_string($this->imageCategoryGetProperty('term'))
					);

		if($this->db->query($query)) {
			$id = $this->db->insert_id;
			$this->lg->logSetProperty('table', 'imageAttribType');
			$this->lg->logSetProperty('query', $query);
			$this->lg->logSave();
			
			// $query1 = sprintf("INSERT INTO `imageLog` (action, afterDesc, query, dateCreated) VALUES (4, 'categoryId: %s, value: %s', '%s', NOW())"
				// , mysql_escape_string($id)
				// , mysql_escape_string($this->imageCategoryGetProperty('title'))
				// , mysql_escape_string($query)
				// );
			// $this->db->query($query1);
			return( $id );
		}
		return false;
	}

	public function imageCategoryUpdate() {
		$query = sprintf(" UPDATE `imageAttribType` SET `title` = '%s', `description` = '%s', `elementSet` = '%s', `term` = '%s' WHERE categoryId = %s "
			, mysql_escape_string($this->imageCategoryGetProperty('title'))
			, mysql_escape_string($this->imageCategoryGetProperty('description'))
			, mysql_escape_string($this->imageCategoryGetProperty('elementSet'))
			, mysql_escape_string($this->imageCategoryGetProperty('term'))
			, mysql_escape_string($this->imageCategoryGetProperty('categoryId'))
			);
		if($this->db->query($query)) {
			$this->lg->logSetProperty('table', 'imageAttribType');
			$this->lg->logSetProperty('query', $query);
			$this->lg->logSave();
			// $this->db->query(sprintf("INSERT INTO `imageLog` (action, afterDesc, query, dateCreated) VALUES (5, 'categoryId: %s, value: %s', '%s', NOW())"
			// , mysql_escape_string($this->imageCategoryGetProperty('categoryId'))
			// , mysql_escape_string($this->imageCategoryGetProperty('title'))
			// , mysql_escape_string($query)
			// ));
			return true;
		}
		return false;
	}

	public function imageCategoryDelete($categoryId) {
		if($categoryId == '') return false;
		$query1 = '';
		$query = sprintf("DELETE FROM `imageAttrib` WHERE categoryId = %s;", mysql_escape_string($categoryId));
		$this->db->query($query);
		$query1 .= $query;
		$query = sprintf("DELETE FROM `imageAttribValue` WHERE categoryId = %s;", mysql_escape_string($categoryId));
		$this->db->query($query);
		$query1 .= $query;
		$query = sprintf("DELETE FROM `imageAttribType` WHERE categoryId = %s;", mysql_escape_string($categoryId));
		$this->db->query($query);
		$query1 .= $query;

		$this->lg->logSetProperty('table', 'imageAttribType');
		$this->lg->logSetProperty('query', $query1);
		$this->lg->logSave();
		return true;
		
		// $query2 = sprintf("INSERT INTO `imageLog` (action, afterDesc, query, dateCreated) VALUES (6, 'Category ID: %s', '%s', NOW())", mysql_escape_string($categoryId), mysql_escape_string($query1));
		// if($this->db->query($query2)) {
			// return true;
		// } else {
			// return false;
		// }
	}
	
	public function imageCategoryList() {
		$query = "SELECT SQL_CALC_FOUND_ROWS * FROM `imageAttribType` WHERE 1=1 ";
		
		if(is_array($this->data['categoryId']) && count($this->data['categoryId'])) {
			$query .= sprintf(" AND `categoryId` IN (%s) ", implode(',',$this->data['categoryId']));
		} else if($this->data['categoryId'] != '' && is_numeric($this->data['categoryId'])) {
			$query .= sprintf(" AND `categoryId` = %s ", mysql_escape_string($this->data['categoryId']));
		}
		
		if($this->data['value'] != '') {
			switch($this->data['searchFormat']) {
				case 'exact':
					$query .= sprintf(" AND `title` = '%s' ", mysql_escape_string($this->data['value']));
					break;
				case 'left':
					$query .= sprintf(" AND `title` LIKE '%s%%' ", mysql_escape_string($this->data['value']));
					break;
				case 'right':
					$query .= sprintf(" AND `title` LIKE '%%%s' ", mysql_escape_string($this->data['value']));
					break;
				case 'both':
				default:
					$query .= sprintf(" AND `title` LIKE '%%%s%%' ", mysql_escape_string($this->data['value']));
					break;
			}
		}
		if($this->data['group'] != '' && in_array($this->data['group'], array('categoryId','title','description','elementSet','term')) && $this->data['dir'] != '') {
			$query .= build_order( array(array('field' => $this->data['group'], 'dir' => $this->data['dir'])), array('categoryId'));
		} else {
			$query .= ' ORDER BY `categoryId`, LOWER(`title`) ';
		}
		if($this->data['start'] != '' && $this->data['limit'] != '') {
			$query .= sprintf(" LIMIT %s, %s ", $this->data['start'], $this->data['limit']);
		}
		try {
			$records = $this->db->query_all($query);
		} catch (Exception $e) {
			trigger_error($e->getMessage(),E_USER_ERROR);
		}
		return $records;
	}

	public function imageCategoryGetBy($category, $categoryType) {
		if(!@in_array($categoryType,array('categoryId','title','term'))) return false;
		if($categoryType == 'categoryId') {
			return $this->imageCategoryExists($category) ? $category : false; 
		}
		$ret = $this->db->query_one( sprintf(" SELECT `categoryId` FROM `imageAttribType` WHERE `%s` = '%s' ", mysql_escape_string($categoryType), mysql_escape_string($category)) );
		return ($ret == NULL) ? false : $ret->categoryId;
	}

	public function imageMetaDataPackageImport($data) {
		if(!is_array($data)) return false;
		$query = sprintf("INSERT IGNORE INTO `imageAttribType` SET `title` = '%s', `description` = '%s', `elementSet` = '%s', `term` = '%s'"
				, mysql_escape_string($data[2])
				, mysql_escape_string($data[3])
				, mysql_escape_string($data[0])
				, mysql_escape_string($data[1])
				);
		if($this->db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

}

class ImageAttribValue
{
	public $db, $record;
	
	public function __construct($db = null) {
		$this->db = $db;
		$this->lg = new LogClass($db);
	}
	
	public function imageAttributeSetData($data) {
		$this->data = $data;
		return(true);
	}
	
	public function imageAttributeGetProperty( $field ) {
		if (isset($this->record[$field])) {
			return( $this->record[$field] );
		} else {
			return(false);
		}
	}
	
	public function imageAttributeSetProperty( $field, $value ) {
		$this->record[$field] = $value;
		return(true);
	}
	
	public function imageAttributeLoadById( $attributeId ) {
		if($attributeId == '') return false;
		$query = sprintf("SELECT * FROM `imageAttribValue` WHERE `attributeId` = %s", mysql_escape_string($attributeId) );
		try {
		$ret = $this->db->query_one( $query );
		} catch (Exception $e) {
			trigger_error($e->getMessage(),E_USER_ERROR);
		}
		if ($ret != NULL) {
			foreach( $ret as $field => $value ) {
				$this->imageAttributeSetProperty($field, $value);
			}
			return(true);
		} else {
			return(false);
		}
	}
	
	public function imageAttributeExists($attributeId) {
		$query = sprintf("SELECT count(*) ct FROM `imageAttribValue` WHERE `attributeId` = '%s'", mysql_escape_string($attributeId));
		$ret = $this->db->query_one($query);
		return (is_object($ret) && $ret->ct) ? true : false;
	}
	
	public function imageAttributeNameExists($name, $categoryId) {
		$query = sprintf("SELECT count(*) ct FROM `imageAttribValue` WHERE `name` = '%s' AND `categoryId` = '%s'", mysql_escape_string($name),mysql_escape_string($categoryId));
		$ret = $this->db->query_one($query);
		return (is_object($ret) && $ret->ct) ? true : false;
	}

	public function imageAttributeDelete($attributeId) {
		$query1 = '';
		$query = sprintf("DELETE FROM `imageAttrib` WHERE attributeId = %s;", mysql_escape_string($attributeId));
		$this->db->query($query);
		$query1 .= $query;
		$query = sprintf("DELETE FROM `imageAttribValue` WHERE attributeId = %s", mysql_escape_string($attributeId));
		$this->db->query($query);		
		$query1 .= $query;
		
		$this->lg->logSetProperty('table', 'imageAttrib');
		$this->lg->logSetProperty('query', $query1);
		$this->lg->logSave();

		return true;
		
		// $query = sprintf("INSERT INTO `imageLog` (action, afterDesc, query, dateCreated) VALUES (9, 'attributeId: %s', '%s', NOW())", mysql_escape_string($attributeId), $query1);
		// if($this->db->query($query)) {
			// return true;
		// } else {
			// return false;
		// }
	}
	
	public function imageAttributeAdd() {
		$query = sprintf(" INSERT IGNORE INTO `imageAttribValue` SET `name` = '%s', `categoryId` = '%s' "
					, mysql_escape_string($this->imageAttributeGetProperty('name'))
					, mysql_escape_string($this->imageAttributeGetProperty('categoryId'))
					);

		if($this->db->query($query)) {
			$id = $this->db->insert_id;
			
			$this->lg->logSetProperty('table', 'imageAttribValue');
			$this->lg->logSetProperty('query', $query);
			$this->lg->logSave();
			
			// $query1 = sprintf("INSERT INTO `imageLog` (action, afterDesc, query, dateCreated) VALUES (7, 'attributeId: %s, value: %s, categoryId: %s', '%s', NOW())"
			// , mysql_escape_string($id)
			// , mysql_escape_string($this->data['name'])
			// , mysql_escape_string($this->data['categoryId'])
			// , mysql_escape_string($query)
			// );
			// $this->db->query($query1);
			
			return( $id );
		}
		return false;
	}

	public function imageAttributeUpdate() {
		$query = sprintf(" UPDATE `imageAttribValue` SET `name` = '%s', `categoryId` = %s WHERE `attributeId` = %s "
			, mysql_escape_string($this->imageAttributeGetProperty('name'))
			, mysql_escape_string($this->imageAttributeGetProperty('categoryId'))
			, mysql_escape_string($this->imageAttributeGetProperty('attributeId'))
			);
		if($this->db->query($query)) {
			
			$this->lg->logSetProperty('table', 'imageAttribValue');
			$this->lg->logSetProperty('query', $query);
			$this->lg->logSave();
			
			// $query1 = sprintf("INSERT INTO `imageLog` (action, afterDesc, query, dateCreated) VALUES (8, 'attributeId: %s, value: %s', '%s', NOW())"
				// , mysql_escape_string($this->imageAttributeGetProperty('attributeId'))
				// , mysql_escape_string($this->imageAttributeGetProperty('name'))
				// , mysql_escape_string($query)
				// );
			// $this->db->query($query1);
			return true;
		}
		return false;
	}

	public function imageAttributeGetBy($attribute, $attributeType, $categoryId = '') {
		if(!@in_array($attributeType,array('attributeId','name'))) return false;
		if($attributeType == 'attributeId') {
			return $this->imageAttributeExists($attribute) ? $attribute : false; 
		}
		if($categoryId != '') {
			$ret = $this->db->query_one( sprintf(" SELECT `attributeId` FROM `imageAttribValue` WHERE `name` = '%s' AND `categoryId` = '%s' ", mysql_escape_string($attribute), mysql_escape_string($categoryId)));
		} else {
			$ret = $this->db->query_one( sprintf(" SELECT `attributeId` FROM `imageAttribValue` WHERE `name` = '%s' ", mysql_escape_string($attribute)) );
		}
		return ($ret == NULL) ? false : $ret->attributeId;
	}

	public function imageAttributeList ($categoryId = '') {
		$query = 'SELECT * FROM `imageAttribValue` WHERE 1=1 ';
		if(is_array($categoryId) && count($categoryId)) {
			$query .= sprintf(" AND `categoryId` IN (%s) ", implode(',', $categoryId));
		} else if($categoryId != '' && is_numeric($categoryId)) {
			$query .= sprintf(" AND `categoryId` = %s ", mysql_escape_string($categoryId));
		}
		$ret = $this->db->query_all($query);
		return ($ret == NULL) ? false : $ret;
	}

	
}


?>