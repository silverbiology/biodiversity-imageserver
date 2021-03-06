<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '1');

/**
 * Flick API for CFLA Images Server
 */
	ini_set('memory_limit','128M');

	$expected=array(
		  'advFilter'
		, 'advFilterId'
		, 'barcode'
		, 'cmd'
		, 'collectionCode'
		, 'constrainTo'
		, 'enAccountId'
		, 'id'
		, 'imageId'
		, 'key'
		, 'limit'
		, 'lookFor'
		, 'ocr'
		, 'stop' # stop is the number of seconds that the loop should run
	);

	// Initialize allowed variables
	foreach ($expected as $formvar)
		$$formvar = (isset(${"_$_SERVER[REQUEST_METHOD]"}[$formvar])) ? ${"_$_SERVER[REQUEST_METHOD]"}[$formvar]:NULL;

	/**
	 * Function print_c (Print Callback)
	 * This is a wrapper function for print that will place the callback around the output statement
	 */
	function print_c($str) {
		global $callback;
		header('Content-type: application/json');
		if ( isset( $callback ) && $callback != '' ) {
			$cb = $callback . '(' . $str . ')';
		} else {
			$cb = $str;
		}
		print $cb;
	}
	
	function checkAuth() {
	// die($_SERVER['REMOTE_ADDR']);
		global $si,$userAccess,$key;
		switch($si->authMode) {
			case 'key':
				if(!$si->remoteAccess->remoteAccessCheck(ip2long($_SERVER['REMOTE_ADDR']), $key)) {
					print_c (json_encode( array( 'success' => false, 'error' => array('msg' => $si->getError(103), 'code' => 103 )) ));
					exit();
				}
				break;
		
			case 'session':
			default:
				if(!$userAccess->is_logged_in()) {
					print_c (json_encode( array( 'success' => false, 'error' => array('msg' => $si->getError(104), 'code' => 104 )) ));
					exit();
				}
				break;
		}
	}

	if (PHP_SAPI === 'cli') {
	
		function parseArgs($argv){
			array_shift($argv);
			$out = array();
			foreach ($argv as $arg){
				if (substr($arg,0,2) == '--'){
				$eqPos = strpos($arg,'=');
				if ($eqPos === false){
					$key = substr($arg,2);
					$out[$key] = isset($out[$key]) ? $out[$key] : true;
				} else {
					$key = substr($arg,2,$eqPos-2);
					$out[$key] = substr($arg,$eqPos+1);
				}
				} else if (substr($arg,0,1) == '-'){
				if (substr($arg,2,1) == '='){
					$key = substr($arg,1,1);
					$out[$key] = substr($arg,3);
				} else {
					$chars = str_split(substr($arg,1));
					foreach ($chars as $char){
					$key = $char;
					$out[$key] = isset($out[$key]) ? $out[$key] : true;
					}
				}
				} else {
				$out[] = $arg;
				}
			}
			return $out;
		}
		
		$args = (parseArgs($argv));
		if ($args) {
			foreach($args as $key => $value) {
				$$key = $value;
			}
		}
		
		include_once( dirname($_SERVER['PHP_SELF']) . '/../../config.php');
	} else {
		include_once('../../config.php');
	}

	$path = $config['path']['base'] . "resources/api/classes/";
	set_include_path(get_include_path() . PATH_SEPARATOR . $path);
	require_once("classes/phpFlickr/phpFlickr.php");
	require_once("classes/bis.php");
	require_once("classes/bis.picassa.php");
	require_once("classes/bis.misc.php");
	require_once("classes/bis.gbif.php");
	require_once("classes/access_user/access_user_class.php");

	$si = new SilverImage($config['mysql']['name']);
	$userAccess = new Access_user($config['mysql']['host'], $config['mysql']['user'], $config['mysql']['pass'], $config['mysql']['name']);
	$timeStart = microtime(true);	

	
	
	switch($cmd) {
		case 'populateBoxDetect':
			header('Content-type: application/json');
			if(!$config['ratioDetect']) {
				print json_encode(array('success' => false, 'error' => array('code' => 137, 'message' => $si->getError(137))));
				exit;
			}
			$timeStart = microtime(true);
			$count = 0;
			$filter['start'] = 0;
			$filter['limit'] = $limit;
			
			if($advFilterId != '') {
				if($si->advFilter->advFilterLoadById($advFilterId)) {
					$advFilter  = $si->advFilter->advFilterGetProperty('filter');
				}
			}
			$advFilter = json_decode(stripslashes(trim($advFilter)),true);
	
			$idArray = array();
			if(is_numeric($imageId)) {
				$imageIds = array($imageId);
			} else {
				$imageIds = json_decode(@stripslashes(trim($imageId)), true);
			}
			if(is_array($imageIds) && count($imageIds)) {
				$idArray = @array_fill_keys($imageIds,'id');
			}
			$barcodes = json_decode(@stripslashes(trim($barcode)), true);
			$barcodes = (is_null($barcodes) && $barcode != '') ? array($barcode) : $barcodes;
			if(is_array($barcodes) && count($barcodes)) {
				$idArray = $idArray + @array_fill_keys($barcodes,'code');
			} else if($barcode != '') {
				$idArray = $idArray + @array_fill_keys($barcode,'code');
			}
			if(is_array($advFilter) && count($advFilter)) {
				$qry = $si->image->getByCrazyFilter($advFilter, true);
				$ret = $si->db->query($qry);
				$count = $si->db->query_total();
				$qry = $si->image->getByCrazyFilter($advFilter);
				// $query = " INSERT IGNORE INTO processQueue(imageId, processType) SELECT im.barcode, 'box_add' FROM ($qry) im ";
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT im.imageId, 'box_add', NOW() FROM ($qry) im ";
				// echo $query; exit;
				$si->db->query($query);
			} else if(is_array($idArray) && count($idArray)) {
				foreach($idArray as $id => $code) {
					$func = ($code == 'id') ? 'imageLoadById' : 'imageLoadByBarcode';
					if(!$si->image->{$func}($id)) continue;
					// if(!$si->pqueue->processQueueFieldExists($si->image->imageGetProperty('barcode'),'box_add')) {
						// $si->pqueue->processQueueSetProperty('imageId', $si->image->imageGetProperty('barcode'));
						// $si->pqueue->processQueueSetProperty('processType', 'box_add');
						// $si->pqueue->processQueueSave();
						// $count++;
					// }
					if(!$si->pqueue->processQueueFieldExists($si->image->imageGetProperty('imageId'),'box_add')) {
						$si->pqueue->processQueueSetProperty('imageId', $si->image->imageGetProperty('imageId'));
						$si->pqueue->processQueueSetProperty('processType', 'box_add');
						$si->pqueue->processQueueSave();
						$count++;
					}
				}
			} else {
				$where = '';
				if(is_numeric($filter['start']) && is_numeric($filter['limit'])) {
					$where = sprintf(" LIMIT %s, %s ", $filter['start'], $filter['limit']);
				}
				$query = 'SELECT count(*) ct FROM `image` WHERE ( `boxFlag` = 0 OR `boxFlag` IS NULL )' . $where;
				$rt = $si->db->query_one($query);
				$count = $rt->ct;
				// $query = " INSERT IGNORE INTO processQueue(imageId, processType) SELECT barcode, 'box_add' FROM `image` WHERE ( `boxFlag` = 0 OR `boxFlag` IS NULL ) " . $where;
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT imageId, 'box_add', NOW() FROM `image` WHERE ( `boxFlag` = 0 OR `boxFlag` IS NULL ) " . $where;
				$si->db->query($query);
				
			}
			$time = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;
	
		case 'populateNameGeographyFinderProcessQueue':
			header('Content-type: application/json');
			$timeStart = microtime(true);
			$count = 0;
			$filter['start'] = 0;
			$filter['limit'] = $limit;
			
			if($advFilterId != '') {
				if($si->advFilter->advFilterLoadById($advFilterId)) {
					$advFilter  = $si->advFilter->advFilterGetProperty('filter');
				}
			}
			$advFilter = json_decode(stripslashes(trim($advFilter)),true);
	
			$idArray = array();
			if(is_numeric($imageId)) {
				$imageIds = array($imageId);
			} else {
				$imageIds = json_decode(@stripslashes(trim($imageId)), true);
			}
			if(is_array($imageIds) && count($imageIds)) {
				$idArray = @array_fill_keys($imageIds,'id');
			}
			$barcodes = json_decode(@stripslashes(trim($barcode)), true);
			$barcodes = (is_null($barcodes) && $barcode != '') ? array($barcode) : $barcodes;
			if(is_array($barcodes) && count($barcodes)) {
				$idArray = $idArray + @array_fill_keys($barcodes,'code');
			}
			if(is_array($advFilter) && count($advFilter)) {
				$qry = $si->image->getByCrazyFilter($advFilter, true);
				$ret = $si->db->query($qry);
				$count = $si->db->query_total();
				$qry = $si->image->getByCrazyFilter($advFilter);
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT im.imageId, 'name_geography_add', NOW() FROM ($qry) im ";
				$si->db->query($query);
			} else if(is_array($idArray) && count($idArray)) {
				foreach($idArray as $id => $code) {
					$func = ($code == 'id') ? 'imageLoadById' : 'imageLoadByBarcode';
					if(!$si->image->{$func}($id)) continue;
					if(!$si->pqueue->processQueueFieldExists($si->image->imageGetProperty('imageId'),'name_geography_add')) {
						$si->pqueue->processQueueSetProperty('imageId', $si->image->imageGetProperty('imageId'));
						$si->pqueue->processQueueSetProperty('processType', 'name_geography_add');
						$si->pqueue->processQueueSave();
						$count++;
						if($limit != '' && $count >= $limit) {
							$countFlag = false;
						}
					}
				}
			} else {
				$where = '';
				if(is_numeric($filter['start']) && is_numeric($filter['limit'])) {
					$where = sprintf(" LIMIT %s, %s ", $filter['start'], $filter['limit']);
				}
				$query = 'SELECT count(*) ct FROM `image` WHERE ( `nameGeographyFinderFlag` = 0 OR `nameGeographyFinderFlag` IS NULL ) AND `ocrFlag` = 1 ' . $where;
				$rt = $si->db->query_one($query);
				$count = $rt->ct;
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT imageId, 'name_geography_add', NOW() FROM `image` WHERE ( `nameGeographyFinderFlag` = 0 OR `nameGeographyFinderFlag` IS NULL ) AND `ocrFlag` = 1 " . $where;
				$si->db->query($query);
			}
			$time = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;
	
		case 'populateNameFinderProcessQueue':
			header('Content-type: application/json');
			$timeStart = microtime(true);
			$count = 0;
			$filter['start'] = 0;
			$filter['limit'] = $limit;
			
			if($advFilterId != '') {
				if($si->advFilter->advFilterLoadById($advFilterId)) {
					$advFilter  = $si->advFilter->advFilterGetProperty('filter');
				}
			}
			$advFilter = json_decode(stripslashes(trim($advFilter)),true);
	
			$idArray = array();
			if(is_numeric($imageId)) {
				$imageIds = array($imageId);
			} else {
				$imageIds = json_decode(@stripslashes(trim($imageId)), true);
			}
			if(is_array($imageIds) && count($imageIds)) {
				$idArray = @array_fill_keys($imageIds,'id');
			}
			$barcodes = json_decode(@stripslashes(trim($barcode)), true);
			$barcodes = (is_null($barcodes) && $barcode != '') ? array($barcode) : $barcodes;
			if(is_array($barcodes) && count($barcodes)) {
				$idArray = $idArray + @array_fill_keys($barcodes,'code');
			}
			if(is_array($advFilter) && count($advFilter)) {
				$qry = $si->image->getByCrazyFilter($advFilter, true);
				$ret = $si->db->query($qry);
				$count = $si->db->query_total();
				$qry = $si->image->getByCrazyFilter($advFilter);
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT im.imageId, 'name_add', NOW() FROM ($qry) im ";
				$si->db->query($query);
			} else if(is_array($idArray) && count($idArray)) {
				foreach($idArray as $id => $code) {
					$func = ($code == 'id') ? 'imageLoadById' : 'imageLoadByBarcode';
					if(!$si->image->{$func}($id)) continue;
					if(!$si->pqueue->processQueueFieldExists($si->image->imageGetProperty('imageId'),'name_add')) {
						$si->pqueue->processQueueSetProperty('imageId', $si->image->imageGetProperty('imageId'));
						$si->pqueue->processQueueSetProperty('processType', 'name_add');
						$si->pqueue->processQueueSave();
						$count++;
						if($limit != '' && $count >= $limit) {
							$countFlag = false;
						}
					}
				}
			} else {
				$where = '';
				if(is_numeric($filter['start']) && is_numeric($filter['limit'])) {
					$where = sprintf(" LIMIT %s, %s ", $filter['start'], $filter['limit']);
				}
				$query = 'SELECT count(*) ct FROM `image` WHERE ( `nameFinderFlag` = 0 OR `nameFinderFlag` IS NULL ) AND `ocrFlag` = 1 ' . $where;
				$rt = $si->db->query_one($query);
				$count = $rt->ct;
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT imageId, 'name_add', NOW() FROM `image` WHERE ( `nameFinderFlag` = 0 OR `nameFinderFlag` IS NULL ) AND `ocrFlag` = 1 " . $where;
				$si->db->query($query);
				
			}
			$time = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;
	
		case 'populateBarcodeDetectProcessQueue':
			header('Content-type: application/json');
			$timeStart = microtime(true);
			$count = 0;
			$filter['start'] = 0;
			$filter['limit'] = $limit;
			
			if($advFilterId != '') {
				if($si->advFilter->advFilterLoadById($advFilterId)) {
					$advFilter  = $si->advFilter->advFilterGetProperty('filter');
				}
			}
			$advFilter = json_decode(stripslashes(trim($advFilter)),true);
	
			$idArray = array();
			if(is_numeric($imageId)) {
				$imageIds = array($imageId);
			} else {
				$imageIds = json_decode(@stripslashes(trim($imageId)), true);
			}
			if(is_array($imageIds) && count($imageIds)) {
				$idArray = @array_fill_keys($imageIds,'id');
			}
			$barcodes = json_decode(@stripslashes(trim($barcode)), true);
			$barcodes = (is_null($barcodes) && $barcode != '') ? array($barcode) : $barcodes;
			if(is_array($barcodes) && count($barcodes)) {
				$idArray = $idArray + @array_fill_keys($barcodes,'code');
			}
			if(is_array($advFilter) && count($advFilter)) {
				$qry = $si->image->getByCrazyFilter($advFilter, true);
				$ret = $si->db->query($qry);
				$count = $si->db->query_total();
				$qry = $si->image->getByCrazyFilter($advFilter);
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT im.imageId, 'barcode_detect', NOW() FROM ($qry) im ";
				$si->db->query($query);
			} else if(is_array($idArray) && count($idArray)) {
				foreach($idArray as $id => $code) {
					$func = ($code == 'id') ? 'imageLoadById' : 'imageLoadByBarcode';
					if(!$si->image->{$func}($id)) continue;
					if(!$si->pqueue->processQueueFieldExists($si->image->imageGetProperty('imageId'),'barcode_detect')) {
						$si->pqueue->processQueueSetProperty('imageId', $si->image->imageGetProperty('imageId'));
						$si->pqueue->processQueueSetProperty('processType', 'barcode_detect');
						$si->pqueue->processQueueSave();
						$count++;
						if($limit != '' && $count >= $limit) {
							$countFlag = false;
						}
					}
				}
			} else {
				$where = '';
				if(is_numeric($filter['start']) && is_numeric($filter['limit'])) {
					$where = sprintf(" LIMIT %s, %s ", $filter['start'], $filter['limit']);
				}
				$query = 'SELECT count(*) ct FROM `image` WHERE (`rawBarcode` = "" OR `rawBarcode` IS NULL) ' . $where;
				$rt = $si->db->query_one($query);
				$count = $rt->ct;
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT imageId, 'barcode_detect', NOW() FROM `image` WHERE (`rawBarcode` = '' OR `rawBarcode` IS NULL) " . $where;
				$si->db->query($query);
				
			}
			$time = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;

		case 'populateOcrProcessQueue':
			header('Content-type: application/json');
			$timeStart = microtime(true);
			$count = 0;
			$filter['start'] = 0;
			$filter['limit'] = $limit;

			$idArray = array();
			if(is_numeric($imageId)) {
				$imageIds = array($imageId);
			} else {
				$imageIds = json_decode(@stripslashes(trim($imageId)), true);
			}
			if(is_array($imageIds) && count($imageIds)) {
				$idArray = @array_fill_keys($imageIds,'id');
			}
			$barcodes = json_decode(@stripslashes(trim($barcode)), true);
			$barcodes = (is_null($barcodes) && $barcode != '') ? array($barcode) : $barcodes;
			if(is_array($barcodes) && count($barcodes)) {
				$idArray = $idArray + @array_fill_keys($barcodes,'code');
			}

			if($advFilterId != '') {
				if($si->advFilter->advFilterLoadById($advFilterId)) {
					$advFilter  = $si->advFilter->advFilterGetProperty('filter');
				}
			}
			$advFilter = json_decode(stripslashes(trim($advFilter)),true);
			if(is_array($advFilter) && count($advFilter)) {
				$qry = $si->image->getByCrazyFilter($advFilter, true);
				$ret = $si->db->query($qry);
				$count = $si->db->query_total();
				$qry = $si->image->getByCrazyFilter($advFilter);
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT im.imageId, 'ocr_add', NOW() FROM ($qry) im ";
				// echo $query; exit;
				$si->db->query($query);

			} else if(is_array($idArray) && count($idArray)) {
				foreach($idArray as $id => $code) {
					$func = ($code == 'id') ? 'imageLoadById' : 'imageLoadByBarcode';
					if(!$si->image->{$func}($id)) continue;
					if(!$si->pqueue->processQueueFieldExists($si->image->imageGetProperty('imageId'),'ocr_add')) {
						$si->pqueue->processQueueSetProperty('imageId', $si->image->imageGetProperty('imageId'));
						$si->pqueue->processQueueSetProperty('processType', 'ocr_add');
						$si->pqueue->processQueueSave();
						$count++;
						if($limit != '' && $count >= $limit) {
							$countFlag = false;
						}
					}
				}
			} else {
				$where = '';
				if(is_numeric($filter['start']) && is_numeric($filter['limit'])) {
					$where = sprintf(" LIMIT %s, %s ", $filter['start'], $filter['limit']);
				}
				$query = 'SELECT count(*) ct FROM `image` WHERE ( `ocrFlag` = 0 OR `ocrFlag` IS NULL ) AND `processed` = 1 ' . $where;
				$rt = $si->db->query_one($query);
				$count = $rt->ct;
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT imageId, 'ocr_add', NOW() FROM `image` WHERE ( `ocrFlag` = 0 OR `ocrFlag` IS NULL ) AND `processed` = 1 " . $where;
				$si->db->query($query);
			}
			$time = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;
		case 'populateFlickrProcessQueue':
		# populate the queue for uploading to flickr
			$timeStart = microtime(true);
			$count = 0;
	
			$ret = $si->image->imageGetFlickrRecords();
			$countFlag = true;
			while(($record = $ret->fetch_object()) && ($countFlag)) {
				if(!$si->pqueue->processQueueFieldExists($record->barcode,'flickr_add')) {
					$si->pqueue->processQueueSetProperty('imageId', $record->barcode);
					$si->pqueue->processQueueSetProperty('processType', 'flickr_add');
					$si->pqueue->processQueueSave();
					$count++;
					if($limit != '' && $count >= $limit) {
						$countFlag = false;
					}
				}
			}
			$time = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;
		case 'populatePicassaProcessQueue':
		# populate the queue for uploading to picassa
			$timeStart = microtime(true);
			$count = 0;
			$ret = $si->image->imageGetPicassaRecords();
			$countFlag = true;
			while(($record = $ret->fetch_object()) && ($countFlag)) {
				if(!$si->pqueue->processQueueFieldExists($record->barcode,'picassa_add')) {
					$si->pqueue->processQueueSetProperty('imageId', $record->barcode);
					$si->pqueue->processQueueSetProperty('processType', 'picassa_add');
					$si->pqueue->processQueueSave();
					$count++;
					if($limit != '' && $count >= $limit) {
						$countFlag = false;
					}
				}
			}
			$time = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;
		case 'populateGTileProcessQueue':
		# populate the queue for creating Google Map Tiles
			$timeStart = microtime(true);
			$count = 0;
	
			$ret = $si->image->imageGetGTileRecords();
			if (is_object($ret)) {
				$record = array();
				$countFlag = true;
				while(($record = $ret->fetch_object()) && $countFlag){
					if(!$si->pqueue->processQueueFieldExists($record->barcode,'google_tile')) {
						$si->pqueue->processQueueSetProperty('imageId', $record->barcode);
						$si->pqueue->processQueueSetProperty('processType', 'google_tile');
						$si->pqueue->processQueueSave();
						$count++;
						if($limit != '' && $count >= $limit) {
							$countFlag = false;
						}
					}
				}
			}
			$time = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;
		case 'populateZoomifyProcessQueue':
		# populate the queue for Zoomify process
			$timeStart = microtime(true);
			$count = 0;
	
			$ret = $si->image->imageGetZoomifyRecords();
			$countFlag = true;
			while(($record = $ret->fetch_object()) && $countFlag) {
				if(!$si->pqueue->processQueueFieldExists($record->barcode,'zoomify')) {
					$si->pqueue->processQueueSetProperty('imageId', $record->barcode);
					$si->pqueue->processQueueSetProperty('processType', 'zoomify');
					$si->pqueue->processQueueSave();
					$count++;
					if($limit != '' && $count >= $limit) {
						$countFlag = false;
					}
				}
			}
			$time = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;
		case 'populateProcessQueue':
		# populate the queue with non-processed images
			$timeStart = microtime(true);
			$count = 0;
			$filter['start'] = 0;
			$filter['limit'] = $limit;
			$ret = $si->image->imgeGetNonProcessedRecords($filter);
			$countFlag = true;
			while(($record = $ret->fetch_object()) && $countFlag) {
				if(!$si->pqueue->processQueueFieldExists($record->barcode,'all')) {
					$si->pqueue->processQueueSetProperty('imageId', $record->barcode);
					$si->pqueue->processQueueSetProperty('processType', 'all');
					$si->pqueue->processQueueSave();
					$count++;
					if($limit != '' && $count >= $limit) {
						$countFlag = false;
					}
				}
			}
			$time = microtime(true) - $timeStart;
			header('Content-type: application/json');
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;
		case 'processOCR':
			if(!$config['tesseractEnabled']) {
				header('Content-type: application/json');
				print json_encode(array('success' => false, 'error' => array('code' => 137, 'message' => $si->getError(137))));
				exit;
			}
			$_TMP = ($config['path']['tmp'] != '') ? $config['path']['tmp'] : sys_get_temp_dir() . '/';
	
			$timeStart = microtime(true);
			$tStart = time();
			$loopFlag = true;
			$images_array = array();$imageCount = 0;
			while($loopFlag) {
				$tDiff = time() - $tStart;
				if( ($stop != '') && ( $tDiff > $stop) ) $loopFlag = false;
	
				if($limit != '') {
					if($limit == 0) break;
				}
				if($limit != '' && $imageCount >= ($limit - 1)) $loopFlag = false;
	
				$record = $si->pqueue->processQueuePop('ocr_add');
				if($record === false) {
					$loopFlag = false;
				} else {
					$si->image->imageLoadById($record->imageId);
					$device = $si->storage->storageDeviceGet($si->image->imageGetProperty('storageDeviceId'));
					switch(strtolower($device['type'])) {
						case 's3':
							$tmpFileName = 'Img_' . uniqid();
							$tmpFilePath = $_TMP . $tmpFileName . '.jpg';
							$tmpFile = $tmpFilePath;
							$key = $si->image->imageGetProperty('path') . '/' . $si->image->imageGetProperty('filename');
							$key = (substr($key, 0, 1)=='/') ? (substr($key, 1, strlen($key)-1)) : ($key);
							$si->amazon->get_object($device['basePath'], $key, array('fileDownload' => $tmpFile));
							break;
						case 'local':
							$tmpFilePath = rtrim($device['basePath'] . $si->image->imageGetProperty('path'),'/') . '/' . $si->image->imageGetProperty('filename');
							$tmpFile = $tmpFilePath;
							break;
					}
					
					if($config['image_processing'] == 1) {
						$tmpImage = $tmpFilePath . '_tmp.jpg';
						$cd = "convert \"$tmpFile\" -colorspace Gray \"$tmpImage\"";
						// $cd = "convert \"$tmpFile\" -colorspace Gray  -contrast-stretch 15% \"$tmpImage\"";
// echo '<br><br>' . $cd;
						exec($cd);
						$command = sprintf("%s \"%s\" \"%s\"", $config['tesseractPath'], $tmpImage, $tmpFilePath);
// echo '<br><br>' . $command;
						exec($command);
						@unlink($tmpImage);
					} else {
						$command = sprintf("%s \"%s\" \"%s\"", $config['tesseractPath'], $tmpFile, $tmpFilePath);
						exec($command);
					}
	
					if(@file_exists($tmpFilePath . '.txt')){
						$value = file_get_contents($tmpFilePath . '.txt');
						// $images_array[] = array('imageId' => $si->image->imageGetProperty('imageId'), 'barcode' => $si->image->imageGetProperty('barcode'));
						$imageCount++;
	
						$si->image->imageSetProperty('ocrFlag',1);
						$si->image->imageSetProperty('ocrValue',$value);
						$si->image->imageSave();
					}
	
					if(strtolower($device['type']) == 's3') {
						@unlink($tmpFile);
						@unlink($tmpFilePath . '.txt');
					}
	
				}
			}
			$time_taken = microtime(true) - $timeStart;
			header('Content-type: application/json');
			// print json_encode(array('success' => true, 'processTime' => $time_taken, 'totalCount' => $imageCount, 'records' => $images_array));
			print json_encode(array('success' => true, 'processTime' => $time_taken, 'totalCount' => $imageCount));
			break;
		case 'processBoxDetect':
			header('Content-type: application/json');
			if(!$config['ratioDetect']) {
				print json_encode(array('success' => false, 'error' => array('code' => 137, 'message' => $si->getError(137))));
				exit;
			}
			$_TMP = ($config['path']['tmp'] != '') ? $config['path']['tmp'] : sys_get_temp_dir() . '/';
	
			$timeStart = microtime(true);
			$tStart = time();
			$loopFlag = true;
			$images_array = array();$imageCount = 0;
	
			$flag = false;
			$idArray = array();
			$imageIds = json_decode(@stripslashes(trim($imageId)),true);
			if(is_array($imageIds) && count($imageIds)) {
				$flag = true;
				$idArray = @array_fill_keys($imageIds,'id');
			}
			$barcodes = json_decode(@stripslashes(trim($barcode)), true);
			$barcodes = (is_null($barcodes) && $barcode != '') ? array($barcode) : $barcodes;
			if(is_array($barcodes) && count($barcodes)) {
				$flag = true;
				$idArray = $idArray + @array_fill_keys($barcodes,'code');
			}
			if($flag) {
				if(is_array($idArray) && count($idArray)) {
					foreach($idArray as $id => $code) {
						$func = ($code == 'id') ? 'imageLoadById' : 'imageLoadByBarcode';
						if(!$si->image->{$func}($id)) continue;
						$device = $si->storage->storageDeviceGet($si->image->imageGetProperty('storageDeviceId'));
						# getting image
						switch(strtolower($device['type'])) {
							case 's3':
								$amazon = new AmazonS3(array('key' => $device['password'],'secret' => $device['key']));
								$tmpPath = $_TMP . $si->image->imageGetProperty('filename');
								$fp = fopen($tmpPath, "w+b");
								$amazon->get_object($device['basePath'], $si->image->imageGetProperty('path').$si->image->imageGetProperty('filename'), array('fileDownload' => $tmpPath));
								// $si->amazon->get_object($config['s3']['bucket'], $key, array('fileDownload' => $tmpPath));
								fclose($fp);
								$image = $tmpPath;
								break;
							case 'local':
								// $image = $config['path']['images'] . $key;
								$image = $device['basePath'] . $si->image->imageGetProperty('path') . '/' . $si->image->imageGetProperty('filename');
								break;
						}
						$bcode = @explode('.',$si->image->imageGetProperty('filename'));
						@array_pop($bcode);
						$bcode = @implode('.',$bcode);


						# processing
						putenv("LD_LIBRARY_PATH=/usr/local/lib");
						$data = exec(sprintf("%s \"%s\"", $config['boxDetectPath'], $image));
						# putting the json data
						$key = $si->image->imageGetProperty('path') . '/' . $bcode . '_box.json';
						switch(strtolower($device['type'])) {
							case 's3':
								$tmpJson = $_TMP . $bcode . '_box.json';
								@file_put_contents($tmpJson,$data);
								$response = $amazon->create_object ($device['basePath'], $key, array('fileUpload' => $tmpJson,'acl' => AmazonS3::ACL_PUBLIC,'storage' => AmazonS3::STORAGE_REDUCED) );
								@unlink($tmpJson);
								@unlink($tmpPath);
								break;
							case 'local':
								// @file_put_contents($config['path']['images'] . $key,$data);
								@file_put_contents($device['basePath'] . $key,$data);
								break;
						}
						$images_array[] = array('imageId' => $si->image->imageGetProperty('imageId'), 'barcode' => $si->image->imageGetProperty('barcode'));
						$imageCount++;
						$si->pqueue->deleteProcessQueue($si->image->imageGetProperty('imageId'),'box_add');
						$si->image->imageSetProperty('boxFlag',1);
						$si->image->imageSave();
					}
				}
			} else {
				while($loopFlag) {
					$tDiff = time() - $tStart;
					if( ($stop != '') && ( $tDiff > $stop) ) $loopFlag = false;
					if($limit != '') {
						if($limit == 0) break;
					}
					if($limit != '' && $imageCount >= ($limit - 1)) $loopFlag = false;
					$record = $si->pqueue->processQueuePop('box_add');
					if($record === false) {
						$loopFlag = false;
					} else {
						// $si->image->imageLoadByBarcode($record->imageId );
						$si->image->imageLoadById($record->imageId );
						$device = $si->storage->storageDeviceGet($si->image->imageGetProperty('storageDeviceId'));
	
						# getting image
						switch(strtolower($device['type'])) {
							case 's3':
								$amazon = new AmazonS3(array('key' => $device['password'],'secret' => $device['key']));
								$tmpPath = $_TMP . $si->image->imageGetProperty('filename');
								$fp = fopen($tmpPath, "w+b");
								$amazon->get_object($device['basePath'], $si->image->imageGetProperty('path').$si->image->imageGetProperty('filename'), array('fileDownload' => $tmpPath));
								// $si->amazon->get_object($config['s3']['bucket'], $key, array('fileDownload' => $tmpPath));
								fclose($fp);
								$image = $tmpPath;
								break;
							case 'local':
								// $image = $config['path']['images'] . $key;
								$image = $device['basePath'] . $si->image->imageGetProperty('path') . '/' . $si->image->imageGetProperty('filename');
								break;
						}
						
						$bcode = @explode('.',$si->image->imageGetProperty('filename'));
						@array_pop($bcode);
						$bcode = @implode('.',$bcode);
						
						# processing
						putenv("LD_LIBRARY_PATH=/usr/local/lib");
						$data = exec(sprintf("%s \"%s\"", $config['boxDetectPath'], $image));
						# putting the json data
						$key = $si->image->imageGetProperty('path') . '/' . $bcode . '_box.json';
						switch(strtolower($device['type'])) {
							case 's3':
								$tmpJson = $_TMP . $bcode . '_box.json';
								@file_put_contents($tmpJson,$data);
								$response = $amazon->create_object ($device['basePath'], $key, array('fileUpload' => $tmpJson,'acl' => AmazonS3::ACL_PUBLIC,'storage' => AmazonS3::STORAGE_REDUCED) );
								@unlink($tmpJson);
								@unlink($tmpPath);
								break;
							case 'local':
								// @file_put_contents($config['path']['images'] . $key,$data);
								@file_put_contents($device['basePath'] . $key,$data);
								break;
						}
						$images_array[] = array('imageId' => $si->image->imageGetProperty('imageId'), 'barcode' => $si->image->imageGetProperty('barcode'));
						$imageCount++;
						$si->image->imageSetProperty('boxFlag',1);
						$si->image->imageSave();
					}
				} # while
			}
			$time_taken = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time_taken, 'totalCount' =>$imageCount, 'records' => $images_array));
			break;
		case 'processBarcodeDetect':
			$timeStart = microtime(true);
			$tStart = time();
			$loopFlag = true;
			$imageCount = 0;
			if(!$config['zBarImgEnabled']) {
				print (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(180)) ));
				exit;
			}
			$command = sprintf("%s --version ", $config['zBarImgPath']);
			$version = exec($command);
			
			while($loopFlag) {
				$tDiff = time() - $tStart;
				if( ($stop != '') && ( $tDiff > $stop) ) $loopFlag = false;
				if($limit != '' && $imageCount >= $limit) $loopFlag = false;
				$record = $si->pqueue->processQueuePop('barcode_detect');
				if($record === false) {
					$loopFlag = false;
				} else {
					$si->image->imageLoadById($record->imageId);

					$key = rtrim($si->image->imageGetProperty('path'),'/') . '/' . $si->image->imageGetProperty('filename');
					$image = $si->storage->storageDeviceFileDownload($si->image->imageGetProperty('storageDeviceId'), $key);
					$command = sprintf("%s \"%s\"", $config['zBarImgPath'], $image);
					$data = exec($command);

					$tmpArrayArray = explode("\r\n", $data);
					$data = array();
					if(is_array($tmpArrayArray)) {
						foreach($tmpArrayArray as $tmpArray) {
							if($tmpArray != '') {
								$parts = explode(":", $tmpArray);
								$data[] = array('code' => $parts[0], 'value' => $parts[1]);
								# Adding attributes
								$attributeData = array();
								if(false === ($attributeData['categoryId'] = $si->imageCategory->imageCategoryGetBy('Detected Barcodes','title'))) {
									$si->imageCategory->imageCategorySetProperty('title','Detected Barcodes');
									$si->imageCategory->imageCategorySetProperty('elementSet','BIS');
									$attributeData['categoryId'] = $si->imageCategory->imageCategoryAdd();
								}
								if(false === ($attributeData['attributeId'] = $si->imageAttribute->imageAttributeGetBy($parts[1],'name',$attributeData['categoryId']))) {
									$si->imageAttribute->imageAttributeSetProperty('name',$parts[1]);
									$si->imageAttribute->imageAttributeSetProperty('categoryId',$attributeData['categoryId']);
									$attributeData['attributeId'] = $si->imageAttribute->imageAttributeAdd();
								}
								$attributeData['imageId'] = array($si->image->imageGetProperty('imageId'));
								$si->image->imageSetData($attributeData);
								$si->image->imageAttributeAdd();
							}
						}
					}
					if(strtolower($si->storage->storageDeviceGetType($si->image->imageGetProperty('storageDeviceId'))) == 's3') {
						@unlink($image);
					}
					$dt = array('success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($data), 'lastTested' => time(), 'software' => 'zbarimg', 'version' => $version, 'results' => $data);
					$si->image->imageSetProperty('rawBarcode', json_encode($dt));
					$si->image->imageSave();
					$imageCount++;
				}
			}
			$time_taken = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time_taken, 'totalCount' => $imageCount));
			
			break;

		case 'processNameFinder':
			$timeStart = microtime(true);
			$tStart = time();
			$loopFlag = true;
			$imageCount = 0;
			
			$constrainTo = json_decode($constrainTo,true);
			if(is_array($constrainTo) && count($constrainTo)) {
				foreach($constrainTo as &$el) {
					$el = $el - 1;
				}
			}
			
			while($loopFlag) {
				$tDiff = time() - $tStart;
				if( ($stop != '') && ( $tDiff > $stop) ) $loopFlag = false;
				if($limit != '' && $imageCount >= $limit) $loopFlag = false;
				$record = $si->pqueue->processQueuePop('name_add');
				if($record === false) {
					$loopFlag = false;
				} else {
					$ret = getNames($record->imageId);
					if($ret['success']) {
						$si->image->imageLoadById($record->imageId);
						if(isset($ret['globalnamesResults'])) {
							$si->image->imageSetProperty('nameFinderValue',json_encode($ret['globalnamesResults']));
						} else if ($ret['gbifResults']) {
							$si->image->imageSetProperty('nameFinderValue',json_encode($ret['gbifResults']));
						}
						$si->image->imageSave();
						
						foreach(array('family','genus','scientificName','specificEpithet','phylum', 'class', 'kingdom', 'order', 'taxonomicStatus') as $rr) {
							$dt = (in_array($rr,array('taxonomicStatus'))) ? $ret : $ret['data'];
							if($dt[$rr] != '') {
								$category = @ucfirst($rr);
								$data = array();
								if(false === ($data['categoryId'] = $si->imageCategory->imageCategoryGetBy($category,'title'))) {
									$si->imageCategory->imageCategorySetProperty('title',$category);
									$si->imageCategory->imageCategorySetProperty('elementSet','BIS');
									$data['categoryId'] = $si->imageCategory->imageCategoryAdd();
								}
								if(false === ($data['attributeId'] = $si->imageAttribute->imageAttributeGetBy($dt[$rr],'name',$data['categoryId']))) {
									$si->imageAttribute->imageAttributeSetProperty('name',$dt[$rr]);
									$si->imageAttribute->imageAttributeSetProperty('categoryId',$data['categoryId']);
									$data['attributeId'] = $si->imageAttribute->imageAttributeAdd();
								}
								$data['imageId'] = array($record->imageId);
								$si->image->imageSetData($data);
								$si->image->imageAttributeAdd();
							}
						}
					}
					
					$si->image->imageLoadById($record->imageId);
					$si->image->imageSetProperty('nameFinderFlag',1);
					$si->image->imageSave();
	
					$imageCount++;
				}
			}
			$time_taken = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time_taken, 'totalCount' => $imageCount));
			
			break;

		case 'processNameGeographyFinder':
			$timeStart = microtime(true);
			$tStart = time();
			$loopFlag = true;
			$imageCount = 0;
			
			$constrainTo = json_decode($constrainTo,true);
			if(is_array($constrainTo) && count($constrainTo)) {
				foreach($constrainTo as &$el) {
					$el = $el - 1;
				}
			}
			$lookFor = json_decode($lookFor,true);
			$advFilter = json_decode($advFilter,true);
			$advFilter = is_null($advFilter) ? '' : $advFilter ;
			
			$advOriginal = $advFilter;
			
			while($loopFlag) {
				$tDiff = time() - $tStart;
				if( ($stop != '') && ( $tDiff > $stop) ) $loopFlag = false;
				if($limit != '' && $imageCount >= $limit) $loopFlag = false;
				$record = $si->pqueue->processQueuePop('name_geography_add');
				if($record === false) {
					$loopFlag = false;
				} else {

					
					if(!(is_array($lookFor) && count($lookFor))) {
						$advFilter = $advOriginal;
						$geoData = array();
						$lookFor = array('Country');
						$data1 = getGeoNames($record->imageId,$advFilter);
						$geoData = array_merge($geoData,$data1);
						$lookFor = array('StateProvince');
						$data1 = getGeoNames($record->imageId,$advFilter);
						$geoData = array_merge($geoData,$data1);
						$children = array();
						if(is_array($data1) && count($data1)) {
							foreach($data1 as $dt) {
								if(isset($dt['StateProvince']) && $dt['StateProvince'] != '') {
									$children[] = array('node' => 'condition', 'object' => 'geographyView', 'key' => 'StateProvince', 'condition' => '=', 'value' => $dt['StateProvince']);
								}
							}
						}
						if(count($children)) {
							$advFilter = array('node' => 'group','logop' => 'or','children' => $children);
							$lookFor = array('County');
							$data1 = getGeoNames($record->imageId,$advFilter);
							$geoData = array_merge($geoData,$data1);
						}
						$lookFor = '';
					} else {
						$geoData = getGeoNames($record->imageId,$advFilter);
					}
					if(is_array($geoData) && count($geoData)) {
						foreach($geoData as $geo) {
							if(count($geo) && is_array($geo)) {
								foreach($geo as $category => $attribute) {
									$data = array();
									if(false === ($data['categoryId'] = $si->imageCategory->imageCategoryGetBy($category,'title'))) {
										$si->imageCategory->imageCategorySetProperty('title',$category);
										$si->imageCategory->imageCategorySetProperty('elementSet','BIS');
										$data['categoryId'] = $si->imageCategory->imageCategoryAdd();
									}
									if(false === ($data['attributeId'] = $si->imageAttribute->imageAttributeGetBy($attribute,'name',$data['categoryId']))) {
										$si->imageAttribute->imageAttributeSetProperty('name',$attribute);
										$si->imageAttribute->imageAttributeSetProperty('categoryId',$data['categoryId']);
										$data['attributeId'] = $si->imageAttribute->imageAttributeAdd();
									}
									$data['imageId'] = array($record->imageId);
									$si->image->imageSetData($data);
									$si->image->imageAttributeAdd();
								}
							}
						}
					}
					
					$si->image->imageLoadById($record->imageId);
					$si->image->imageSetProperty('nameGeographyFinderFlag',1);
					$si->image->imageSave();
	
					$imageCount++;
				}
			}
			$time_taken = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time_taken, 'totalCount' => $imageCount));
			
			break;
			
		case 'uploadFlickr':
			$_TMP = ($config['path']['tmp'] != '') ? $config['path']['tmp'] : sys_get_temp_dir() . '/';
	
			$timeStart = microtime(true);
			$tStart = time();
			$loopFlag = true;
			$f = new phpFlickr($config['flkr']['key'],$config['flkr']['secret']);
			if( $f->auth_checkToken() === false) {
				$f->auth('write');
			}
	
			$images_array = array();$imageCount = 0;
			while($loopFlag) {
				$tDiff = time() - $tStart;
				if( ($stop != '') && ( $tDiff > $stop) ) $loopFlag = false;
				if($limit != '' && $imageCount >= $limit) $loopFlag = false;
				$record = $si->pqueue->processQueuePop('flickr_add');
				if($record === false) {
					$loopFlag = false;
				} else {
	
					if($config['mode'] == 's3') {
						$tmpFileName = 'Img_' . time();
						$tmpFilePath = $_TMP . $tmpFileName;
						$image = $tmpFilePath . '.jpg';
						$key = $si->image->imageBarcodePath($record->imageId) . $record->imageId . '.jpg';
						$si->amazon->get_object($config['s3']['bucket'], $key, array('fileDownload' => $image));
					} else {
						$image = $config['path']['images'] . $si->image->imageBarcodePath($record->imageId) . $record->imageId . '.jpg';
					}
					# change setting photo to private while uploading
					$res = $f->sync_upload( $image, $record->imageId, '', '', 0 );
					if( $res != false ) {
	
						$flkrData = $f->photos_getInfo($res);
						$flickr_details = json_encode(array('server' => $flkrData['server'],'farm' => $flkrData['farm'],'secret' => $flkrData['secret']));
	
						$tags = "{$record->imageId} copyright:(CyberFlora-Louisiana)";
						$f->photos_addTags($res,$tags);
						$si->image->imageLoadByBarcode($record->imageId);
	
						$images_array[] = array('imageId' => $si->image->imageGetProperty('imageId'), 'barcode' => $si->image->imageGetProperty('barcode'));
						$imageCount++;
	
						$si->image->imageSetProperty('flickrPlantId',$res);
						$si->image->imageSetProperty('flickrModified',date('Y-m-d H:i:s'));
						$si->image->imageSetProperty('flickrDetails',$flickr_details);
						$si->image->imageSave();
					}
	
					if($config['mode'] == 's3') {
						@unlink($image);
					}
	
				}
			}
			$time_taken = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time_taken, 'totalCount' =>$imageCount, 'records' => $images_array));
	
			break;
		case 'uploadPicassa':
			$_TMP = ($config['path']['tmp'] != '') ? $config['path']['tmp'] : sys_get_temp_dir() . '/';
	
			$timeStart = microtime(true);
			$tStart = time();
			$loopFlag = true;
	
			$picassa = new PicassaWeb;
			
			$picassa->set('picassa_path',$config['picassa']['lib_path']);
			$picassa->set('picassa_user',$config['picassa']['email']);
			$picassa->set('picassa_pass',$config['picassa']['pass']);
			$picassa->set('picassa_album',$config['picassa']['album']);
			
			$picassa->clientLogin();
	
			$images_array = array();$imageCount = 0;
	
			while($loopFlag) {
				if( ($stop != "") && ((time() - $tStart) > $stop) ) $loopFlag = false;
				if($limit != '' && $imageCount >= $limit) $loopFlag = false;
				$record = $si->pqueue->processQueuePop('picassa_add');
				if($record === false) {
					$loopFlag = false;
				} else {
					$image = array();
					if($config['mode'] == 's3') {
						$tmpFile = $_TMP . 'Img_' . time() . '.jpg';
						$key = $si->image->imageBarcodePath($record->imageId) . $record->imageId . '.jpg';
						$si->amazon->get_object($config['s3']['bucket'], $key, array('fileDownload' => $tmpFile));
						$image['tmp_name'] = $tmpFile;
					} else {
						$image['tmp_name'] = $config['path']['images'] . $si->image->imageBarcodePath($record->imageId) . $record->imageId . '.jpg';
					}
					$image['name'] = $record->imageId;
					$image['type'] = 'image/jpeg';
					$image['tags'] = $record->imageId;
					$album_id = $picassa->getAlbumID();
					$res = $picassa->addPhoto($image);
					if( $res != false ) {
						
						$si->image->imageLoadByBarcode($record->imageId);
	
						$images_array[] = array('imageId' => $si->image->imageGetProperty('imageId'), 'barcode' => $si->image->imageGetProperty('barcode'));
						$imageCount++;
	
						$si->image->imageSetProperty('picassaPlantId',$res);
						$si->image->imageSetProperty('picassaModified',date('Y-m-d H:i:s'));
						$si->image->imageSave();
					}
				}
				if($config['mode'] == 's3') {
					@unlink($tmpFile);
				}
			}
			$time_taken = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time_taken, 'totalCount' =>$imageCount, 'records' => $images_array));
			break;
	
		case 'populateEnLabels':
			$timeStart = microtime(true);
			$start_date = $si->s2l->getLatestDate();

			$url = $config['hsUrl'] . '?task=getEnLabels&start_date=' . $start_date;
			// echo $url; exit;
			$jsonObject = @stripslashes(@file_get_contents($url));
	
			$jsonObject = json_decode($jsonObject,true);
			if($jsonObject['success']) {
				$labels = $jsonObject['results'];
				if(is_array($labels) && count($labels)) {
					foreach($labels as $label) {
						$si->s2l->Specimen2LabelSetProperty('labelId',$label['label_id']);
						$si->s2l->Specimen2LabelSetProperty('evernoteAccountId',$label['evernote_account']);
						$si->s2l->Specimen2LabelSetProperty('barcode',$label['barcode']);
						$si->s2l->Specimen2LabelSetProperty('dateAdded',$label['date_added']);
						if($si->s2l->Specimen2LabelSave()) {
							$labelCount++;
						}
					}
				}
			}
			$time_taken = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time_taken, 'labelCount' => $labelCount));
			break;
	
		case 'populateGuessTaxaProcessQueue':
			$timeStart = microtime(true);
			$count = 0;
			$filter['start'] = 0;
			$filter['limit'] = $limit;
			if(trim($imageId) != '') {
				$imageIds = json_decode(stripslashes($imageId),true);
				if(is_array($imageIds) && count($imageIds)) {
					foreach($imageIds as $imageId) {
						$loadFlag = false;
						if(!is_numeric($imageId)) {
							$loadFlag = $si->image->imageLoadByBarcode($imageId);
						} else {
							$loadFlag = $si->image->imageLoadById($imageId);
						}
						if($loadFlag) {
							if(!$si->pqueue->processQueueFieldExists($si->image->imageGetProperty('barcode'),'guess_add')) {
								$si->pqueue->processQueueSetProperty('imageId', $si->image->imageGetProperty('barcode'));
								$si->pqueue->processQueueSetProperty('processType', 'guess_add');
								$si->pqueue->processQueueSave();
								$count++;
							}
						}
					}
				}
			} else {
				$ret = $si->image->imageGetGuessTaxaRecords($filter);
				$countFlag = true;
				while(($record = $ret->fetch_object()) && ($countFlag)) {
					if(!$si->pqueue->processQueueFieldExists($record->barcode,'guess_add')) {
						$si->pqueue->processQueueSetProperty('imageId', $record->barcode);
						$si->pqueue->processQueueSetProperty('processType', 'guess_add');
						$si->pqueue->processQueueSave();
						$count++;
						if($limit != '' && $count >= $limit) {
							$countFlag = false;
						}
					}
				}
			}
			$time = microtime(true) - $timeStart;
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;
	
		case 'processGuessTaxa':
			if(!$config['tesseractEnabled']) {
				header('Content-type: application/json');
				print json_encode(array('success' => false, 'error' => array('code' => 137, 'message' => $si->getError(137))));
				exit;
			}
			$_TMP = ($config['path']['tmp'] != '') ? $config['path']['tmp'] : sys_get_temp_dir() . '/';
	
			$timeStart = microtime(true);
			$tStart = time();
			$loopFlag = true;
			$images_array = array();$imageCount = 0;
			while($loopFlag) {
				$tDiff = time() - $tStart;
				if( ($stop != '') && ( $tDiff > $stop) ) $loopFlag = false;
	
				if($limit != '') {
					if($limit == 0) break;
				}
				if($limit != '' && $imageCount >= ($limit - 1)) $loopFlag = false;
	
				$record = $si->pqueue->processQueuePop('guess_add');
				if($record === false) {
					$loopFlag = false;
				} else {
					$imageId = $record->imageId;
					$si->image->imageLoadByBarcode($imageId);
					if(!($si->image->imageGetProperty('ocr_flag')))
					{
					//Perform ocr and store values
						if($config['mode'] == 's3') {
							$tmpFileName = 'Img_' . microtime();
							$tmpFilePath = $_TMP . $tmpFileName;
							$tmpFile = $tmpFilePath . '.jpg';
							$key = $si->image->imageBarcodePath($record->imageId) . $record->imageId . '.jpg';
	
							$si->amazon->get_object($config['s3']['bucket'], $key, array('fileDownload' => $tmpFile));
						} else {
							$tmpFilePath = $config['path']['images'] . $si->image->imageBarcodePath($record->imageId) . $record->imageId;
							$tmpFile = $tmpFilePath . '.jpg';
						}
	
						if($config['image_processing'] == 1) {
							$tmpImage = $tmpFilePath . '_tmp.jpg';
							$cd = "convert \"$tmpFile\" -colorspace Gray  -contrast-stretch 15% \"$tmpImage\"";
							exec($cd);
							$command = sprintf("%s \"%s\" \"%s\"", $config['tesseractPath'], $tmpImage, $tmpFilePath);
							exec($command);
							@unlink($tmpImage);
						} else {
							$command = sprintf("%s \"%s\" \"%s\"", $config['tesseractPath'], $tmpFile, $tmpFilePath);
							exec($command);
						}
	
						if(@file_exists($tmpFilePath . '.txt')){
							$value = file_get_contents($tmpFilePath . '.txt');
							$si->image->imageLoadByBarcode($record->imageId);
							$images_array[] = array('imageId' => $si->image->imageGetProperty('imageId'), 'barcode' => $si->image->imageGetProperty('barcode'));
							$imageCount++;
	
							$si->image->imageSetProperty('ocrFlag',1);
							$si->image->imageSetProperty('ocrValue',$value);
							$si->image->imageSave();
						}
						if($config['mode'] == 's3') {
							@unlink($tmpFile);
							@unlink($tmpFilePath . '.txt');
						}
					}
					$si->image->imageLoadByBarcode($imageId);
					$imageCount++;
					$array = gbifNameFinder($si->image->imageGetProperty('ocrValue'));
					if($array) {
						foreach($array as $names) {
							$array1 = gbifChecklistBank($names);
							$array2 = gbifFullRecord($array1['taxonID']);
							$expectedRank = array('family','genus');
							if(strtolower($array2['taxonomicStatus']=='synonym')) {
								if(in_array(strtolower($array1['rank']),$expectedRank))
								{
									$si->image->imageSetProperty('tmp'.ucfirst($array1['rank']),$array2['canonicalName']);
									$si->image->imageSetProperty('guessFlag',1);
									$si->image->imageSave();
								}
							}
							else
							{
								if(in_array(strtolower($array1['rank']),$expectedRank))
								{
									$si->image->imageSetProperty('tmp'.ucfirst($array1['rank']).'Accepted',$array2['higherTaxon']);
									$si->image->imageSetProperty('guessFlag',1);
									$si->image->imageSave();
								}
							}
						}
					}
					
				}
			}
			$time_taken = microtime(true) - $timeStart;
			header('Content-type: application/json');
			print json_encode(array('success' => true, 'processTime' => $time_taken, 'totalCount' => $imageCount, 'images' => $images_array));
			break;
			
		case 'populateEvernoteProcessQueue':
			$timeStart = microtime(true);
			$count = 0;
			$filter['start'] = 0;
			$filter['limit'] = $limit;
			$filter['collectionCode'] = (trim($collectionCode!='')) ? $collectionCode : '';
			
			if($advFilterId != '') {
				if($si->advFilter->advFilterLoadById($advFilterId)) {
					$advFilter  = $si->advFilter->advFilterGetProperty('filter');
				}
			}
			$advFilter = json_decode(stripslashes(trim($advFilter)),true);
	
			$idArray = array();
			if(is_numeric($imageId)) {
				$imageIds = array($imageId);
			} else {
				$imageIds = json_decode(@stripslashes(trim($imageId)), true);
			}
			if(is_array($imageIds) && count($imageIds)) {
				$idArray = @array_fill_keys($imageIds,'id');
			}

			$barcodes = json_decode(@stripslashes(trim($barcode)), true);
			$barcodes = (is_null($barcodes) && $barcode != '') ? array($barcode) : $barcodes;
			if(is_array($barcodes) && count($barcodes)) {
				$idArray = $idArray + @array_fill_keys($barcodes,'code');
			} else if($barcode != '') {
				$idArray = $idArray + @array_fill_keys($barcode,'code');
			}
			
			if(is_array($advFilter) && count($advFilter)) {
				$qry = $si->image->getByCrazyFilter($advFilter, true);
				$ret = $si->db->query($qry);
				$count = $si->db->query_total();
				$qry = $si->image->getByCrazyFilter($advFilter);
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT im.imageId, 'evernote', NOW()  FROM ($qry) im ";
				// echo $query; exit;
				$si->db->query($query);
			} else if(is_array($idArray) && count($idArray)) {
				foreach($idArray as $id => $code) {
					$func = ($code == 'id') ? 'imageLoadById' : 'imageLoadByBarcode';
					if(!$si->image->{$func}($id)) continue;
					if(!$si->pqueue->processQueueFieldExists($si->image->imageGetProperty('imageId'),'evernote')) {
						$si->pqueue->processQueueSetProperty('imageId', $si->image->imageGetProperty('imageId'));
						$si->pqueue->processQueueSetProperty('processType', 'evernote');
						$si->pqueue->processQueueSave();
						$count++;
					}
				}
			} else {
				$where = '';
				if(is_numeric($filter['start']) && is_numeric($filter['limit'])) {
					$where = sprintf(" LIMIT %s, %s ", $filter['start'], $filter['limit']);
				}
				$query = 'SELECT count(*) ct FROM `image` WHERE (  `processed` = 0 OR `processed` IS NULL ' . (($filter['collectionCode'] != '') ? sprintf(" AND `collectionCode` = '%s' ", $filter['collectionCode']) : '' ) . ' )' . $where;
				$rt = $si->db->query_one($query);
				$count = $rt->ct;
				$query = " INSERT IGNORE INTO processQueue(imageId, processType, dateAdded) SELECT imageId, 'evernote', NOW() FROM `image` WHERE (  `processed` = 0 OR `processed` IS NULL " . (($filter['collectionCode'] != '') ? sprintf(" AND `collectionCode` = '%s' ", $filter['collectionCode']) : '' ) . " ) " . $where;
				$si->db->query($query);
				
			}
			$time = microtime(true) - $timeStart;
			header('Content-type: application/json');
			print json_encode(array('success' => true, 'processTime' => $time, 'totalCount' => $count));
			break;
			
		case 'processEvernoteProcessQueue':
			$timeStart = microtime(true);
			$tStart = time();
			$loopFlag = true;
			$imageCount = 0;
			if(!$si->en->evernoteAccountsLoadById( $enAccountId )) {
				print json_encode(array('success' => false, 'message' => 'No valid evernote account id given'));
				exit;
			}
			while($loopFlag) {
				$tDiff = time() - $tStart;
				if( ($stop != '') && ( $tDiff > $stop) ) $loopFlag = false;
				$record = $si->pqueue->processQueuePop('evernote');
				if($record === false) {
					$loopFlag = false;
				} else {
					$si->image->imageLoadById($record->imageId);
					
					$url = $config['evernoteUrl']."?cmd=add_note";
					$url .= "&title=".$si->image->imageGetProperty('barcode');
					if($si->image->imageGetProperty('collectionCode') != '') {
						$tagName = "CollectionCode:".$si->image->imageGetProperty('collectionCode');
						$url .= "&tag=[\"".$tagName."\"]";
					}
					$label = $si->image->imageGetUrl($record->imageId);
					$url .= "&label=".$label['url'];
					$url .= "&auth=[".json_encode($si->en->evernoteAccountsGetDetails()).']';
					$result = file_get_contents($url);
					$result = json_decode($result, true);
					if($result['success']) {
						$si->s2l->Specimen2LabelSetProperty('labelId',$result['noteRet']['noteRet']['updateSequenceNum']);
						$si->s2l->Specimen2LabelSetProperty('evernoteAccountId',$enAccountId);
						$si->s2l->Specimen2LabelSetProperty('barcode',$si->image->imageGetProperty('barcode'));
						$si->s2l->Specimen2LabelSave();
						if($si->image->imageGetProperty('collectionCode') != '')
						$si->en->evernoteTagsAdd($tagName, $result['noteRet']['noteRet']['tagGuids'][0]);
						$imageCount++;
					}
				}
				if($limit != '' && $imageCount >= $limit) $loopFlag = false;
			}
			$time_taken = microtime(true) - $timeStart;
			header('Content-type: application/json');
			print json_encode(array('success' => true, 'processTime' => $time_taken, 'totalCount' =>$imageCount));
			break;
	
	# Test Tasks
	
			case 'testGeo':
				echo '<pre>';
				$advFilter = json_decode($advFilter,true);
				$advFilter = is_null($advFilter) ? '' : $advFilter ;
			
				$constrainTo = json_decode($constrainTo,true);
				if(is_array($constrainTo) && count($constrainTo)) {
					foreach($constrainTo as &$el) {
						$el = $el - 1;
					}
				}
				$lookFor = json_decode($lookFor,true);
				if(!(is_array($lookFor) && count($lookFor))) {
					$data = array();
					$lookFor = array('Country');
					$data1 = getGeoNames($imageId,$advFilter);
					$data = array_merge($data,$data1);
					$lookFor = array('StateProvince');
					$data1 = getGeoNames($imageId,$advFilter);
					$data = array_merge($data,$data1);
					echo '<br>';
					print_r($lookFor);
					echo '<br>';
					print_r($data);
					$children = array();
					if(is_array($data1) && count($data1)) {
						foreach($data1 as $dt) {
							if(isset($dt['StateProvince']) && $dt['StateProvince'] != '') {
								$children[] = array('node' => 'condition', 'object' => 'geographyView', 'key' => 'StateProvince', 'condition' => '=', 'value' => $dt['StateProvince']);
							}
						}
					}
					if(count($children)) {
						$advFilter = array('node' => 'group','logop' => 'or','children' => $children);
						$lookFor = array('County');
						$data1 = getGeoNames($imageId,$advFilter);
					echo '<br>';
					print_r($advFilter);
					echo '<br>';
					print_r($lookFor);
					echo '<br>';
					print_r($data1);
						$data = array_merge($data,$data1);
					}
				} else {
					$data = getGeoNames($imageId,$advFilter);
				}
				// $data = getGeoNames($imageId,$advFilter);
				print_r($data);
				break;
	
			case 'ocrTaxaLookup':
				checkAuth();

				$constrainTo = json_decode($constrainTo,true);
				if(is_array($constrainTo) && count($constrainTo)) {
					foreach($constrainTo as &$el) {
						$el = $el - 1;
					}
				}
				
				$data = getNames($imageId,$ocr);
				
				header('Content-type: application/json');
				echo json_encode($data);
				
				// echo '<pre>';
				// var_dump($data);
				break;
				
			default:
				print json_encode(array('success' => false, 'message' => 'No cmd Provided'));
				break;
		}

	function getGeoNames($imageId, $advFilter = '') {
		global $si,$config, $lookFor, $constrainTo;
		
		$filterQuery = '';
		
		if(!$si->image->imageLoadById($imageId)) {
			return array();
		}
		if(is_array($advFilter) && count($advFilter)) {
			$categories = array();
			$filterWhere = '';
			
			$filterWhere = geographyFilter($advFilter);
			
			$filterWhere = ($filterWhere != '') ? ' AND ' . $filterWhere : $filterWhere;
			
			$ar = array();
			foreach(array('Country','StateProvince', 'County', 'Locality') as $region) {
				if(!in_array($region, array_keys($categories)) || $categories[$region] == '') {
					if(is_array($lookFor) && count($lookFor)) {
						if(in_array($region,$lookFor)) {
							$ar[] = " ( SELECT `$region` AS word, '$region' region FROM `geographyView` WHERE 1=1 " . $filterWhere . ' ) ';
						}
					} else {
						$ar[] = " ( SELECT `$region` AS word, '$region' region FROM `geographyView` WHERE 1=1 " . $filterWhere . ' ) ';
					}
				}
			}
			$filterQuery = implode(' UNION ',$ar);
		}
		
		$data = array();
		
		$str = $si->image->imageGetProperty('ocrValue');

		$parsedWords = array();
		$eraseBlackList = array('Northeast Louisiana University, Monroe', 'Northeast Louisiana University');
		$blackList = array('date','north','south','east','west','thomas');
		$rankArray = array('Country','StateProvince','County','Locality');
		$whiteList = array('New York' => 'StateProvince', 'District of Columbia' => 'StateProvince', 'New Hampshire' => 'StateProvince', 'New Jersey' => 'StateProvince', 'New Mexico' => 'StateProvince', 'New York' => 'StateProvince', 'North Carolina' => 'StateProvince', 'North Dakota' => 'StateProvince', 'Rhode Island' => 'StateProvince', 'South Carolina' => 'StateProvince', 'South Dakota' => 'StateProvince', 'West Virginia' => 'StateProvince' );

		if(count($eraseBlackList) && is_array($eraseBlackList)) {
			foreach($eraseBlackList as $trm) {
				$str = str_ireplace($trm,'',$str);
			}
		}

		$linesArray = preg_split ('/$\R?^/m', $str);
		
		if(is_array($linesArray) && count($linesArray)) {
			for($i = 0; $i < count($linesArray); $i++) {
				$line = $linesArray[$i];
				
				if(is_array($constrainTo) && count($constrainTo)) {
					if(!in_array($i,$constrainTo)) continue;
				}
				if(trim($line) != '') {
					$line = preg_replace('/\s+/',' ',trim($line));
					
					if(is_array($whiteList) && count($whiteList)) {
						foreach($whiteList as  $ky => $wl) {
							if(false !== stripos($line,$ky)) {
								if(is_array($lookFor) && count($lookFor)) {
									if(in_array($wl,$lookFor)) {
										$data[] = array( $wl => $ky);
									}
								} else {
									$data[] = array( $wl => $ky);
								}
							}
						}
					}
					
					$wordsArray = explode(' ', $line);
					if(is_array($wordsArray) && count($wordsArray)) {
						foreach($wordsArray as $word) {
							if(preg_match('/^[a-zA-Z.,:]*$/',$word)) {
								$word = trim($word,'.,:');
								if(strlen($word) < 3) continue;
								if(in_array(@strtolower($word),$blackList)) continue;
								if(in_array($word,$parsedWords)) continue;
								if($filterQuery != '') {
									# including the words if any from the advFilter conditions
									if(false !== ($cat = array_search(ucfirst(strtolower($word)),$advFilter))) {
										$data[] = array($cat => ucfirst(strtolower($word)));
										$parsedWords[] = $word;
									}
									$query = " SELECT * FROM ($filterQuery) t WHERE t.`word` = '" . mysql_escape_string($word) . "' ";
									// echo '<br>' . $query . '<br>';
									$ret = $si->db->query($query);
									if ($ret != NULL) {
										while($record = $ret->fetch_object()) {
											$data[] = array($record->region => $record->word);
											$parsedWords[] = $word;
										}
									}
								} else {
									$query = sprintf(" SELECT DISTINCT `name`, `rank`  FROM `geography` WHERE `name` = '%s' ", $word);
									$ret = $si->db->query_one($query);
									if ($ret != NULL) {
										if(isset($rankArray[$ret->rank]) && $rankArray[$ret->rank] != '') {
											if(is_array($lookFor) && count($lookFor)) {
												if(in_array($rankArray[$ret->rank],$lookFor)) {
													$data[] = array($rankArray[$ret->rank] => $ret->name);
												}
											} else {
												$data[] = array($rankArray[$ret->rank] => $ret->name);
											}
											$parsedWords[] = $word;
										}
										
									}
								}
							}
						}
					}
				}
			}
		}
		return $data;
	}
	
	function getNames($imageId = '',$ocr = '') {
		global $si,$config,$constrainTo;
		if($imageId == '' && $ocr == '') {
			return array('success' => false);
		}
		
		if($ocr != '') {
			$ocrValue = urldecode($ocr);
		} else {
			if( $imageId != '' && !$si->image->imageLoadById($imageId)) {
				return array('success' => false);
			}
			$ocrValue = $si->image->imageGetProperty('ocrValue');
		}
		
		if(is_array($constrainTo) && count($constrainTo)) {
			$tempArray = array();
			$ocrValue = preg_split ('/$\R?^/m', $ocrValue);
			foreach($constrainTo as $index) {
				if(array_key_exists($index,$ocrValue)) {
					$tempArray[] = $ocrValue[$index];
				}
			}
			$ocrValue = implode("\r\n", $tempArray);
		}
		
		$names = array();
		
		$sourceUrl = 'http://gnrd.globalnames.org/name_finder.json?';
		$sourceParams1 = array('text' => $ocrValue);
		
		$gnResolver = 'http://resolver.globalnames.org/name_resolvers.json?';
		$gnResolverParams = array('data_source_ids' => 1);
		
		$sourceUrl2 = 'http://ecat-dev.gbif.org/ws/indexer?';
		$sourceParams2 = array('input' => $ocrValue, 'type' => 'text', 'format' => 'json');
		
		$verificationUrl = 'http://ecat-dev.gbif.org/ws/usage/?';
		$verificationParams = array('rkey' => 1, 'showRanks' => 'kpcofgs');

		$getUrl = @http_build_query($sourceParams1);
		$data = json_decode(@file_get_contents($sourceUrl . $getUrl),true);
		
		
		$tokenUrl = $data['token_url'];
		if(isset($tokenUrl) && $tokenUrl != '' && $data['status'] == 303) {
			$counter = 0;
			do
			{
				$counter++;
				if($counter > 30) break;
				sleep(1);
				$data = @file_get_contents($tokenUrl);
				$data = utf8_encode($data);
				$data = json_decode($data,true);
				
				# $data = json_decode(@file_get_contents($tokenUrl),true);
			}
			while($data["status"] != 200);
		}
		
		if(isset($data["names"]) && is_array($data["names"]) && count($data["names"])) {
			foreach($data["names"] as $dtName) {
				# $gnData = json_decode(@file_get_contents($gnResolver . $dtName['scientificName']),true);
				$params = $gnResolverParams;
				$params['names'] = $dtName['scientificName'];
				$getUrl = @http_build_query($params);
				$gnData = @file_get_contents($gnResolver . $getUrl);
				$gnData = utf8_encode($gnData);
				$gnData = json_decode($gnData,true);
				if($gnData['status'] == 'success' && isset($gnData['data'][0]['results']) && $gnData['data'][0]['results'][0]['score'] > 0.5) {
					$gnData['data'][0]['results'][0]['scientificName'] = $gnData['data'][0]['results'][0]['canonical_form'];
					$names[] = $gnData['data'][0]['results'][0];
				}
			}
		}
		$dataSource = '';
		if( !count($names) ) {
			$getUrl = @http_build_query($sourceParams2);
			$data = json_decode(@file_get_contents($sourceUrl2 . $getUrl),true);
			$names = $data['names'];
			$dataSource = 'GBIF';
			$resultName = 'gbifResults';
		} else {
			$dataSource = 'GlobalNames';
			$resultName = 'globalnamesResults';
		}
		if(is_array($names) && count($names)) {
			foreach($names as $dt) {
				$word = $dt['scientificName'];
				$word = preg_replace('/\s+/',' ',trim($word));
				$params = $verificationParams;
				$params['q'] = $word;
				$vUrl = @http_build_query($params);
				$vData = file_get_contents($verificationUrl . $vUrl);
				$vData = utf8_encode($vData);
				$vData = json_decode($vData,true);
				if(count($vData['data'])) {
				
					// $tmpData = $vData['data'];
					$acceptedName = '';
					if(isset($dt['current_name_string']) && $dt['current_name_string'] != '') {
						$ar = explode(' ', $dt['current_name_string']);
						if(count($ar) > 1) {
							$acceptedName = implode(' ',array($ar[0],$ar[1]));
						}
					}
				
					foreach(array('kingdom','phylum','order','class','family','genus') as $taxon) {
						$vData['data'][0][$taxon] = array_shift(explode(' ',trim($vData['data'][0][$taxon])));
					}
					$ar = explode(' ', $vData['data'][0]['scientificName']);
					$vData['data'][0]['specificEpithet'] =  $ar[1];
					$taxonomicStatus = ($vData['data'][0]['isSynonym'] == 'true') ? 'Synonym' : '';
					$output = array();
					$data = array();
					$flag = false;
					if(false !== (stripos($ocrValue,$vData['data'][0]['specificEpithet']))) {
						$data['specificEpithet'] = $vData['data'][0]['specificEpithet'];
						$data['scientificName'] = $vData['data'][0]['scientificName'];
						$flag = true;
						
					}
					
					$genus = $vData['data'][0]['genus'];
					if(false !== (stripos($ocrValue,$dt['canonical_form']))) {
						$tmpGenus = @array_shift(@explode(' ',$dt['canonical_form']));
						$genus = $tmpGenus;
					}
					
					if(false !== (stripos($ocrValue,$genus))) {
						$flag = true;
						$data['genus'] = $genus;
						$data['family'] = $vData['data'][0]['family'];
						$data['order'] = $vData['data'][0]['order'];
						$data['class'] = $vData['data'][0]['class'];
						$data['phylum'] = $vData['data'][0]['phylum'];
						$data['kingdom'] = $vData['data'][0]['kingdom'];
					}
					$output['success'] = true;
					$output['ocr'] = $ocrValue;
					$output['taxonomicStatus'] = $taxonomicStatus;
					$output['acceptedName'] = $acceptedName;
					$output['dataSource'] = $dataSource;
					$output[$resultName] = $names;
					$output['data'] = $data;
					// $output['tmpData'] =  $tmpData;
					if($flag) return $output;
					
					// return array('success' => true, 'data' => array('family' => $vData['data'][0]['family'],'genus' => $vData['data'][0]['genus'],'scientificName' => $vData['data'][0]['scientificName'],'specificEpithet' => $ar[1], 'phylum' => $vData['data'][0]['phylum'], 'class' => $vData['data'][0]['class'], 'kingdom' => $vData['data'][0]['kingdom'], 'order' => $vData['data'][0]['order'], 'taxonomicStatus' => $taxonomicStatus, 'rawData' => json_encode($names)));
				}
			}
		}

		return array('success' => false);
	}
	
	function get_contents($url) {
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		ob_start();
		curl_exec ($ch);
		curl_close ($ch);
		return ob_get_clean();  
	}
	
	function geographyFilter($filter) {
		global $categories;
		$str = '';
		if($clearFlag) $tables = array('image');
		switch($filter['node']) {
			case 'group':
				$ar =array();
				if(is_array($filter['children']) && count($filter['children'])) {
					foreach($filter['children'] as $child) {
						$dt = geographyFilter($child);
						($dt != '' ) ? $ar[] = $dt : '';
					}
				}
				if(count($ar)) {
					$str .= ' ( ' . implode($filter['logop'], $ar) . ' ) ';
				}
				break;
			case 'condition':
				switch($filter['object']) {
					case 'geographyView':
						$categories[$filter['key']] = $filter['value'];
						if($filter['key'] != '' && $filter['value'] != '') {
							switch($filter['condition']) {
								case '=':
								case '!=':
									$str .= sprintf(" ( `%s` %s '%s' ) " , $filter['key'], $filter['condition'], $filter['value']);
									break;
								case 'is':
									$str .= sprintf(" ( `%s` = '%s' ) " , $filter['key'], $filter['value']);
									break;
								case '%s':
								case 's%':
								case '%s%':
									$op = str_replace('%','%%',$filter['condition']);
									$op = str_replace('s','%s',$op);
									$str .= sprintf(" ( `%s` LIKE '$op' ) " , $filter['key'], $filter['value']);
									break;
								case 'in':
									$str .= sprintf(" ( `%s` IN (%s) ) " , $filter['key'], $filter['value']);
									break;
							}
						}
						break;
				}
				break;
		}
		return $str;
	}
?>