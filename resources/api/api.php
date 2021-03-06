<?php
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('memory_limit', '128M');
	set_time_limit(0);
	session_start();
	ob_start();
	$old_error_handler = set_error_handler("myErrorHandler");

	if (isset($_SERVER['HTTP_ORIGIN'])) {
		header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Max-Age: 86400');    // cache for 1 day
	}

	function stripslashes_r($array) {
	  foreach ($array as $key => $value) {
		$array[$key] = is_array($value) ?
		  stripslashes_r($value) :
		  stripslashes($value);
	  }
	  return $array;
	}

	if (get_magic_quotes_gpc()) {
	  $_GET     = stripslashes_r($_GET);
	  $_POST    = stripslashes_r($_POST);
	  $_COOKIE  = stripslashes_r($_COOKIE);
	  $_REQUEST = stripslashes_r($_REQUEST);
	}
	
	/**
	 * @author SilverBiology
	 * @website http://www.silverbiology.com
	*/

	$expected = array (
			'accountName'
		,	'active'
		,	'admin0'
		,	'admin1'
		,	'admin2'
		,	'admin3'
		,	'advFilter'
		,	'advFilterId'
		,	'api'
		,	'associations'
		,	'attribType'
		,	'attribute'
		,	'attributeId'
		,	'authMode'
		,	'authToken'
		,	'barcode'
		,	'baseUrl'
		,	'basePath'
		,	'browse'
		,	'callback'
		,	'category'
		,	'categoryId'
		,	'categoryType'
		,	'characters'
		,	'characterType'
		,	'code'
		,	'collectionId'
		,	'consumerKey'
		,	'consumerSecret'
		,	'country'
		,	'cmd'
		,	'degree'
		,	'description'
		,	'destinationPath'
		,	'dir'
		,	'elementSet'
		,	'enAccountId'
		,	'ENGTYPE_1'
		,	'eventId'
		,	'eventTypeId'
		,	'expiryDate'
		,	'extra'
		,	'family'
		,	'filename'
		,	'filter'
		,	'force'
		,	'formatHTML'
		,	'genus'
		,	'geoFlag'
		,	'geographyId'
		,	'gridSort'
		,	'group'
		,	'imageId'
		,	'imagePath'
		,	'index'
		,	'ip'
		,	'iso'
		,	'key'
		,	'limit'
		,	'loadFlag'
		,	'method'
		,	'name'
		,	'newStorageId'
		,	'newImagePath'
		,	'nodeApi'
		,	'nodeValue'
		,	'notebookGuid'
		,	'order'
		,	'params'
		,	'parentId'
		,	'password'
		,	'rank'
		,	'rating'
		,	'referencePath'
		,	'remoteAccessId'
		,	'searchFormat'
		,	'searchType'
		,	'searchValue'
		,	'setId'
		,	'setValueId'
		,	'showBarcode'
		,	'showNames'
		,	'showOCR'
		,	'size'
		,	'sort'
		,	'start'
		,	'statusType'
		,	'stop'
		,	'storageDeviceId'
		,	'stream'
		,	'tag'
		,	'term'
		,	'tiles'
		,	'type'
		,	'types'
		,	'url'
		,	'useRating'
		,	'userName'
		,	'useStatus'
		,	'title'
		,	'userId'
		,	'value'
		,	'varname'
		,	'zoom'
	);

	// # Initialize allowed variables
	// foreach ($expected as $formvar)
		// $$formvar = (isset(${"_$_SERVER[REQUEST_METHOD]"}[$formvar])) ? ${"_$_SERVER[REQUEST_METHOD]"}[$formvar]:NULL;

	# Getting session in variable and closing the session for writing
	$_tSESSION = $_SESSION;
	session_write_close();
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
		
		include_once( dirname($_SERVER['PHP_SELF']) . '/../../config.dynamic.php');
	} else {
		include_once('../../config.dynamic.php');
	}

	
	/**
	* Function myErrorHandler
	* Used to catch any errors and send back a custom json error message.
	*/
	function myErrorHandler($errno, $errstr, $errfile, $errline) {
		global $allowed_ips, $config;
		switch ($errno) {

			case E_USER_ERROR:
				$msg = "ERROR [$errno] $errstr";
				$msg .= "  Fatal error on line $errline in file $errfile";
				$msg .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")";
				$msg .= "Aborting...";
				print_c( json_encode( array( 'success' => false,  'error' => array('msg' => $msg , 'code' => $errno ) ) ) );
				if($config['report_software_errors']) {
					$get = urlencode(json_encode( array( 'datetime' => date('d-M-Y'), 'license' => $config['license'], 'version' => $config['version'], 'errorno' => $errno, 'errorstr' => $errstr, 'errline' => $errline, 'errfile' => $errfile ) ));
					@file_get_contents( $config['error_report_path'] .  '?log=' . $get );
				}
				exit(1);
				break;
/*			
			case E_USER_WARNING:
				echo "<b>WARNING</b> [$errno] $errstr<br />\n";
				break;
			
			case E_USER_NOTICE:
				echo "<b>NOTICE</b> [$errno] $errstr<br />\n";
				break;
			
			default:
				echo "Unknown error type: [$errno] $errstr<br />\n";
				break;
*/
		}
		
		/* Don't execute PHP internal error handler */
		return true;
	}
	
	
	$path = $config['path']['base'] . "resources/api/classes/";
	set_include_path(get_include_path() . PATH_SEPARATOR . $path);

	require_once("classes/bis.php");
	require_once("classes/access_user/access_user_class.php");
	
	if($config['flkr']['enabled']) {
		require_once("classes/phpFlickr/phpFlickr.php");
	}
	
	$si = new SilverImage($config['mysql']['name']);
	$userAccess = new Access_user($config['mysql']['host'], $config['mysql']['user'], $config['mysql']['pass'], $config['mysql']['name']);

	$GET = ${"_$_SERVER[REQUEST_METHOD]"};
	if(isset($GET['api'])) $api = $GET['api'];
	
	# This is the output type that the program needs to return, defaults to json
	if(!isset($api)) {
		$api = "json";
	}
	$authMode = $GET['authMode'];
	$key = $GET['key'];

	# This will control the incoming processes that need to be preformed.
	$userAccess->db = &$si->db;
	$si->setAuthMode($authMode);
	$timeStart = microtime(true);
	
	function router($data = null) {
		global $config, $expected, $si, $userAccess,$GET;
		
		$valid = true;
		$errorCode = 0;
		$timeStart = microtime(true);
		
		if ($data != null) {
			// go through and set all the values from the json object which should fake a normal request
			foreach($data as $key => $val) {
				if(in_array($key,$expected))
					$$key = $val;
			}
		} else {
		// print_r($expected);
			# Initialize allowed variables
			foreach ($expected as $formvar)
				$$formvar = (isset($GET[$formvar])) ? $GET[$formvar]:NULL;
		}

		# This is the output type that the program needs to return, defaults to json
		if(!isset($api)) {
			$api = "json";
		}

		# Type of command to perform
		switch( $cmd ) {

			case 'advFilterAdd':
				checkAuth();
				if($name == '') {
					$valid = false;
					$errorCode = 148;
				} else if ($si->advFilter->advFilterNameExists($name)) {
					$valid = false;
					$errorCode = 221;
				}
				$filter = trim($filter);
				$filter1 = json_decode($filter,true);
				if(!(is_array($filter1) && count($filter1))) {
					$valid = false;
					$errorCode = 222;
				}
				if($valid) {
					$si->advFilter->advFilterSetProperty('name', $name);
					$si->advFilter->advFilterSetProperty('description', $description);
					$si->advFilter->advFilterSetProperty('filter', $filter);
					$si->advFilter->advFilterSetProperty('lastModifiedBy', $_SESSION['user_id']);
					if(false === ($id = $si->advFilter->advFilterSave())) {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(223)) ));
					} else {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'advFilterId' => $id ) ) );
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'advFilterDelete':
				checkAuth();
				if($advFilterId==''){
					$valid = false;
					$errorCode = 226;
				} elseif(!$si->advFilter->advFilterLoadById($advFilterId)) {
					$valid = false;
					$errorCode = 227;
				}
				if($valid) {
					if($si->advFilter->advFilterDelete($advFilterId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(224)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'advFilterList':
				checkAuth();
				$data['start'] = ($start == '') ? 0 : $start;
				$data['limit'] = ($limit == '') ? 100 : $limit;
				$data['advFilterId'] = (!is_numeric($advFilterId)) ? json_decode(trim($advFilterId),true) : $advFilterId;
				$data['name'] = $name;
				$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
				$data['value'] = str_replace('%','%%',trim($value));
				$data['group'] = $group;
				$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';
				$si->advFilter->advFilterSetData($data);
				if($valid) {
					$records = $si->advFilter->advFilterList();
					if(is_array($records) && count($records)) {
						foreach($records as &$record) {
							if ($force) {
								$record->filter = json_decode($record->filter,true);
							} else {
								$record->filter = $record->filter;
							}
						}
					}
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $si->advFilter->db->query_total(), 'records' => $records ) ) );
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;
				
			case 'advFilterUpdate':
				checkAuth();
				if($advFilterId ==''){
					$valid = false;
					$errorCode = 226;
				} elseif(!$si->advFilter->advFilterLoadById($advFilterId)) {
					$valid = false;
					$errorCode = 227;
				} else if ($name != '' && $name != $si->advFilter->advFilterGetProperty('name') && $si->advFilter->advFilterNameExists($name)) {
					$valid = false;
					$errorCode = 221;
				}
				if($valid) {
					($name != '' ) ? $si->advFilter->advFilterSetProperty('name', $name) : '';
					($description != '' ) ? $si->advFilter->advFilterSetProperty('description', $description) : '';
					($filter != '' ) ? $si->advFilter->advFilterSetProperty('filter', $filter) : '';
					$si->advFilter->advFilterSetProperty('lastModifiedBy', $_SESSION['user_id']);
					
					if($si->advFilter->advFilterUpdate()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(225)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'attributeAdd':
				checkAuth();
				if($categoryId == '') {
					$valid = false;
					$errorCode = 101;
				} else if(!$si->imageCategory->imageCategoryExists($categoryId)) {
					$valid = false;
					$errorCode = 147;
				} else if($name == '') {
					$valid = false;
					$errorCode = 148;
				} else if($si->imageAttribute->imageAttributeNameExists($name,$categoryId)) {
					$valid = false;
					$errorCode = 228;
				}
				if($valid) {
					$si->imageAttribute->lg->logSetProperty('action', 'attributeAdd');
					$si->imageAttribute->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
					
					$si->imageAttribute->imageAttributeSetProperty('name',$name);
					$si->imageAttribute->imageAttributeSetProperty('categoryId',$categoryId);
					$id = $si->imageAttribute->imageAttributeAdd();
					if($id) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'attributeId' => $id ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(105) ) ) ) ;
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;
				
			case 'attributeDelete':
				checkAuth();
				if($attributeId == "") {
					$valid = false;
					$errorCode = 109;
				} else if (!$si->imageAttribute->imageAttributeExists($attributeId)) {
					$valid = false;
					$errorCode = 149;
				}
				if($valid) {
					$si->imageAttribute->lg->logSetProperty('action', 'attributeDelete');
					$si->imageAttribute->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);

					if($si->imageAttribute->imageAttributeDelete($attributeId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;

			case 'attributeList':
					$showNames = (trim($showNames) == 'false') ? false : true;
					$data['showNames'] = $showNames;
					$data['start'] = (is_numeric($start)) ? $start : 0;
					$data['limit'] = (is_numeric($limit)) ? $limit : 10;
					// $data['code'] = $code;
					$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
					$data['value'] = str_replace('%','%%',trim($value));
					
					$data['categoryId'] = (!is_numeric($categoryId)) ? json_decode(trim($categoryId),true) : $categoryId;
					$data['group'] = trim($group);
					$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';

					if($valid) {
							$si->image->imageSetData($data);
							$ret = $si->image->imageListAttributes();
							$names = array();
							if(!is_null($ret)) {
								while($row = $ret->fetch_object()) {
									if($showNames) {
										in_array($row->name,$names) ? '' : $names[] = $row->name;
									} else {
										$names[] = $row;
									}
								}
							}
							$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $si->image->total, 'records' => $names ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
					}
				break;

			case 'attributeUpdate':
				checkAuth();
				if($attributeId == "") {
					$valid = false;
					$errorCode = 109;
				} else if (!$si->imageAttribute->imageAttributeLoadById($attributeId)) {
					$valid = false;
					$errorCode = 149;
				} else if ($name != '' && $name != $si->imageAttribute->imageAttributeGetProperty('name')) {
					$ctId = (trim($categoryId) != '') ? $categoryId : $si->imageAttribute->imageAttributeGetProperty('categoryId');
					if($si->imageAttribute->imageAttributeNameExists($name,$ctId)) {
						$valid = false;
						$errorCode = 228;
					}
				} else if ($categoryId != '' && $categoryId != $si->imageAttribute->imageAttributeGetProperty('categoryId')) {
					$nme = (trim($name) != '') ? $name : $si->imageAttribute->imageAttributeGetProperty('name');
					if($si->imageAttribute->imageAttributeNameExists($nme,$categoryId)) {
						$valid = false;
						$errorCode = 228;
					}
				}

				if($valid) {
					$si->imageAttribute->lg->logSetProperty('action', 'attributeUpdate');
					$si->imageAttribute->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
					
					(trim($name) != '') ? $si->imageAttribute->imageAttributeSetProperty('name',$name) : '';
					(trim($categoryId) != '') ? $si->imageAttribute->imageAttributeSetProperty('categoryId',$categoryId) : '';

					if($si->imageAttribute->imageAttributeUpdate()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;
				
			case 'categoryAdd':
				checkAuth();
				if($title == "") {
					$valid = false;
					$errorCode = 112;
				} else if ($si->imageCategory->imageCategoryTitleExists($title)) {
					$valid = false;
					$errorCode = 229;
				}
				if($valid) {
					$si->imageCategory->lg->logSetProperty('action', 'categoryAdd');
					$si->imageCategory->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
					
					$si->imageCategory->imageCategorySetProperty('title',$title);
					$si->imageCategory->imageCategorySetProperty('description',$description);
					$si->imageCategory->imageCategorySetProperty('elementSet',$elementSet);
					$si->imageCategory->imageCategorySetProperty('term',$term);
					$id = $si->imageCategory->imageCategoryAdd();
					if($id) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'categoryId' => $id ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(110) ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;

			case 'categoryDelete':
				checkAuth();
				if($categoryId == "") {
					$valid = false;
					$errorCode = 101;
				} else if(!$si->imageCategory->imageCategoryExists($categoryId)) {
					$valid = false;
					$errorCode = 147;
				}
				if($valid) {
					$si->imageCategory->lg->logSetProperty('action', 'categoryDelete');
					$si->imageCategory->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
					
					if($si->imageCategory->imageCategoryDelete($categoryId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(146) ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;

			case 'categoryList':
				$data['start'] = (is_numeric($start)) ? $start : 0;
				$data['limit'] = (is_numeric($limit)) ? $limit : 10;
				$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
				$data['value'] = str_replace('%','%%',trim($value));
				
				$data['categoryId'] = (!is_numeric($categoryId)) ? json_decode(trim($categoryId),true) : $categoryId;
				$data['group'] = trim($group);
				$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';
					
				if($valid) {
					$si->imageCategory->imageCategorySetData($data);
					$rets = $si->imageCategory->imageCategoryList();
					$rets = is_null($rets) ? array() : $rets;
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $si->imageCategory->db->query_total(), 'records' => $rets ) ) );
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;

			case 'categoryUpdate':
				checkAuth();
				if($categoryId == "") {
					$valid = false;
					$errorCode = 101;
				} else if(!$si->imageCategory->imageCategoryLoadById($categoryId)) {
					$valid = false;
					$errorCode = 147;
				} else if ($title != '' && $title != $si->imageCategory->imageCategoryGetProperty('title') && $si->imageCategory->imageCategoryTitleExists($title)) {
					$valid = false;
					$errorCode = 229;
				}
				if($valid) {
					$si->imageCategory->lg->logSetProperty('action', 'categoryUpdate');
					$si->imageCategory->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);

					$si->imageCategory->imageCategorySetProperty('categoryId',$categoryId);
					(trim($title) != '') ? $si->imageCategory->imageCategorySetProperty('title',$title) : '';
					(trim($description) != '') ? $si->imageCategory->imageCategorySetProperty('description',$description) : '';
					(trim($elementSet) != '') ? $si->imageCategory->imageCategorySetProperty('elementSet',$elementSet) : '';
					(trim($term) != '') ? $si->imageCategory->imageCategorySetProperty('term',$term) : '';
					if($si->imageCategory->imageCategoryUpdate()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(111) ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;
				
			case 'collectionAdd':
				checkAuth();
				if($name == '' || $code == '') {
					$valid = false;
					$errorCode = 125;
				}
				if($valid) {
					$si->collection->lg->logSetProperty('action', 'collectionAdd');
					$si->collection->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
					
					$si->collection->collectionSetProperty('name', $name);
					$si->collection->collectionSetProperty('code', $code);
					
					if($si->collection->collectionSave()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'collectionId' => $si->collection->insert_id ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray(127) ) ));
					}
				} else {
					$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray($errorCode) ) ));
				}
				break;
				
			case 'collectionDelete':
				checkAuth();
				if($collectionId == '') {
					$valid = false;
					$errorCode = 126;
				} else if(!$si->collection->collectionLoadById($collectionId)) {
					$valid = false;
					$errorCode = 128;
				}
				if($valid) {
					if($si->collection->collectionDelete($collectionId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart) ) );
					} else {
						$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray(127) ) ));
					}
				} else {
					$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray($errorCode) ) ));
				}
				break;


			case 'collectionList':
				$data['start'] = ($start == '') ? 0 : $start;
				$data['limit'] = ($limit == '') ? 25 : $limit;
				$data['collectionId'] = (!is_numeric($collectionId)) ? json_decode(trim($collectionId),true) : $collectionId;
				$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
				$data['value'] = str_replace('%','%%',trim($value));
				$data['group'] = $group;
				$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';
				$data['code'] = $code;

				if($valid) {
					$si->collection->collectionSetData($data);
					$ret = $si->collection->collectionList();
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $si->collection->db->query_total(), 'records' => $ret ) ) );
				} else {
					$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;

			case 'collectionUpdate':
				checkAuth();
				if($collectionId == '') {
					$valid = false;
					$errorCode = 126;
				} else if(!$si->collection->collectionLoadById($collectionId)) {
					$valid = false;
					$errorCode = 128;
				}

				if($valid) {
					$si->collection->lg->logSetProperty('action', 'collectionUpdate');
					$si->collection->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);

					($name != '') ? $si->collection->collectionSetProperty('name', $name) : '';
					($code != '') ? $si->collection->collectionSetProperty('code', $code) : '';
					($collectionSize != '') ? $si->collection->collectionSetProperty('collectionSize', $collectionSize) : '';
					if($si->collection->collectionSave()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(129) ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;
				
				
			case 'eventAdd':
				checkAuth();
				if(trim($title) == '') {
					$valid = false;
					$errorCode = 112;
				}
				if(trim($eventTypeId) == '') {
					$valid = false;
					$errorCode = 113;
				}

				if($valid) {
					$si->event->lg->logSetProperty('action', 'eventAdd');
					$si->event->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);

					$si->event->eventsSetProperty('geographyId', $geographyId);
					$si->event->eventsSetProperty('eventTypeId', $eventTypeId);
					$si->event->eventsSetProperty('title', $title);
					$si->event->eventsSetProperty('description', $description);
					$si->event->eventsSetProperty('lastModifiedBy', $_SESSION['user_id']);
					if($si->event->eventsSave()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'eventId' => $si->event->insert_id ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(114) ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;

			case 'eventDelete':
				checkAuth();
				if($eventId == '') {
					$valid = false;
					$errorCode = 115;
				} else if(!$si->event->eventsLoadById($eventId)) {
					$valid = false;
					$errorCode = 117;
				}
				if($valid) {
					$si->event->lg->logSetProperty('action', 'eventDelete');
					$si->event->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
					if($si->event->eventsDelete($eventId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(116) ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;

			case 'eventList':
				$data['start'] = ($start == '') ? 0 : $start;
				$data['limit'] = ($limit == '') ? 100 : $limit;
				$data['eventId'] = (!is_numeric($eventId)) ? json_decode(trim($eventId),true) : $eventId;
				$data['eventTypeId'] = (!is_numeric($eventTypeId)) ? json_decode(trim($eventTypeId),true) : $eventTypeId;
				$data['geographyId'] = (!is_numeric($geographyId)) ? json_decode(trim($geographyId),true) : $geographyId;
				$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
				$data['value'] = str_replace('%','%%',trim($value));
				$data['geoFlag'] = (strtolower(trim($geoFlag)) == 'true') ? true : false;
				$data['group'] = $group;
				$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';

				if($valid) {
					$si->event->eventsSetData($data);
					$ret = $si->event->eventsListRecords();
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $si->event->db->query_total(), 'records' => $ret ) ) );
				} else {
					$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;

			case 'eventUpdate':
				checkAuth();
				if($eventId == '') {
					$valid = false;
					$errorCode = 115;
				} else if(!$si->event->eventsLoadById($eventId)) {
					$valid = false;
					$errorCode = 117;
				}

				if($valid) {
					$si->event->lg->logSetProperty('action', 'addEvent');
					$si->event->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);

					($geographyId != '') ? $si->event->eventsSetProperty('geographyId', $geographyId) : '';
					($eventTypeId != '') ? $si->event->eventsSetProperty('eventTypeId', $eventTypeId) : '';
					($title != '') ? $si->event->eventsSetProperty('title', $title) : '';
					($description != '') ? $si->event->eventsSetProperty('description', $description) : '';
					$si->event->eventsSetProperty('lastModifiedBy', $_SESSION['user_id']);
					if($si->event->eventsSave()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(118) ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;


			case 'eventTypeAdd':
				checkAuth();
				if(trim($title) == '') {
					$valid = false;
					$errorCode = 112;
				}
				if($valid) {
					$si->eventType->lg->logSetProperty('action', 'eventTypeAdd');
					$si->eventType->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);

					$si->eventType->eventTypesSetProperty('title', $title);
					$si->eventType->eventTypesSetProperty('description', $description);
					$si->eventType->eventTypesSetProperty('lastModifiedBy', $_SESSION['user_id']);
					
					if($si->eventType->eventTypesSave()) {
						if(false == $si->eventType->insert_id) {
							$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(123) ) ) );
						} else {
							$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'eventTypeId' => $si->eventType->insert_id ) ) );
						}
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(119) ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;

			case 'eventTypeDelete':
				checkAuth();
				if($eventTypeId == '') {
					$valid = false;
					$errorCode = 113;
				} elseif(!$si->eventType->eventTypesLoadById($eventTypeId)) {
					$valid = false;
					$errorCode = 120;
				}
				if($valid) {
					$si->eventType->lg->logSetProperty('action', 'deleteEventType');
					$si->eventType->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
					
					if($si->eventType->eventTypesDelete($eventTypeId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(121) ) ) );
					}

				} else {
					$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;

			case 'eventTypeList':
				$data['start'] = ($start == '') ? 0 : $start;
				$data['limit'] = ($limit == '') ? 100 : $limit;
				$data['eventTypeId'] = (!is_numeric($eventTypeId)) ? json_decode(trim($eventTypeId),true) : $eventTypeId;
				$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
				$data['value'] = str_replace('%','%%',trim($value));
				$data['group'] = $group;
				$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';

				if($valid) {
					$si->eventType->eventTypesSetData($data);
					$ret = $si->eventType->eventTypesListRecords();
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $si->eventType->db->query_total(), 'records' => $ret ) ) );
				} else {
					$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;

			case 'eventTypeUpdate':
				checkAuth();
				if($eventTypeId == '') {
					$valid = false;
					$errorCode = 113;
				} elseif(!$si->eventType->eventTypesLoadById($eventTypeId)) {
					$valid = false;
					$errorCode = 120;
				}

				if($title != '') {
					if($si->eventType->eventTypesTitleExists($title)) {
						if($si->eventType->eventTypesGetProperty("title") != $title) {
							$valid = false;
							$errorCode = 218;
						}
					}
				}
				
				if($valid) {
					$si->eventType->lg->logSetProperty('action', 'addEventType');
					$si->eventType->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);

					($title != '') ? $si->eventType->eventTypesSetProperty('title', $title) : '';
					($description != '') ? $si->eventType->eventTypesSetProperty('description', $description) : '';
					$si->eventType->eventTypesSetProperty('lastModifiedBy', $_SESSION['user_id']);
					if($si->eventType->eventTypesSave()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(122) ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;
				
			case 'evernoteAccountAdd':
				checkAuth();
				if($accountName=='' || $consumerKey=='' || $consumerSecret=='' || $notebookGuid=='') {
					$valid = false;
					$errorCode = 131;
				} else if ($si->en->evernoteAccountsExists($accountName)) {
					$valid = false;
					$errorCode = 132;
				}
				if($valid) {
					$data['accountName'] = $accountName;
					// $data['userName'] = $userName;
					// $data['password'] = $password;
					$data['consumerKey'] = $consumerKey;
					$data['consumerSecret'] = $consumerSecret;
					$data['notebookGuid'] = $notebookGuid;
					$data['rank'] = (trim($rank)!='') ? $rank : 0;
					$data['authToken'] = (trim($authToken)!='') ? $authToken : '';
					$data['expiryDate'] = (trim($expiryDate)!='') ? $expiryDate : '';
					$si->en->evernoteAccountsSetAllData($data);
					if(false === ($id = $si->en->evernoteAccountsAdd())) {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(135)) ));
					} else {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'enAccountId' => $id ) ) );
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'evernoteAccountDelete':
				checkAuth();
				if($enAccountId==''){
					$valid = false;
					$errorCode = 133;
				} elseif(!$si->en->evernoteAccountsLoadById($enAccountId)) {
					$valid = false;
					$errorCode = 134;
				}
				if($valid) {
					if($si->en->evernoteAccountsDelete($enAccountId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(136)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'evernoteAccountList':
				checkAuth();
				$data['start'] = ($start == '') ? 0 : $start;
				$data['limit'] = ($limit == '') ? 100 : $limit;
				$data['enAccountId'] = (!is_numeric($enAccountId)) ? json_decode(trim($enAccountId),true) : $enAccountId;
				$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
				$data['value'] = str_replace('%','%%',trim($value));
				$data['group'] = $group;
				$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';
				$si->en->evernoteAccountsSetData($data);
				if($valid) {
					$accounts = $si->en->evernoteAccountsList();
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $si->en->db->query_total(), 'records' => $accounts ) ) );
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;
				
			case 'evernoteAccountUpdate':
				checkAuth();
				if($enAccountId==''){
					$valid = false;
					$errorCode = 133;
				} else if(!$si->en->evernoteAccountsLoadById($enAccountId)) {
					$valid = false;
					$errorCode = 134;
				} else if ($accountName!='' && $accountName != $si->en->evernoteAccountsGetProperty('accountName') && $si->en->evernoteAccountsExists($accountName)) {
					$valid = false;
					$errorCode = 132;
				}
				if($valid) {
					if($accountName!='') $data['accountName'] = $accountName;
					// if($userName!='') $data['userName'] = $userName;
					// if($password!='') $data['password'] = $password;
					if($consumerKey!='') $data['consumerKey'] = $consumerKey;
					if($consumerSecret!='') $data['consumerSecret'] = $consumerSecret;
					if($notebookGuid!='') $data['notebookGuid'] = $notebookGuid;
					if($rank!='') $data['rank'] = $rank;
					if($authToken!='') $data['authToken'] = $authToken;
					if($expiryDate!='') $data['expiryDate'] = $expiryDate;
					$si->en->evernoteAccountsSetAllData($data);
					if($si->en->evernoteAccountsUpdate()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(137)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'evernoteSearchByLabel':
				$value = urldecode(trim($value));
				$enAccountId = trim($enAccountId);
				if($value == '') {
					$valid = false;
					$errorCode = 102;
				} elseif($enAccountId!='' && !$si->en->evernoteAccountsFieldExists($enAccountId)) {
					$valid = false;
					$errorCode = 134;
				}
				if($valid) {
					$tag = (trim($tag)!='') ? $tag : '';
					if($tag != '') {
						$tagRecord = $si->en->evernoteTagsLoadByTagName($tag);
						if($tagRecord) {
							$tag = $tagRecord['tagGuid'];
						} else {
							$tag = '';
						}
					}
					$start = (trim($start) == '') ? 0 : trim($start);
					$limit = (trim($limit) == '') ? 25 : trim($limit);
					$data = array();
					$si->en->evernoteAccountsSetData(array('enAccountId' => $enAccountId));
					$accounts = $si->en->evernoteAccountsList();
					$totalNotes = 0;
					if(is_array($accounts) && count($accounts)) {
						$limit = ceil($limit/(count($accounts)));
						foreach($accounts as $account) {
							$acnt = array(
								'enAccountId' => $account['enAccountId']
								, 'notebookGuid' => $account['notebookGuid']
								, 'authToken' => $account['authToken']
							);
							$evernote_details_json = json_encode($acnt);

							$url = $config['evernoteUrl'];
							$url .= '?cmd=findNotes&auth=[' . $evernote_details_json . ']&start=' . $start . '&limit=' . $limit . '&searchWord=' . urlencode($value);
							if($tag != '') $url .= '&tag=[\"'.$tag.'\"]';
							$rr = json_decode(@file_get_contents($url),true);
							if($rr['success']) {
								//$totalNotes += $rr['totalNotes'];
								$labels = $rr['data'];
								if(is_array($labels) && count($labels)) {
									foreach($labels as $label) {
										if(!array_key_exists($label,$data)) {
											$ar = array();
											if(!$si->s2l->load_by_labelId($label)) continue;
											$totalNotes++;
											$si->image->imageSetData(array("field"=>"barcode", "value"=>$si->s2l->get('barcode'), "start"=>0, "limit"=>$limit));
											$ar = $si->image->imageList();
											$ar = $ar[0];
											$tmpPath = $si->image->imageGetUrl($ar->imageId);
											$ar->path = $tmpPath['baseUrl'];
											$fname = explode(".", $ar->filename);
											$ar->ext = @array_pop($fname);
											$ar->en_flag = ($si->s2l->load_by_barcode($ar->barcode)) ? 1 : 0;
											$data[$label] = $ar;
										}
									}
								}
							} # if rr[success]
						} # for
					} # if labels
					$data = @array_values($data);
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $totalNotes, 'records' => $data ) ));
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}

				break;

	# Image commands

			// case 'imageAddAttribute':
				// checkAuth();
				// $data['imageId'] = $imageId;
				// $categoryType = in_array(trim($categoryType),array('categoryId','title','term')) ? trim($categoryType) : 'categoryId';
				// $attribType = in_array(trim($attribType),array('attributeId','name')) ? trim($attribType) : 'attributeId';
				// $force = (trim($force) == 'true') ? true : false;
				// if($advFilterId != '') {
					// if($si->advFilter->advFilterLoadById($advFilterId)) {
						// $advFilter  = $si->advFilter->advFilterGetProperty('filter');
					// }
				// }
				// $data['advFilter'] = json_decode(trim($advFilter),true);
				// if($data['imageId'] == "" && !(is_array($data['advFilter']) && count($data['advFilter']))) {
					// $valid = false;
					// $errorCode = 220;
				// } elseif(trim($category) == '') {
					// $valid = false;
					// $errorCode = 160;
				// } elseif(trim($attribute) == '') {
					// $valid = false;
					// $errorCode = 159;
				// } else {
					// if(false === ($data['categoryId'] = $si->imageCategory->imageCategoryGetBy($category,$categoryType))) {
						// if ($force) {
							// $si->imageCategory->imageCategorySetProperty($categoryType,$category);
							// $id = $si->imageCategory->imageCategoryAdd();
							// $data['categoryId'] = $id;
						// } else {
							// $valid = false;
							// $errorCode = 147;
						// }
					// }
					// if(false === ($data['attributeId'] = $si->imageAttribute->imageAttributeGetBy($attribute,$attribType,$data['categoryId']))) {
						// if ($force && $attribType == 'name') {
							// $si->imageAttribute->imageAttributeSetProperty('name',$attribute);
							// $si->imageAttribute->imageAttributeSetProperty('categoryId',$data['categoryId']);
							// $id = $si->imageAttribute->imageAttributeAdd();
							// $data['attributeId'] = $id;
						// } else {
							// $valid = false;
							// $errorCode = 149;
						// }
					// }
				// }
				// if($valid) {
					// $si->image->imageSetData($data);
					// if($si->image->imageAttributeAdd()) {
						// $response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					// } else {
						// $response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(161)) ));
					// }
				// } else {
					// $response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				// }
				// break;

			case 'imageAddAttribute':
				checkAuth();
				
				if($imageId != '') {
					if(!is_numeric($imageId)) {
						$imageId = json_decode($imageId,true);
					} else {
						$imageId = array($imageId);
					}
				}
				if($barcode != '') {
					$barcode = json_decode($barcode,true);
				}
				$categoryType = in_array(trim($categoryType),array('categoryId','title','term')) ? trim($categoryType) : 'categoryId';
				$attribType = in_array(trim($attribType),array('attributeId','name')) ? trim($attribType) : 'attributeId';
				if (trim($force) == 'true') $force = true;
				if($advFilterId != '') {
					if($si->advFilter->advFilterLoadById($advFilterId)) {
						$advFilter  = $si->advFilter->advFilterGetProperty('filter');
					}
				}
				$data['advFilter'] = json_decode(trim($advFilter),true);
				if(trim($category) == '') {
					$valid = false;
					$errorCode = 160;
				} elseif(trim($attribute) == '') {
					$valid = false;
					$errorCode = 159;
				} else {
					if(false === ($data['categoryId'] = $si->imageCategory->imageCategoryGetBy($category,$categoryType))) {
						if ($force) {
							$si->imageCategory->lg->logSetProperty('action', 'imageAddAttribute');
							$si->imageCategory->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
					
							$si->imageCategory->imageCategorySetProperty($categoryType,$category);
							$id = $si->imageCategory->imageCategoryAdd();
							$data['categoryId'] = $id;
						} else {
							$valid = false;
							$errorCode = 147;
						}
					}
					if(false === ($data['attributeId'] = $si->imageAttribute->imageAttributeGetBy($attribute, $attribType, $data['categoryId']))) {
						if ($force && $attribType == 'name') {
							$si->imageAttribute->lg->logSetProperty('action', 'imageAddAttribute');
							$si->imageAttribute->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
							$si->imageAttribute->imageAttributeSetProperty('name',$attribute);
							$si->imageAttribute->imageAttributeSetProperty('categoryId',$data['categoryId']);
							$id = $si->imageAttribute->imageAttributeAdd();
							$data['attributeId'] = $id;
						} else {
							$valid = false;
							$errorCode = 149;
						}
					}
				}
				if($valid) {
					$si->image->lg->logSetProperty('action', 'imageAddAttribute');
					$si->image->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
					
					$data['imageId'] = $imageId;
					if(is_array($barcode)) {
						$data['barcode'] = $barcode;
					}
					$si->image->imageSetData($data);
					if($si->image->imageAttributeAdd()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray(161)) ));
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageAddFromLocal':
				$storageDeviceId = (trim($storageDeviceId)!='') ? $storageDeviceId : $si->storage->storageDeviceGetDefault();
				if(!$si->remoteAccess->remoteAccessCheck(ip2long($_SERVER['REMOTE_ADDR']), $key)) {
					$errorCode = 103;
					$valid = false;
				} else if($storageDeviceId == '' || !$si->storage->storageDeviceExists($storageDeviceId)) {
					$errorCode = 156;
					$valid = false;
				} elseif($filename=='') {
					$errorCode = 163;
					$valid = false;
				} else {
					$config['allowedImportTypes'] = array(1,2,3); //GIF, JPEG, PNG
					# http://www.php.net/manual/en/function.exif-imagetype.php
					$stream = ($stream != '') ? $stream : '';
					if((strpos($filename,'/')) !== false) {
						$tmpFilename = explode('/', $filename);
						$filename = $tmpFilename[count($tmpFilename)-1];
					}
					$tmp = sys_get_temp_dir() . '/' . $filename;
					file_put_contents($tmp, $stream);
					$size = getimagesize($tmp);
					if(!in_array($size[2],$config['allowedImportTypes'])) {
						$errorCode = 164;
						$valid = false;
					}
				}
				if($valid) {
					$bcode = @explode('.',$filename);
					array_pop($bcode);
					$bcode = @implode('.',$bcode);
					$imagePath = (isset($imagePath) && $imagePath != '')?$imagePath:$bcode . '/';
				
					$response = $si->storage->storageDeviceStore($tmp,$storageDeviceId,$filename, $imagePath, $key);
					$iEXd = new EXIFread($tmp);
					unlink($tmp);
					if($response['success']) {
						$si->pqueue->processQueueSetProperty('imageId', $response['imageId']);
						$si->pqueue->processQueueSetProperty('processType','all');
						$si->pqueue->processQueueSave();
						
						if($barcode != '' || $code != '') {
							if($si->image->imageLoadById($response['imageId'])) {
								$si->image->imageSetProperty('barcode',$barcode);
								if($si->collection->collectionCodeExists($code)) {
									$si->image->imageSetProperty('collectionCode',$code);
								}
								$si->image->imageSave();
							}
						}
						
						# Add latitude and longitude - Start
						if($gps = $iEXd->getGPS()) {
							$si->imageCategory->lg->logSetProperty('action', 'imageAddFromLocal');
							$si->imageCategory->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
							$si->image->lg->logSetProperty('action', 'imageAddFromLocal');
							$si->image->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
							$si->imageAttribute->lg->logSetProperty('action', 'imageAddFromLocal');
							$si->imageAttribute->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);


							$catId = 0;
							$atrId = 0;
							$catArray = $si->imageCategory->imageCategoryList();
							if(is_array($catArray)) {
								foreach($catArray as $cat) {
									if($cat['title'] == 'Latitude') {
										$catId = $cat['categoryId'];
										break;
									}
								}
							}
							if(!$catId) {
								$si->imageCategory->imageCategorySetProperty('title', 'Latitude');
								$catId = $si->imageCategory->imageCategoryAdd();
							}
							$atrArray = $si->imageAttribute->imageAttributeList($catId);
							if(is_array($atrArray)) {
								foreach($atrArray as $atr) {
									if($atr['name'] == $gps['Latitude']) {
										$atrId = $atr['attributeId'];
										break;
									}
								}
							}
							if(!$atrId) {
								$si->imageAttribute->imageAttributeSetProperty('name',$gps['Latitude']);
								$si->imageAttribute->imageAttributeSetProperty('categoryId',$catId);
								$atrId = $si->imageAttribute->imageAttributeAdd();
							}
							$data['imageId'] = $response['imageId'];
							$data['attributeId'] = $atrId;
							$data['categoryId'] = $catId;
							$si->image->imageSetData($data);
							$si->image->imageAttributeAdd();
							
							$catId = 0;
							$atrId = 0;
							if(is_array($catArray)) {
								foreach($catArray as $cat) {
									if($cat['title'] == 'Longitude') {
										$catId = $cat['categoryId'];
										break;
									}
								}
							}
							if(!$catId) {
								$si->imageCategory->imageCategorySetProperty('title', 'Longitude');
								$catId = $si->imageCategory->imageCategoryAdd();
							}
							$atrArray = $si->imageAttribute->imageAttributeList($catId);
							if(is_array($atrArray)) {
								foreach($atrArray as $atr) {
									if($atr['name'] == $gps['Longitude']) {
										$atrId = $atr['attributeId'];
										break;
									}
								}
							}
							if(!$atrId) {
								$si->imageAttribute->imageAttributeSetProperty('name',$gps['Longitude']);
								$si->imageAttribute->imageAttributeSetProperty('categoryId',$catId);
								$atrId = $si->imageAttribute->imageAttributeAdd();
							}
							$data['imageId'] = $response['imageId'];
							$data['attributeId'] = $atrId;
							$data['categoryId'] = $catId;
							$si->image->imageSetData($data);
							$si->image->imageAttributeAdd();
						}
						# Add latitude and longitude - End
						
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'imageId' => $response['imageId'] ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(165)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'imageAddFromDnd':
				checkAuth();
				$storageDeviceId = (trim($storageDeviceId)!='') ? $storageDeviceId : $si->storage->storageDeviceGetDefault();
				if($storageDeviceId == '' || !$si->storage->storageDeviceExists($storageDeviceId)) {
					$errorCode = 156;
					$valid = false;
				} elseif($filename=='') {
					$errorCode = 163;
					$valid = false;
				} else {
					$config['allowedImportTypes'] = array(1,2,3); //GIF, JPEG, PNG
					# http://www.php.net/manual/en/function.exif-imagetype.php
					$stream = ($stream != '') ? $stream : '';
					$stream = str_replace(' ','+',$stream);
					$stream = base64_decode($stream);
					if((strpos($filename,'/')) !== false) {
						$tmpFilename = explode('/', $filename);
						$filename = $tmpFilename[count($tmpFilename)-1];
					}
					$tmp = sys_get_temp_dir() . '/' . $filename;
					file_put_contents($tmp, $stream);
					$size = getimagesize($tmp);
					if(!in_array($size[2],$config['allowedImportTypes'])) {
						$errorCode = 164;
						$valid = false;
					}
				}
				if($valid) {
					$bcode = @explode('.',$filename);
					array_pop($bcode);
					$bcode = @implode('.',$bcode);
					$imagePath = (isset($imagePath))?$imagePath:$bcode . '/';
				
					$response = $si->storage->storageDeviceStore($tmp,$storageDeviceId,$filename, $imagePath, $key);
					$iEXd = new EXIFread($tmp);
					unlink($tmp);				
//					var_dump($response);
					if($response['success']) {
						$si->pqueue->processQueueSetProperty('imageId', $response['imageId']);
						$si->pqueue->processQueueSetProperty('processType','all');
						$si->pqueue->processQueueSave();
						
						# Add latitude and longitude - Start
						if($gps = $iEXd->getGPS()) {
							$si->imageCategory->lg->logSetProperty('action', 'imageAddFromDnd');
							$si->imageCategory->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
							$si->image->lg->logSetProperty('action', 'imageAddFromDnd');
							$si->image->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
							$si->imageAttribute->lg->logSetProperty('action', 'imageAddFromDnd');
							$si->imageAttribute->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
							
							$catId = 0;
							$atrId = 0;
							$catArray = $si->imageCategory->imageCategoryList();
							if(is_array($catArray)) {
								foreach($catArray as $cat) {
									if($cat['title'] == 'Latitude') {
										$catId = $cat['categoryId'];
										break;
									}
								}
							}
							if(!$catId) {
								$si->imageCategory->imageCategorySetProperty('title', 'Latitude');
								$catId = $si->imageCategory->imageCategoryAdd();
							}
							$atrArray = $si->imageAttribute->imageAttributeList($catId);
							if(is_array($atrArray)) {
								foreach($atrArray as $atr) {
									if($atr['name'] == $gps['Latitude']) {
										$atrId = $atr['attributeId'];
										break;
									}
								}
							}
							if(!$atrId) {
								$si->imageAttribute->imageAttributeSetProperty('name',$gps['Latitude']);
								$si->imageAttribute->imageAttributeSetProperty('categoryId',$catId);
								$atrId = $si->imageAttribute->imageAttributeAdd();
							}
							$data['imageId'] = $response['imageId'];
							$data['attributeId'] = $atrId;
							$data['categoryId'] = $catId;
							$si->image->imageSetData($data);
							$si->image->imageAttributeAdd();
							
							$catId = 0;
							$atrId = 0;
							if(is_array($catArray)) {
								foreach($catArray as $cat) {
									if($cat['title'] == 'Longitude') {
										$catId = $cat['categoryId'];
										break;
									}
								}
							}
							if(!$catId) {
								$si->imageCategory->imageCategorySetProperty('title', 'Longitude');
								$catId = $si->imageCategory->imageCategoryAdd();
							}
							$atrArray = $si->imageAttribute->imageAttributeList($catId);
							if(is_array($atrArray)) {
								foreach($atrArray as $atr) {
									if($atr['name'] == $gps['Longitude']) {
										$atrId = $atr['attributeId'];
										break;
									}
								}
							}
							if(!$atrId) {
								$si->imageAttribute->imageAttributeSetProperty('name',$gps['Longitude']);
								$si->imageAttribute->imageAttributeSetProperty('categoryId',$catId);
								$atrId = $si->imageAttribute->imageAttributeAdd();
							}
							$data['imageId'] = $response['imageId'];
							$data['attributeId'] = $atrId;
							$data['categoryId'] = $catId;
							$si->image->imageSetData($data);
							$si->image->imageAttributeAdd();
						}
						# Add latitude and longitude - End
						
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'imageId' => $response['imageId'] ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(165)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageAddFromExisting':
				checkAuth();
				if($imagePath != '') {
					$imagePath = '/' . ltrim($imagePath,'/');
				}
				if($storageDeviceId == '' || $imagePath == '' || $filename == '') {
					$valid = false;
					$errorCode = 124;
				} elseif(!$si->storage->storageDeviceExists($storageDeviceId)) {
					$valid = false;
					$errorCode = 156;
				} elseif(!$si->image->imageExists($storageDeviceId, $imagePath, $filename)) {
					$valid = false;
					$errorCode = 168;
				} else {
					$valid = true;
				}
				if($valid) {
					$imageId = $si->image->imageGetId($filename, $imagePath, $storageDeviceId);
					if(!$imageId) {
						$device = $si->storage->storageDeviceGet($storageDeviceId);
						$ar = @getimagesize($device['basePath'] . '/' . $imagePath . '/' . $filename);
						
						$si->image->imageSetProperty('width',$ar[0]);
						$si->image->imageSetProperty('height',$ar[1]);
						$si->image->imageSetProperty('filename',$filename);
						$si->image->imageSetProperty('storageDeviceId', $storageDeviceId);
						$si->image->imageSetProperty('path', $imagePath);
						$si->image->imageSetProperty('originalFilename', $filename);
						
						if($si->image->imageSave()) {
							$imageId = $si->image->insert_id;
							$si->pqueue->processQueueSetProperty('imageId', $imageId);
							$si->pqueue->processQueueSetProperty('processType','all');
							$si->pqueue->processQueueSave();
							$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'imageId' => $imageId ) ) );
						} else {
							$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(167)) ));
						}
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(166)) ));
					}
					
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageAddFromForm':
				checkAuth();
				// if(!$si->remoteAccess->remoteAccessCheck(ip2long($_SERVER['REMOTE_ADDR']), $key)) {
					// $errorCode = 171;
					// $valid = false;
				// }

				if($valid) {
					$results = array();
					$totalCount = 0;

					$config["allowedImportTypes"] = array(1,2,3); //GIF, JPEG, PNG
					$storageDeviceId = (trim($storageDeviceId)!='') ? $storageDeviceId : $si->storage->storageDeviceGetDefault();
					if($storageDeviceId=='' || !$si->storage->storageDeviceExists($storageDeviceId)) {
						$results[$i] = array( 'success' => false,  'error' => array( 'success' => false, 'error' => $si->getErrorArray(156)) );
						continue;
					}
					
					for($i=0;$i<count($_FILES["filename"]["name"]);$i++) {
						$bcode = @explode('.',$_FILES["filename"]["name"]);
						array_pop($bcode);
						$bcode = @implode('.',$bcode);
						$imagePath[$i] = (isset($imagePath[$i]))?$imagePath[$i]:$bcode . '/';
						if ($_FILES["filename"]["error"][$i] > 0) {
							$results[$i] = array( 'success' => false,  'error' => array('msg' => $_FILES["filename"]["error"][$i]) );
							continue;
						}
						# http://www.php.net/manual/en/function.exif-imagetype.php
						$size = getimagesize($_FILES["filename"]["tmp_name"][$i]);
						if(in_array($size[2],$config["allowedImportTypes"])) {
							$response = $si->storage->storageDeviceStore($_FILES["filename"]["tmp_name"][$i],$storageDeviceId, $_FILES["filename"]["name"][$i], $imagePath[$i], $key);
							$iEXd = new EXIFread($_FILES["filename"]["tmp_name"][$i]);
							if($response['success']) {
								$si->pqueue->processQueueSetProperty('imageId', $response['imageId']);
								$si->pqueue->processQueueSetProperty('processType','all');
								$si->pqueue->processQueueSave();

								if($code != '') {
									if($si->image->imageLoadById($response['imageId'])) {
										if($si->collection->collectionCodeExists($code)) {
											$si->image->imageSetProperty('collectionCode',$code);
										}
										$si->image->imageSave();
									}
								}
								
								# Add latitude and longitude - Start
								if($gps = $iEXd->getGPS()) {
									$si->imageCategory->lg->logSetProperty('action', 'imageAddFromForm');
									$si->imageCategory->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
									$si->image->lg->logSetProperty('action', 'imageAddFromForm');
									$si->image->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
									$si->imageAttribute->lg->logSetProperty('action', 'imageAddFromForm');
									$si->imageAttribute->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
									
									$catId = 0;
									$atrId = 0;
									$catArray = $si->imageCategory->imageCategoryList();
									if(is_array($catArray)) {
										foreach($catArray as $cat) {
											if($cat['title'] == 'Latitude') {
												$catId = $cat['categoryId'];
												break;
											}
										}
									}
									if(!$catId) {
										$si->imageCategory->imageCategorySetProperty('title', 'Latitude');
										$catId = $si->imageCategory->imageCategoryAdd();
									}
									$atrArray = $si->imageAttribute->imageAttributeList($catId);
									if(is_array($atrArray)) {
										foreach($atrArray as $atr) {
											if($atr['name'] == $gps['Latitude']) {
												$atrId = $atr['attributeId'];
												break;
											}
										}
									}
									if(!$atrId) {
										$si->imageAttribute->imageAttributeSetProperty('name',$gps['Latitude']);
										$si->imageAttribute->imageAttributeSetProperty('categoryId',$catId);
										$atrId = $si->imageAttribute->imageAttributeAdd();
									}
									$data['imageId'] = $response['imageId'];
									$data['attributeId'] = $atrId;
									$data['categoryId'] = $catId;
									$si->image->imageSetData($data);
									$si->image->imageAttributeAdd();
									
									$catId = 0;
									$atrId = 0;
									if(is_array($catArray)) {
										foreach($catArray as $cat) {
											if($cat['title'] == 'Longitude') {
												$catId = $cat['categoryId'];
												break;
											}
										}
									}
									if(!$catId) {
										$si->imageCategory->imageCategorySetProperty('title', 'Longitude');
										$catId = $si->imageCategory->imageCategoryAdd();
									}
									$atrArray = $si->imageAttribute->imageAttributeList($catId);
									if(is_array($atrArray)) {
										foreach($atrArray as $atr) {
											if($atr['name'] == $gps['Longitude']) {
												$atrId = $atr['attributeId'];
												break;
											}
										}
									}
									if(!$atrId) {
										$si->imageAttribute->imageAttributeSetProperty('name',$gps['Longitude']);
										$si->imageAttribute->imageAttributeSetProperty('categoryId',$catId);
										$atrId = $si->imageAttribute->imageAttributeAdd();
									}
									$data['imageId'] = $response['imageId'];
									$data['attributeId'] = $atrId;
									$data['categoryId'] = $catId;
									$si->image->imageSetData($data);
									$si->image->imageAttributeAdd();
								}
								# Add latitude and longitude - End
								
								$results[$i] =  array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'imageId' => $response['imageId'] );
								$totalCount++;
							} else {
								$results[$i] =  array( 'success' => false, 'error' => $si->getErrorArray(165));
							}
						} else {
							$results[$i] =  array( 'success' => false, 'error' => $si->getErrorArray(164));
						}
					}
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $totalCount, 'records' => $results ) ) );
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageAddFromServer':
				checkAuth();
				$flag = false;
				$loadFlag =  (@strtolower($loadFlag) == 'move') ?'move' : 'copy';
				# destinationPath and imagePath relative to the storage path
				$storageDeviceId = (trim($storageDeviceId)!='') ? $storageDeviceId : $si->storage->storageDeviceGetDefault();
				if($storageDeviceId == '' || $imagePath == '' || $filename == '') {
					$valid = false;
					$errorCode = 172;
				} else if(!$si->storage->storageDeviceExists($storageDeviceId)) {
					$valid = false;
					$errorCode = 156;
				}
				$device = $si->storage->storageDeviceGet($storageDeviceId);
				$basePath = rtrim($device['basePath'],'/') . '/';
				$imagePath = '/' . ltrim($imagePath,'/');
				$imagePath = rtrim($imagePath,'/') . '/';

				# Relative to the store root NOT the system.
				if(!@file_exists($basePath . $imagePath . $filename)) {
					$valid = false;
					$errorCode = 168;
				}

				if($valid) {
					$bcode = @explode('.',$filename);
					array_pop($bcode);
					$bcode = @implode('.',$bcode);
					
					// $destinationPath = ($destinationPath == '') ? '/serverimages/' . $bcode . '/' : $destinationPath; 
					$destinationPath = ($destinationPath == '') ? '/' . $bcode . '/' : $destinationPath; 
					$imgPath = $destinationPath;
					$imgPath = rtrim($imgPath,'/') . '/';
				
				
				
					$imageId = $si->image->imageGetId($filename, $imgPath, $storageDeviceId);
					if(!$imageId) {
						$si->image->imageMkdirRecursive($basePath . $imgPath);
						switch($loadFlag) {
							case 'move':
								if(rename($basePath . $imagePath . $filename, $basePath . $imgPath . $filename)) $flag = true;
								break;
							case 'copy':
							default:
								if(copy($basePath . $imagePath . $filename, $basePath . $imgPath . $filename)) $flag = true;
								break;
						}
						if($flag) {
							$ar = @getimagesize($basePath . $imgPath . $filename);
							$imgPath = '/' . ltrim($imgPath,'/');
							$imgPath = rtrim($imgPath,'/') . '/';
							$si->image->imageSetProperty('width',$ar[0]);
							$si->image->imageSetProperty('height',$ar[1]);
							$si->image->imageSetProperty('filename',$filename);
							$si->image->imageSetProperty('storageDeviceId', $storageDeviceId);
							$si->image->imageSetProperty('path', $imgPath);
							$si->image->imageSetProperty('originalFilename', $filename);
							$si->image->imageSave();
							$imageId = $si->image->imageGetId($filename, $imgPath, $storageDeviceId);
							$si->pqueue->processQueueSetProperty('imageId', $imageId);
							$si->pqueue->processQueueSetProperty('processType','all');
							$si->pqueue->processQueueSave();
							$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'imageId' => $imageId ) ) );
						} else {
							$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(173)) ));
						}
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(166)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageAddFromUrl':
				checkAuth();
				$storageDeviceId = (trim($storageDeviceId)!='') ? $storageDeviceId : $si->storage->storageDeviceGetDefault();
				if($storageDeviceId=='' || !$si->storage->storageDeviceExists($storageDeviceId)) {
					$results[$i] = array( 'success' => false,  'error' => array( 'success' => false, 'error' => $si->getErrorArray(156)) );
					continue;
				}
				if($url == '') {
					$valid = false;
					$errorCode = 107;
				} elseif(!($section = file_get_contents($url, NULL, NULL, 0, 8))) {
					$valid = false;
					$errorCode = 107;
				} else {
					for($i=0;$i<strlen($section);$i++) {
						$hexString .= dechex(ord($section[$i]));
					}
					if($hexString == '89504e47da1aa') {
						$fileType = 'png';
					} elseif(substr($hexString, 0, 12) == '474946383961' || substr($hexString, 0, 12) == '474946383761') {
						$fileType = 'gif';
					} elseif(substr($hexString, 0, 4) == 'ffd8') {
						$fileType = 'jpg';
					} else {
						$valid = false;
						$errorCode = 164;
					}
				}
				if($valid) {
					$temp = explode('/', $url);
					$filename = $temp[count($temp)-1];
					
					$bcode = @explode('.',$filename);
					array_pop($bcode);
					$bcode = @implode('.',$bcode);
					$imagePath = (isset($imagePath))?$imagePath:$bcode . '/';

					
					$data = file_get_contents($url);
					$tmp = sys_get_temp_dir() . '/' . $filename;
					file_put_contents($tmp, $data);
					unset($data);
					$response = $si->storage->storageDeviceStore($tmp,$storageDeviceId,$filename, $imagePath, $key);
					$iEXd = new EXIFread($filename);
					unlink($tmp);  // Remove tmp image after it has been stored in the right location.
					if($response['success']) {
						$si->pqueue->processQueueSetProperty('imageId', $response['imageId']);
						$si->pqueue->processQueueSetProperty('processType','all');
						$si->pqueue->processQueueSave();
						
						# Add latitude and longitude - Start
						if($gps = $iEXd->getGPS()) {
							$si->imageCategory->lg->logSetProperty('action', 'imageAddFromUrl');
							$si->imageCategory->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
							$si->image->lg->logSetProperty('action', 'imageAddFromUrl');
							$si->image->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
							$si->imageAttribute->lg->logSetProperty('action', 'imageAddFromUrl');
							$si->imageAttribute->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
							
							$catId = 0;
							$atrId = 0;
							$catArray = $si->imageCategory->imageCategoryList();
							if(is_array($catArray)) {
								foreach($catArray as $cat) {
									if($cat['title'] == 'Latitude') {
										$catId = $cat['categoryId'];
										break;
									}
								}
							}
							if(!$catId) {
								$si->imageCategory->imageCategorySetProperty('title', 'Latitude');
								$catId = $si->imageCategory->imageCategoryAdd();
							}
							$atrArray = $si->imageAttribute->imageAttributeList($catId);
							if(is_array($atrArray)) {
								foreach($atrArray as $atr) {
									if($atr['name'] == $gps['Latitude']) {
										$atrId = $atr['attributeId'];
										break;
									}
								}
							}
							if(!$atrId) {
								$si->imageAttribute->imageAttributeSetProperty('name',$gps['Latitude']);
								$si->imageAttribute->imageAttributeSetProperty('categoryId',$catId);
								$atrId = $si->imageAttribute->imageAttributeAdd();
							}
							$data['imageId'] = $response['imageId'];
							$data['attributeId'] = $atrId;
							$data['categoryId'] = $catId;
							$si->image->imageSetData($data);
							$si->image->imageAttributeAdd();
							
							$catId = 0;
							$atrId = 0;
							if(is_array($catArray)) {
								foreach($catArray as $cat) {
									if($cat['title'] == 'Longitude') {
										$catId = $cat['categoryId'];
										break;
									}
								}
							}
							if(!$catId) {
								$si->imageCategory->imageCategorySetProperty('title', 'Longitude');
								$catId = $si->imageCategory->imageCategoryAdd();
							}
							$atrArray = $si->imageAttribute->imageAttributeList($catId);
							if(is_array($atrArray)) {
								foreach($atrArray as $atr) {
									if($atr['name'] == $gps['Longitude']) {
										$atrId = $atr['attributeId'];
										break;
									}
								}
							}
							if(!$atrId) {
								$si->imageAttribute->imageAttributeSetProperty('name',$gps['Longitude']);
								$si->imageAttribute->imageAttributeSetProperty('categoryId',$catId);
								$atrId = $si->imageAttribute->imageAttributeAdd();
							}
							$data['imageId'] = $response['imageId'];
							$data['attributeId'] = $atrId;
							$data['categoryId'] = $catId;
							$si->image->imageSetData($data);
							$si->image->imageAttributeAdd();
						}
						# Add latitude and longitude - End
						
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'imageId' => $response['imageId'] ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(165)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageAddRating':
				$count = 0;
				$ret = $si->imageRating->imageRatingGetAvg();
				if(is_object($ret) && !is_null($ret)) {
					while ($row = $ret->fetch_object()) {
						if($si->image->imageAddRating($row->imageId,$row->rating)) {
							$si->imageRating->imageRatingUpdateCalc($row->imageId);
							$count++;
						}
					}
				}
				$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $count ) ) );
				break;
				
			case 'imageAddToCollection':
				checkAuth();
				if($advFilterId != '') {
					if($si->advFilter->advFilterLoadById($advFilterId)) {
						$advFilter  = $si->advFilter->advFilterGetProperty('filter');
					}
				}
				$data['advFilter'] = json_decode(trim($advFilter),true);
				if($code =='') {
					$valid = false;
					$errorCode = 179;
				} elseif(!$si->collection->collectionCodeExists($code)) {
					$valid = false;
					$errorCode = 175;
				} elseif($imageId == '' && !(is_array($data['advFilter']) && count($data['advFilter']))) {
					$valid = false;
					$errorCode = 220;
				} else if($imageId != '' && !(is_array($data['advFilter']) && count($data['advFilter']))) {
					if(is_numeric($imageId)) {
						if(!$si->image->imageLoadById($imageId)) {
							$valid = false;
							$errorCode = 158;
						} else {
							$imageId = array($imageId);
						}
					} else {
						$imageId = json_decode($imageId,true);
					}
				}
				if($valid) {
					$data['imageId'] = $imageId;
					$si->collection->collectionSetData($data);
					if($si->collection->collectionAddImage($code)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(176)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageAddToEvent':
				checkAuth();
				if($advFilterId != '') {
					if($si->advFilter->advFilterLoadById($advFilterId)) {
						$advFilter  = $si->advFilter->advFilterGetProperty('filter');
					}
				}
				$data['advFilter'] = json_decode(trim($advFilter),true);
				if($eventId == '') {
					$valid = false;
					$errorCode = 115;
				} else if(!$si->event->eventsRecordExists($eventId)) {
					$valid = false;
					$errorCode = 117;
				}
				if($imageId != '') {
					if(!is_numeric($imageId)) {
						$imageId = json_decode($imageId,true);
					} else {
						$imageId = array($imageId);
					}
				}
				
				if($valid) {
					$data['imageId'] = $imageId;
					$si->event->eventsSetData($data);
					$si->event->lg->logSetProperty('action', 'imageAddToEvent');
					$si->event->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
					if(false !== ($id = $si->event->eventsAddImage($eventId))) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(177)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imagesLoadFromDir':
				checkAuth();
				if($storageDeviceId == '' || $imagePath == '') {
					$valid = false;
					$errorCode = 172;
				} else if(!$si->storage->storageDeviceExists($storageDeviceId)) {
					$valid = false;
					$errorCode = 156;
				} else {
					$valid = true;
				}
				if($valid) {
					$count = 0;
					$loadFlag =  (@strtolower($loadFlag) == 'move') ?'move' : 'copy';
					if($code != '') {
						if(!$si->collection->collectionCodeExists($code)) {
							$code = '';
						}
					}
					
					$device = $si->storage->storageDeviceGet($storageDeviceId);
					$basePath = rtrim($device['basePath'],'/') . '/';
					
					if($destinationPath != ''){
						$destinationPath = ltrim($destinationPath,'/');
						$destinationPath = rtrim($destinationPath,'/') . '/';
					}
					
					$imagePath = ltrim($imagePath,'/');
					$imagePath = rtrim($imagePath,'/') . '/';
					$skipFiles = array(".", "..");
					$allowedExt = array('jpg','JPG');
					foreach(scandir($basePath . $imagePath) as $file) {
						if (in_array($file, $skipFiles)) {continue;}
						$parts = pathinfo( $file );
						$bcode = $parts['filename'];
						$ext = $parts['extension'];
						if(!in_array($ext,$allowedExt)) continue;

						$flag = false;
						if($destinationPath != '') {
							$imageId = $si->image->imageGetId($file, $destinationPath . $bcode . '/', $storageDeviceId);
							if(!$imageId) {
								$si->image->imageMkdirRecursive($basePath . $destinationPath . $bcode . '/');
								switch($loadFlag) {
									case 'move':
										if(rename($basePath . $imagePath . $file, $basePath . $destinationPath . $bcode . '/' . $file)) {
											$flag = true;
											$ar = @getimagesize($basePath . $destinationPath . $bcode . '/' . $file);
											$si->image->imageSetProperty('path', $destinationPath . $bcode . '/');
										}
										break;
									case 'copy':
									default:
										if(copy($basePath . $imagePath . $file, $basePath . $destinationPath . $bcode . '/' . $file)) {
											$flag = true;
											$ar = @getimagesize($basePath . $destinationPath . $bcode . '/' . $file);
											$si->image->imageSetProperty('path', $destinationPath . $bcode . '/');
										}
										break;
								}
							}
						} else {
							$imageId = $si->image->imageGetId($file, $imagePath, $storageDeviceId);
							if(!$imageId) {
								$flag = true;
								$ar = @getimagesize($basePath . $imagePath . $file);
								$si->image->imageSetProperty('path', $imagePath);
							}
						}
						
						
						if($flag) {
							$si->image->imageSetProperty('width',$ar[0]);
							$si->image->imageSetProperty('height',$ar[1]);
							$si->image->imageSetProperty('filename',$file);
							$si->image->imageSetProperty('storageDeviceId', $storageDeviceId);
							$si->image->imageSetProperty('originalFilename', $file);
							$si->image->imageSetProperty('collectionCode',$code);						
							
							if($si->image->imageSave()) {
								$count++;
								$imageId = $si->image->insert_id;
								$si->pqueue->processQueueSetProperty('imageId', $imageId);
								$si->pqueue->processQueueSetProperty('processType','all');
								$si->pqueue->processQueueSave();
							}					
						}
					}
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $count ) ) );
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageCalculateRating':
				if($imageId == '') {
					$valid = false;
					$errorCode = 157;
				} else if(!$si->image->imageLoadById($imageId)) {
					$valid = false;
					$errorCode = 158;
				} else if( !(is_numeric($rating) && $rating >= 0 && $rating <= 5)) {
					$valid = false;
					$errorCode = 191;
				}

				if($valid) {
					$userId = ($userAccess->is_logged_in()) ? $_SESSION['user_id'] : 0;
					$si->imageRating->imageRatingSetProperty('imageId',$imageId);
					$si->imageRating->imageRatingSetProperty('userId',$userId);
					$si->imageRating->imageRatingSetProperty('ipAddress',str_replace('.', '', $_SERVER['REMOTE_ADDR']));
					$si->imageRating->imageRatingSetProperty('rating',$rating);
					$si->imageRating->imageRatingSave();
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );

				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'imageDelete':
				checkAuth();
				if(trim($imageId) == '') {
					$valid = false;
					$errorCode = 157;
				}
				if($valid) {
					$data['obj'] = $si->amazon;
					$imageId = (!is_numeric($imageId)) ? json_decode(trim($imageId),true) : $imageId;
					$items = array();
					if(is_array($imageId)) {
						foreach($imageId as $imid) {
							$data['imageId'] = $imid;
							$si->image->imageSetData($data);
							if($si->image->imageLoadById($data['imageId'])) {
								if(($si->image->imageGetProperty('remoteAccessKey') == $key) || ($key==0)) {
									$ret = $si->image->imageDelete();
									if($ret['success']) $items[] = $imid;
								} else {
									$ret['success'] = false;
									$ret['code'] = 171;
								}
							} else {
								$ret['success'] = false;
								$ret['code'] = 170;
							}
						}
					} else {
						$data['imageId'] = $imageId;
						$si->image->imageSetData($data);
						if($si->image->imageLoadById($data['imageId'])) {
							if(($si->image->imageGetProperty('remoteAccessKey') == $key) || ($key==0)) {
								$ret = $si->image->imageDelete();
								if($ret['success']) $items[] = $imageId;
							} else {
								$ret['success'] = false;
								$ret['code'] = 171;
							}
						} else {
							$ret['success'] = false;
							$ret['code'] = 170;
						}
					}
					
					if(count($items)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($items), 'records' => $items ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($ret['code'])) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;


			case 'imageDeleteAttribute':
				checkAuth();
				if($imageId != '') {
					if(!is_numeric($imageId)) {
						$imageId = json_decode($imageId,true);
					} else {
						if(!$si->image->imageLoadById($imageId)) {
							$valid = false;
							$errorCode = 158;
						} else {
							$imageId = array($imageId);
						}
					}
				} else if($advFilterId != '') {
					if($si->advFilter->advFilterLoadById($advFilterId)) {
						$advFilter  = $si->advFilter->advFilterGetProperty('filter');
					}
					$data['advFilter'] = json_decode(trim($advFilter),true);
				} else if (trim($advFilter) != "") {
					$data['advFilter'] = json_decode(trim($advFilter),true);
				} else {
					$valid = false;
					$errorCode = 157;
				}
				$attribType = in_array(trim($attribType),array('attributeId','name')) ? trim($attribType) : 'attributeId';
				$data['imageId'] = $imageId;
				$data['attributeId'] = $attributeId;
				if($data['attributeId'] == "") {
					if($attribute != "") {
						if(false === ($data['attributeId'] = $si->imageAttribute->imageAttributeGetBy($attribute, $attribType, $data['categoryId']))) {
							$valid = false;
							$errorCode = 149;
						}
					} else {
						$valid = false;
						$errorCode = 109;
					}
				}
				if($valid) {
					$si->image->lg->logSetProperty('action', 'imageDeleteAttribute');
					$si->image->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);

					$si->image->imageSetData($data);
					if($si->image->imageAttributeDelete()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(162)) ));
					}
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

				
			case 'imageDeleteAttribute1':
				checkAuth();
				
				if($imageId == "") {
					$valid = false;
					$errorCode = 157;
				} else if(is_numeric($imageId)) {
					if(!$si->image->imageLoadById($imageId)) {
						$valid = false;
						$errorCode = 158;
					} else {
						$imageId = array($imageId);
					}
				} else {
					$imageId = json_decode($imageId,true);
				}
				$data['imageId'] = $imageId;
				$data['attributeId'] = $attributeId;
				if($data['attributeId'] == "") {
					$valid = false;
					$errorCode = 109;
				}
				if($valid) {
					$si->image->lg->logSetProperty('action', 'imageDeleteAttribute');
					$si->image->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);

					$si->image->imageSetData($data);
					if($si->image->imageAttributeDelete()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(162)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
								
			case 'imageDeleteFromEvent':
				checkAuth();
				// if($eventId == '') {
					// $valid = false;
					// $errorCode = 115;
				// } else if($imageId == '') {
					// $valid = false;
					// $errorCode = 157;
				// } else if(!$si->event->eventsRecordExists($eventId)) {
					// $valid = false;
					// $errorCode = 117;
				// } else if(!$si->image->imageFieldExists($imageId)) {
					// $valid = false;
					// $errorCode = 170;
				// }
				
				if($eventId == '') {
					$valid = false;
					$errorCode = 115;
				} else if(!$si->event->eventsRecordExists($eventId)) {
					$valid = false;
					$errorCode = 117;
				}
				
				if($advFilterId != '') {
					if($si->advFilter->advFilterLoadById($advFilterId)) {
						$advFilter  = $si->advFilter->advFilterGetProperty('filter');
					}
				}
				$data['advFilter'] = json_decode(trim($advFilter),true);
				if($imageId == '' && !(is_array($data['advFilter']) && count($data['advFilter']))) {
					$valid = false;
					$errorCode = 220;
				} else if($imageId != '' && !(is_array($data['advFilter']) && count($data['advFilter']))) {
					if(is_numeric($imageId)) {
						if(!$si->image->imageLoadById($imageId)) {
							$valid = false;
							$errorCode = 158;
						} else {
							$imageId = array($imageId);
						}
					} else {
						$imageId = json_decode($imageId,true);
					}
				}

				if($valid) {
					$data['imageId'] = $imageId;
					$data['eventId'] = $eventId;
				
					$si->event->lg->logSetProperty('action', 'imageDeleteFromEvent');
					$si->event->lg->logSetProperty('lastModifiedBy', $_SESSION['user_id']);
					
					$si->event->eventsSetData($data);
					if($si->event->eventsDeleteImage()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(178)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageDetails':
				$data['imageId'] = $imageId;
				if ($data['imageId'] == '') {
					$errorCode = 157;
					$valid = false;
				}
				if($valid) {
					$si->image->imageSetData($data);
					$ar = $si->image->imageDetails();

					if($ar['status']) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'results' => $ar['record'] ) ) );
					} else {
						$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray($ar['error']) ) ) );
					}

				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

	/*
			case 'imageDetectBarcode':
				if(!$config['zBarImgEnabled']) {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(180)) ));
					exit;
				}
				$_TMP = ($config['path']['tmp'] != '') ? $config['path']['tmp'] : sys_get_temp_dir() . '/';

				$results = array();
				
				if($advFilterId != '') {
					if($si->advFilter->advFilterLoadById($advFilterId)) {
						$advFilter  = $si->advFilter->advFilterGetProperty('filter');
					}
				}
				$advFilter = json_decode(trim($advFilter),true);
				$idArray = array();
				if(is_numeric($imageId)) {
					$imageIds = array($imageId);
				} else {
					$imageIds = json_decode(trim($imageId), true);
				}
				if(is_array($imageIds) && count($imageIds)) {
					$idArray = @array_fill_keys($imageIds,'id');
				}
				$barcodes = json_decode(trim($barcode), true);
				$barcodes = (is_null($barcodes) && $barcode != '') ? array($barcode) : $barcodes;
				if(is_array($barcodes) && count($barcodes)) {
					$idArray = $idArray + @array_fill_keys($barcodes,'code');
				}

				function processDetectBarcode($img,$force = false) {
					global $config,$si;
					$timeStart = microtime(true) ;
					$key = rtrim($img->imageGetProperty('path'),'/') . '/' . $img->imageGetProperty('filename');
					$cacheFlag = false;
					$explodeFilename = explode(".", $img->imageGetProperty('filename'));
					@array_pop($explodeFilename);
					$expFilename = implode('.',$explodeFilename);
					$cachePath = rtrim($img->imageGetProperty('path'),'/') . '/' . $expFilename . "-barcodes.json";

					if(strtolower($force) != 'true') {
						$cacheFlag = $si->storage->storageDeviceFileExists($img->imageGetProperty('storageDeviceId'), $cachePath);
					}

					if($cacheFlag) {
						$data = $si->storage->storageDeviceFileGetContents($img->imageGetProperty('storageDeviceId'), $cachePath);
						$data = json_decode($data, true);
						return array('imageId' => $img->imageGetProperty('imageId'), 'data' => $data);
					} else {
						# No cache or not using cache
						$image = $si->storage->storageDeviceFileDownload($img->imageGetProperty('storageDeviceId'), $key);
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
									if(false === ($attributeData['attributeId'] = $si->imageAttribute->imageAttributeGetBy($tmpArray,'name',$attributeData['categoryId']))) {
										$si->imageAttribute->imageAttributeSetProperty('name',$tmpArray);
										$si->imageAttribute->imageAttributeSetProperty('categoryId',$attributeData['categoryId']);
										$attributeData['attributeId'] = $si->imageAttribute->imageAttributeAdd();
									}
									$attributeData['imageId'] = array($img->imageGetProperty('imageId'));
									$si->image->imageSetData($attributeData);
									$si->image->imageAttributeAdd();
								}
							}
						}
						if(strtolower($si->storage->storageDeviceGetType($img->imageGetProperty('storageDeviceId'))) == 's3') {
							@unlink($image);
						}
						$command = sprintf("%s --version ", $config['zBarImgPath']);
						$ver = exec($command);
						$dt = array('success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($data), 'lastTested' => time(), 'software' => 'zbarimg', 'version' => $ver, 'results' => $data);
						$tmpJsonFile = json_encode($dt);
						$key = rtrim($img->imageGetProperty('path'),'/') . '/' . $expFilename . '-barcodes.json';

						$si->storage->storageDeviceCreateFile($img->imageGetProperty('storageDeviceId'), $key, $tmpJsonFile);
						return array('imageId' => $img->imageGetProperty('imageId'), 'data' => $dt);
					}
				}

				if(is_array($advFilter) && count($advFilter)) {
					$query = $si->image->getByCrazyFilter($advFilter);
					$ret = $si->db->query($query);
					if (is_object($ret)) {
						while ($img = $ret->fetch_object()) {
							if($si->image->imageLoadById($img->imageId)) {
								$results[] = processDetectBarcode($si->image,$force);
							}
						}
					}
				} else if(is_array($idArray) && count($idArray)) {
					foreach($idArray as $id => $code) {
						$func = ($code == 'id') ? 'imageLoadById' : 'imageLoadByBarcode';
						if(!$si->image->{$func}($id)) continue;
						$results[] = processDetectBarcode($si->image,$force);
					}
				}
				$response = (json_encode(array('success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($results), 'results' => $results)));

				break;
	*/


			case 'imageDetectBarcode':
				if(!$config['zBarImgEnabled']) {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(180)) ));
					exit;
				}
				$results = array();
				
				if($advFilterId != '') {
					if($si->advFilter->advFilterLoadById($advFilterId)) {
						$advFilter  = $si->advFilter->advFilterGetProperty('filter');
					}
				}
				$advFilter = json_decode(trim($advFilter),true);
				$idArray = array();
				if(is_numeric($imageId)) {
					$imageIds = array($imageId);
				} else {
					$imageIds = json_decode(trim($imageId), true);
				}
				if(is_array($imageIds) && count($imageIds)) {
					$idArray = @array_fill_keys($imageIds,'id');
				}
				$barcodes = json_decode(trim($barcode), true);
				$barcodes = (is_null($barcodes) && $barcode != '') ? array($barcode) : $barcodes;
				if(is_array($barcodes) && count($barcodes)) {
					$idArray = $idArray + @array_fill_keys($barcodes,'code');
				}

				if(is_array($advFilter) && count($advFilter)) {
					$query = $si->image->getByCrazyFilter($advFilter);
					$ret = $si->db->query($query);
					if (is_object($ret)) {
						while ($img = $ret->fetch_object()) {
							if($si->image->imageLoadById($img->imageId)) {
								$results[] = array('imageId' => $img->imageId, 'data' => json_decode($si->image->imageGetProperty('rawBarcode'),true));
							}
						}
					}
				} else if(is_array($idArray) && count($idArray)) {
					foreach($idArray as $id => $code) {
						$func = ($code == 'id') ? 'imageLoadById' : 'imageLoadByBarcode';
						if(!$si->image->{$func}($id)) continue;
						$results[] = array('imageId' => $si->image->imageGetProperty('imageId'), 'data' => json_decode($si->image->imageGetProperty('rawBarcode'),true));
					}
				}
				$response = (json_encode(array('success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($results), 'results' => $results)));

				break;

			case 'imageDetectColorBox':
				$_TMP = ($config['path']['tmp'] != '') ? $config['path']['tmp'] : sys_get_temp_dir() . '/';
				$loadFlag = $existsFlag = false;
				if(trim($imageId) != '') {
					$loadFlag = $si->image->imageLoadById($imageId);
				} elseif(trim($barcode) != '') {
					$loadFlag = $si->image->imageLoadByBarcode($barcode);
				}
				if(!$loadFlag) {
					$valid = false;
					$errorCode = 181;
				}
				if($valid) {
					$force = (strtolower($force) == 'true') ? true : false;
					$filename = @explode('.', $si->image->imageGetProperty('filename'));
					@array_pop($filename);
					$filename = @explode('.', $filename);
					
					$key = @rtrim($si->image->imageGetProperty('path'),'/') . '/' . $filename . '_box.json';
					if($si->storage->storageDeviceFileExists($si->image->imageGetProperty('storageDeviceId'), $key)) {
						$data = $si->storage->storageDeviceFileGetContents($si->image->imageGetProperty('storageDeviceId'), $key);
						if($data) {
							$existsFlag = true;
						}
					}
					if(!$existsFlag || $force) {
						$image = $si->image->imageGetProperty('path') . '/' . $si->image->imageGetProperty('filename');

						# Getting image
						$image = $si->storage->storageDeviceFileDownload($si->image->imageGetProperty('storageDeviceId'), $image);

						# processing
						putenv("LD_LIBRARY_PATH=/usr/local/lib");
						$data = exec(sprintf("%s %s", $config['boxDetectPath'], $image));

						# saving the json object
						$si->storage->storageDeviceCreateFile($si->image->imageGetProperty('storageDeviceId'), $key, $data);
					}
					$si->pqueue->processQueueDelete($si->image->imageGetProperty('barcode'), 'box_add');
					$si->image->imageSetProperty('boxFlag', 1);
					$si->image->imageSave();

					$data = json_decode($data, true);
					$variable = ($data['data']['height'] > $data['data']['width']) ? $data['data']['height'] : $data['data']['width'];
					$data['data']['pixelsPerCentimeter'] = @round($variable/4);
					$data['data']['pixelsPerInch'] = @round($variable/1.57);
					$data['processedTime'] = microtime(true) - $timeStart;
					$response = (json_encode($data));
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageGetOcr':
				$objFlag = false;
				$formatHTML = (in_array(trim($formatHTML), array('TRUE','true','1'))) ? true : false;
				if(trim($imageId) != '') {
					$objFlag = $si->image->imageLoadById($imageId);
				} else if(trim($barcode) != '') {
					$objFlag = $si->image->imageLoadByBarcode($barcode);
				}
				$ocrData = ($objFlag) ? $si->image->imageGetProperty('ocrValue') : '';
				$ocrData = ($ocrData != '' && $formatHTML) ? nl2br($ocrData) : $ocrData;
				header('content-type: text/plain');
				print $ocrData;
				break;
				
			case 'imageGetUrl':
				$loadFlag = false;
				if(trim($imageId) != '') {
					$loadFlag = $si->image->imageLoadById($imageId);
				} elseif(trim($barcode) != '') {
					$loadFlag = $si->image->imageLoadByBarcode($barcode);
				}
				if(!$loadFlag) {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(181)) ));
				} else {
					if(isset($size) && in_array($size, array('s','m','l'))) {
						$size = "_".$size;
					} else {
						$size = "";
					}
					$imageId = $si->image->imageGetProperty('imageId');
					$tmpFilename = explode(".",$si->image->imageGetProperty('filename'));
					$ext = @array_pop($tmpFilename);
					$filename = implode('.',$tmpFilename) . $size . '.' . $ext;
					// $filename = str_replace(' ','%20',$filename);
					// echo $filename;exit;
					if($si->image->imageExists($si->image->imageGetProperty('storageDeviceId'), $si->image->imageGetProperty('path'), $filename)) {
						$url = $si->image->imageGetUrl($imageId);
						header('Content-type: text/plain');
						print($url['baseUrl'] . $filename);
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(168)) ));
					}
				}
				break;

			case 'imageList':
				$data['start'] = ($start != '') ? $start : 0;
				$data['limit'] = ($limit != '') ? $limit : 100;
				$data['showOCR'] = (@in_array(trim($showOCR),array('1','true','TRUE'))) ? true : false;
				$data['showBarcode'] = (@in_array(trim($showBarcode),array('1','true','TRUE'))) ? true : false;
				$data['order'] = json_decode(trim($order),true);
				$data['gridSort'] = json_decode(trim($gridSort),true);
				if(trim($sort) != '') {
					$data['sort'] = trim($sort);
					$dir = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';
					$data['dir'] = trim($dir);
				}
				
				if($advFilterId != '') {
					if($si->advFilter->advFilterLoadById($advFilterId)) {
						$advFilter  = $si->advFilter->advFilterGetProperty('filter');
					}
				}
				$data['advFilter'] = json_decode(trim($advFilter),true);
				
				if(is_array($filter)) {
					$data['filter'] = $filter;
				} else {
					$data['filter'] = json_decode(trim($filter),true);
				}
				$data['imageId'] = (!is_numeric($imageId)) ? json_decode(trim($imageId),true) : $imageId;
				$data['barcode'] = is_null(json_decode($barcode,true)) ? $barcode : json_decode($barcode,true);
				$data['filename'] = is_null(json_decode($filename,true)) ? $filename : json_decode($filename,true);
				$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
				$data['value'] = str_replace('%','%%',trim($value));
				$data['group'] = $group;
				$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';
				$data['code'] = ($code != '') ? $code : '';

				$data['characters'] = $characters;
				$data['characterType'] = in_array(strtolower(trim($characterType)), array('ids','string')) ? strtolower(trim($characterType)) : 'string';
				$data['browse'] = $browse;
				$data['searchValue'] = $searchValue;
				$data['searchType'] = $searchType;

				$data['useRating'] = (trim($useRating) == 'true') ? true : false;
				$data['useStatus'] = (trim($useStatus) == 'true') ? true : false;

				if($valid) {
					$associations = json_decode(trim($associations),true);
					$si->image->imageSetData($data);
					$data = $si->image->imageList();
					$total = $si->image->total;
					if(is_array($data) && count($data)) {
						foreach($data as &$dt) {
							if((@in_array(trim($showBarcode),array('1','true','TRUE')))) {
								$dt->rawBarcode = json_decode($dt->rawBarcode,true);
							}
							$device = $si->storage->storageDeviceGet($dt->storageDeviceId);
							$url = $device['baseUrl'];
							switch(strtolower($device['type'])) {
								case 's3':
									$tmp = $dt->path;
									// $tmp = substr($tmp, 0, 1)=='/' ? substr($tmp, 1, strlen($tmp)-1) : $tmp;
									$tmp = ltrim($tmp,'/');
									$url .= $tmp . '/';
									break;
								case 'local':
									$url = rtrim($url,'/') . '/';
									if($device['method'] == 'reference') {
										$si->image->imageSetFullPath($dt->filename);
										$bcode = $si->image->imageGetName();
										$url .= $bcode . '/';
									} else {
										$url .= ($dt->path == '/' || $dt->path == '') ? '' : trim($dt->path,'/') . '/';
									}
									break;
							}
							unset($dt->storageDeviceId);
							unset($dt->path);
							$dt->path = $url;
							$fname = explode(".", $dt->filename);
							$dt->ext = @array_pop($fname);
							$dt->enFlag = ($si->s2l->Specimen2LabelLoadByBarcode($dt->barcode)) ? 1 : 0;
							
							if(!(is_array($data['advFilter']) && count($data['advFilter']))) {
								if(is_array($associations) && count($associations)) {
									foreach($associations as $association) {
										switch($association) {
											case 'events':
												$dt->events = $si->event->eventsByImage($dt->imageId);
												break;
											case 'geography':
												$dt->geography = $si->geography->geographyByImage($dt->imageId);
												break;
											case 'attributes':
												$dt->attributes = $si->image->imageGetAttributeDetails($dt->imageId);
												break;
										}
									}
								}
							}
						}
					}

					if($api=='rss'){
						include("feedwriter.php");

						$RSSFeed = new FeedWriter(RSS2);
						$RSSFeed->setTitle($config['rssFeed']['title']);
						$RSSFeed->setLink($config['rssFeed']['webUrl']);
							
						foreach($data as $key => $value){
							
							$key1 = get_object_vars($value);						
							$imgMed = $key1['path'] . $key1['barcode'] . '_m.jpg';
							$imgLarg = $key1['path'] . $key1['barcode'] . '_l.jpg';
						
							$title = $key1['barcode'];  
							$newItem = $RSSFeed->createNewItem();
							 
							# Add elements to the feed item    
							$newItem->setTitle($title);
							$newItem->setLink($img1);
							$newItem->setDescription("<a href='" . $imgLarg . "'><img style='border:1px solid #5C7FB9'src='" . $imgMed . "'/></a>");
							$newItem->setEncloser($imgLarg, '7', 'image/jpeg');
							# set the feed item
							$RSSFeed->addItem($newItem);
						}

						$RSSFeed->genarateFeed();
					} else {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $total, 'records' => $data ) ));
					}
				} else {				
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

				
			case 'imageListAttribute':
				if($imageId == '') {
					$valid = false;
					$errorCode = 157;
				} else if(!$si->image->imageLoadById($imageId)) {
					$valid = false;
					$errorCode = 158;
				}
				if($valid) {
					$attbr = $si->image->imageGetAttributes($imageId);
					if($attbr) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart , 'results' => $attbr) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(182)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
					
			case 'imageListByEvent':
				if($eventId == '') {
					$valid = false;
					$errorCode = 115;
				} else if(!$si->event->eventsRecordExists($eventId)) {
					$valid = false;
					$errorCode = 117;
				}
				$attributesFlag = (trim(@strtolower($attributesFlag)) == 'false') ? false : true;
				$size = (in_array($size, array('s','m','l'))) ? $size : 'l';
				if($valid) {
					$imageIds = $si->event->eventsListImages($eventId,$size,$attributesFlag);
					if($imageIds) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'results' => $imageIds ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(183)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'imageListBySet':
				if($setId != '') {
					if(!$si->set->setLoadById($setId)) {
						$valid = false;
						$errorCode = 198;
					}
				} else {
					$setId = '';
				}
				if($valid) {
					$data = $si->set->setListImages($setId);
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($data['data']), 'results' => $data['data'] ) ) );
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'imageListBySetKeyValue':
				if($category=='' || $attribute=='')	{
					$valid = false;
					$errorCode = 207;
				}
				if($valid) {
					$data = $si->set->setListImageByKeyValue($category, $attribute);
					if($data) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($data), 'results' => $data ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(208)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageListCharacters':
				checkAuth();
				$data['start'] = ($start == '') ? 0 : $start;
				$data['limit'] = ($limit == '') ? 100 : $limit;
				$data['browse'] = $browse;
				$data['characters'] = $characters;
				$data['searchValue'] = $searchValue;
				$data['searchType'] = $searchType;
				$si->image->imageSetData($data);
				if(false !== ($nodes = $si->image->imageLoadCharacterList())) {
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($nodes), 'results' => $nodes)));
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(185)) ));
				}
				break;

			case 'imageListNodes':
				$nodeApi = ($nodeApi != '') ? @strtolower($nodeApi) : 'root';
				if(!in_array($nodeApi, array('root', 'alpha', 'families', 'family', 'genera', 'genus', 'scientificname') )) {
					$errorCode = 186;
					$valid = false;
				}
				$data['nodeApi'] = $nodeApi;
				$data['nodeValue'] = $nodeValue;
				$data['family'] = $family;
				$data['genus'] = $genus;

				if($valid) {
					$si->image->imageSetData($data);
					if(false !== ($nodes = $si->image->imageLoadNodeImages())) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($nodes), 'results' => $nodes)));
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(185)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageListNodesCharacters':
				$nodeApi = ($nodeApi != '') ? @strtolower($nodeApi) : 'root';
				if(!in_array($nodeApi, array('root'))) {
					$errorCode = 186;
					$valid = false;
				}
				$data['nodeValue'] = $nodeValue;
				$data['family'] = $family;
				$data['genus'] = $genus;
				if($valid) {
					$si->image->imageSetData($data);
					if(false !== ($nodes = $si->image->imageLoadNodeCharacters())) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($nodes), 'results' => $nodes)));
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(185)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageLoadFromIncoming':
				$storageDeviceId = (trim($storageDeviceId)!='') ? $storageDeviceId : $si->storage->storageDeviceGetDefault();
				if($storageDeviceId == '' || !$si->storage->storageDeviceExists($storageDeviceId)) {
					$errorCode = 156;
					$valid = false;
				} else {
					$device = $si->storage->storageDeviceGet($storageDeviceId);
				}
				if($valid) {
					if($device['method'] == 'reference' && $device['referencePath'] != '') {
						if(is_dir($device['referencePath'])) {
							$handle = opendir($device['referencePath']);
							while (false !== ($filename = readdir($handle))) {
								if( $filename == '.' || $filename == '..') continue;
								$image = new Image($si->db);
								$image->imageSetFullPath(rtrim($device['referencePath'],'/') . '/' . $filename);
								if(strtolower($image->imageGetName('ext')) != 'jpg') continue;
								$ar = explode('.',$filename);
								@array_pop($ar);
								$barcode = implode('.',$ar);
								if($image->imageBarcodeExists($barcode) == false) {
									$parts = array();
									$parts = preg_split("/[0-9]+/", $barcode);
									$collectionCode = $parts[0];
									unset($parts);
									$image->imageMkdirRecursive(rtrim($device['basePath'],'/') . '/' . $barcode);
									$path = rtrim($device['referencePath'],'/') . '/' . $filename;
									$ar = @getimagesize($path);

									$image->imageSetProperty('barcode',$barcode);
									$image->imageSetProperty('filename',$filename);
									$image->imageSetProperty('flickrPlantId',0);
									$image->imageSetProperty('picassaPlantId',0);
									$image->imageSetProperty('gTileProcessed',0);
									$image->imageSetProperty('zoomEnabled',0);
									$image->imageSetProperty('processed',0);
									$image->imageSetProperty('width',$ar[0]);
									$image->imageSetProperty('height',$ar[1]);
									$image->imageSetProperty('collectionCode',$collectionCode);
									$image->imageSetProperty('storageDeviceId', $storageDeviceId);
									$image->imageSetProperty('originalFilename', $filename);
									$res = $image->imageSave();
									if ($res) {
										$si->pqueue->processQueueSetProperty('imageId', $image->insert_id);
										$si->pqueue->processQueueSetProperty('processType', 'all');
										$si->pqueue->processQueueSave();
									}
									unset($image);
									$count++;
								}
							}
						}
					// } else if(is_dir($config['path']['incoming'])) {
					} else if($config['path']['incoming'] != '') {
						$incomingAr = array();
						if(is_array($config['path']['incoming']) && count($config['path']['incoming'])) {
							$incomingAr = $config['path']['incoming'];
						} else {
							$incomingAr[] = $config['path']['incoming'];
						}
						foreach($incomingAr as $incoming) {
							if(is_dir($incoming)) {
								$handle = opendir($incoming);
								while (false !== ($filename = readdir($handle))) {
								// echo '<br>';echo $filename;continue;
									if( $filename == '.' || $filename == '..') continue;
									
									$image = new Image($si->db);
									$image->imageSetFullPath($incoming . $filename);
									if(strtolower($image->imageGetName('ext')) != 'jpg') continue;
									$successFlag = $image->imageMoveToImages($storageDeviceId, $config['base100']);
									// echo '<pre>'; var_dump($successFlag);exit;
									if($successFlag['success']) {
										$barcode = $image->imageGetName();
										$filename = $image->imageGetProperty('filename');

										$parts = array();
										$parts = preg_split("/[0-9]+/", $barcode);
										$collectionCode = $parts[0];
										unset($parts);

										$device = $si->storage->storageDeviceGet($storageDeviceId);

										// $path = $config['path']['images'] . $image->imageBarcodePath( $barcode ) . $filename;
										$path = $device['basePath'] . $barcode . '/' . $filename;
										$ar = @getimagesize($path);

										# if barcode exits already, the image is replaced and the db record is reset and queue populated
										$imageId = '';
										if($image->imageBarcodeExists($barcode)) {
											$image->imageLoadByBarcode($barcode);
											$imageId = $image->imageGetProperty('imageId');
										}
										$image->imageSetProperty('barcode',$barcode);
										$image->imageSetProperty('filename',$filename);
										$image->imageSetProperty('flickrPlantId',0);
										$image->imageSetProperty('picassaPlantId',0);
										$image->imageSetProperty('gTileProcessed',0);
										$image->imageSetProperty('zoomEnabled',0);
										$image->imageSetProperty('processed',0);
										$image->imageSetProperty('width',$ar[0]);
										$image->imageSetProperty('height',$ar[1]);
										$image->imageSetProperty('collectionCode',$collectionCode);
										$image->imageSetProperty('storageDeviceId', $storageDeviceId);
									//	$image->imageSetProperty('path', $imagePath);
										$image->imageSetProperty('originalFilename', $filename);
										$res = $image->imageSave();
										if ($res) {
											$imageId = ($imageId == '') ? $image->insert_id : $imageId;
											$si->pqueue->processQueueSetProperty('imageId', $imageId);
											$si->pqueue->processQueueSetProperty('processType', 'all');
											$si->pqueue->processQueueSave();
										}
										unset($image);
										$count++;
									}
								}
							}
						}
						
					
						
					} 
					$response = ( json_encode( array('success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $count ) ) );
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'imageModifyRechop':
				if($imageId == '') {
					$valid = false;
					$errorCode = 157;
				}
				if($valid) {
					$ar = array();
					if(is_numeric($imageId)) {
						$imageIds = array($imageId);
					} else {
						$imageIds = json_decode($imageId,true);
					}
					if(is_array($imageIds) && count($imageIds)) {
						foreach($imageIds as $imageId) {
							if($si->image->imageLoadById($imageId)) {
								$si->image->imageSetProperty('flickrPlantID',0);
								$si->image->imageSetProperty('picassaPlantID',0);
								$si->image->imageSetProperty('gTileProcessed',0);
								$si->image->imageSetProperty('zoomEnabled',0);
								$si->image->imageSetProperty('processed',0);
								$si->image->imageSave();
						
								$si->pqueue->processQueueSetProperty('imageId', $imageId);
								$si->pqueue->processQueueSetProperty('processType','all');
								$si->pqueue->processQueueSave();

								$ar[] = $imageId;
							}
						}
					}
					$response = ( json_encode( array('success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($ar), 'results' => $ar ) ) );
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'imageModifyRotate':
				$data = array();
				$data['imageId'] = trim($imageId);
				$data['degree'] = trim($degree);
				$data['obj'] = $si->amazon;

				if(trim($imageId) == '') {
					$errorCode = 157;
					$valid = false;
				} else if(!$si->image->imageLoadById($imageId)) {
					$valid = false;
					$errorCode = 158;
				} else if(!in_array($data['degree'], array('90', '180', '270'))){
					$errorCode = 187;
					$valid = false;
				} else if(!($userAccess->is_logged_in() && $userAccess->get_accessLevel() == 10)){
					$errorCode = 104;
					$valid = false;
				}
				if($valid) {
					$si->image->imageSetData($data);
					if($si->image->imageModifyRotate()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart  ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(188)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'imageMoveExisting':
				if($imageId == '' || $newStorageId == '' || $newImagePath == '') {
					$valid = false;
					$errorCode = 189;
				} elseif(!$si->image->imageFieldExists($imageId)) {
					$valid = false;
					$errorCode = 170;
				} elseif(!$si->storage->storageDeviceExists($newStorageId)) {
					$valid = false;
					$errorCode = 156;
				}
				
				if($valid) {
					if($si->storage->storageDeviceMoveImage($imageId, $newStorageId, $newImagePath)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$errorCode = 190;
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(190)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'imageRemoveFromCollection':
				checkAuth();
				if($advFilterId != '') {
					if($si->advFilter->advFilterLoadById($advFilterId)) {
						$advFilter  = $si->advFilter->advFilterGetProperty('filter');
					}
				}
				$data['advFilter'] = json_decode(trim($advFilter),true);
				if($imageId == '' && !(is_array($data['advFilter']) && count($data['advFilter']))) {
					$valid = false;
					$errorCode = 220;
				} else if($imageId != '' && !(is_array($data['advFilter']) && count($data['advFilter']))) {
					if(is_numeric($imageId)) {
						if(!$si->image->imageLoadById($imageId)) {
							$valid = false;
							$errorCode = 158;
						} else {
							$imageId = array($imageId);
						}
					} else {
						$imageId = json_decode($imageId,true);
					}
				}
				if($valid) {
					$data['imageId'] = $imageId;
					$si->collection->collectionSetData($data);
					if($si->collection->collectionRemoveImage()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(176)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'imageTilesGet':
				$imageId = trim($imageId);
				$barcode = trim($barcode);
				$_TMP = ($config['path']['tmp'] != '') ? $config['path']['tmp'] : sys_get_temp_dir() . '/';
				if($imageId == "" && $barcode == '') {
					$valid = false;
					$errorCode = 210;
				} else if($imageId != "" && !$si->image->imageLoadById($imageId)) {
					$valid = false;
					$errorCode = 158;
				} else if($barcode != "" && !$si->image->imageLoadByBarcode($barcode)) {
					$valid = false;
					$errorCode = 211;
				} else if(!(isset($config['path']['tilesDb']) && $config['path']['tilesDb'] != '') || !(isset($config['path']['tiles']) && $config['path']['tiles'] != '')) {
					$valid = false;
					$errorCode = 212;
				} else {
					$si->image->imageMkdirRecursive($config['path']['tilesDb']);
					$si->image->imageMkdirRecursive($config['path']['tiles']);
					if(!file_exists($config['path']['tilesDb']) || !file_exists($config['path']['tiles'])) {
						$valid = false;
						$errorCode = 212;
					}
				}
				if($valid) {
					$barcode = $si->image->imageGetName();
					$filename = $si->image->imageGetProperty('filename');
					$imgPath = $si->image->imageGetProperty('path');
					$tmpPath = $si->storage->storageDeviceFileDownload($si->image->imageGetProperty('storageDeviceId'), $imgPath.'/'.$si->image->imageGetProperty('filename'));
					$t1 = explode("/", $tmpPath);
					$t2 = $t1[count($t1)-1];
					unset($t1[count($t1)-1]);
					$t1 = implode("/", $t1);
					$url = $config['tileGenerator'] . '?cmd=loadImage&filename=' . str_replace(' ', '%20', $t2) . '&absolutePath=' . rtrim($t1,'/') . '/';
					$t3 = explode(".", $t2);
					if(count($t3) > 1) {
						@array_pop($t3);
					}
					$fname = @implode('.',$t3);
					$res = json_decode(trim(@file_get_contents($url)));
					if(strtolower($si->storage->storageDeviceGetType($si->image->imageGetProperty('storageDeviceId'))) == 's3') {
						@unlink($tmpPath);
					}
					
					if(!$res->zoomLevel) {
						$zoomlevel = 0;
						$handles = @opendir($config['path']['tiles'] . strtolower($fname));
						while (false !== ($zooml = @readdir($handles))) {
							if( $zooml == '.' || $zooml == '..') continue;
							$zoomlevel++;
						}
					} else {
						$zoomlevel = $res->zoomLevel;
					}
					
					if(in_array(@strtolower($tiles),array('create','createclear'))) {
						// $si->image->imageMkdirRecursive( $config['path']['tilesDb'] );
						$tileFolder = @strtolower($fname);
						$it = new imgTiles($config['path']['tilesDb'] . $tileFolder . '.sqlite');

						$handle = @opendir($config['path']['tiles'] . $tileFolder);
						while (false !== ($zoom = @readdir($handle))) {
							if( $zoom == '.' || $zoom == '..') continue;
							$handle1 = @opendir($config['path']['tiles'] . $tileFolder . '/' . $zoom);
							while (false !== ($tile = readdir($handle1))) {
								if( $tile == '.' || $tile == '..') continue;
								$it->recordTile($zoom, $config['path']['tiles'] . $tileFolder . '/' . $zoom . '/' . $tile);
							}
						}
						if(@strtolower($tiles) == 'createclear') {
							$si->image->imageRmdirRecursive($config['path']['tiles'] . $tileFolder);
						}
					}
					$url = $config['tileUrl'] . strtolower($fname).'/';
					$tpl = $url . '{z}/tile_{i}.jpg';
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'url' => $url, 'tpl' => $tpl, 'maxZoomLevel' => $zoomlevel) ) );
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			# example : cmd=imageTilesLoad&filename=USMS000018155&zoom=2&index=tile_14.jpg
			case 'imageTilesLoad';
				$filename = @strtolower($filename);
				$filename = @basename($filename,'.jpg');
				$index = @str_replace('tile_','',@basename($index,'.jpg'));
				$it = new imgTiles($config['path']['tilesDb'] . $filename . '.sqlite');
				$result = $it->getTileData($zoom, $index);
				$type = 'image/jpeg';
				header('Content-Type:' . $type);
				print $result;
				break;
				
			case 'imageUpdate':
				checkAuth();
				if($imageId == "" && $barcode == '') {
					$valid = false;
					$errorCode = 210;
				} else if($imageId != "" && !$si->image->imageLoadById($imageId)) {
					$valid = false;
					$errorCode = 158;
				} else if($barcode != "" && !$si->image->imageLoadByBarcode($barcode)) {
					$valid = false;
					$errorCode = 211;
				}
				
				if($valid) {
					$fieldsArray = array('filename','barcode','width','height','family','genus','specificEpithet','rank','author','title','description','globalUniqueIdentifier','copyright','characters','flickrPlantID','flickrDetails','picassaPlantID','zoomEnabled','ocrValue','ocrFlag','scientificName','code','catalogueNumber','tmpFamily','tmpFamilyAccepted','tmpGenus','tmpGenusAccepted','storageDeviceId','path','originalFilename','remoteAccessKey','statusType','rating');
					// $params = @json_decode(trim($params),true);
					$params = @json_decode(trim($params),true);
//					$params = @json_decode(trim($params),true);
					if(is_array($params) && count($params)) {
						foreach($params as $key => $value) {
							if(@in_array($key,$fieldsArray)) {
								$si->image->imageSetProperty($key,$value);
							}
						}
						$si->image->imageSave();
					}
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart)));
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;


				
	# Geopraphy Commands			
			case 'geographyAdd':
				checkAuth();
				if($name == '') {
					$valid = false;
					$errorCode = 148;
				}
				if($si->geography->geographyNameExists($name)) {
					$valid = false;
					$errorCode = 139;
				}
				$rank = (in_array(trim($rank), array('0','1','2','3','4','5'))) ? $rank : 0;
				if($rank == 0 && $iso == '') {
					$valid = false;
					$errorCode = 138;
				}
				$parentId = (trim($parentId) != '') ? $parentId : 0;
				if($parentId != 0) { 
					if(!$si->geography->geographyExists($parentId)) {
						$valid = false;
						$errorCode = 231;
					}
				}

				if($valid) {
					$si->geography->geographySetProperty('parentId', $parentId);
					$si->geography->geographySetProperty('name', $name);
					$si->geography->geographySetProperty('varname', $varname);
					$si->geography->geographySetProperty('rank', $rank);
					$si->geography->geographySetProperty('iso', $iso);
					if(false === ($id = $si->geography->geographySave())) {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(143)) ));
					} else {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'geographyId' => $id ) ) );
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'geographyDelete':
				checkAuth();
				if($geographyId==''){
					$valid = false;
					$errorCode = 141;
				} elseif(!$si->geography->geographyLoadById($geographyId)) {
					$valid = false;
					$errorCode = 142;
				}
				if($valid) {
					if($si->geography->geographyDelete($geographyId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(144)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'geographyImport':
				
				break;
				
			case 'geographyList':
				checkAuth();
				$data['start'] = ($start == '') ? 0 : $start;
				$data['limit'] = ($limit == '') ? 100 : $limit;
				$data['geographyId'] = (!is_numeric($geographyId)) ? json_decode(trim($geographyId),true) : $geographyId;
				$data['parentId'] = (!is_numeric($parentId)) ? json_decode(trim($parentId),true) : $parentId;
				$data['ISO'] = $ISO;
				$data['filter'] = $filter;
				$data['rank'] = $rank;
				if($advFilterId != '') {
					if($si->advFilter->advFilterLoadById($advFilterId)) {
						$advFilter  = $si->advFilter->advFilterGetProperty('filter');
					}
				}
				$data['advFilter'] = json_decode(trim($advFilter),true);
				$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
				$data['value'] = str_replace('%','%%',trim($value));
				$data['group'] = $group;
				$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';
				$si->geography->geographySetData($data);
				if($valid) {
					$records = $si->geography->geographyList();
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $si->geography->db->query_total(), 'records' => $records ) ) );
				} else {
					$response = ( json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;
				
			case 'geographyUpdate':
				checkAuth();
				if($geographyId ==''){
					$valid = false;
					$errorCode = 141;
				} elseif(!$si->geography->geographyLoadById($geographyId)) {
					$valid = false;
					$errorCode = 142;
				} else {
					$rank = (in_array(trim($rank), array('0','1','2','3','4','5'))) ? $rank : $si->geography->geographyGetProperty('rank');
					if(trim($name) != '' && trim($name) != $si->geography->geographyGetProperty('name')) {
						if($si->geography->geographyNameExists(trim($name))) {
							$valid = false;
							$errorCode = 139;
						}
					}
					$iso = (trim($iso) != '') ? trim($iso) : $si->geography->geographyGetProperty('iso');
					if($rank == 0 && $iso == '') {
						$valid = false;
						$errorCode = 138;
					}
					$parentId = (trim($parentId) != '') ? $parentId : $si->geography->geographyGetProperty('parentId');
					if($parentId != 0) { 
						if(!$si->geography->geographyExists($parentId)) {
							$valid = false;
							$errorCode = 231;
						}
					}
					$varname = (trim($varname) != '') ? trim($varname) : $si->geography->geographyGetProperty('varname');
				}
				
				if($valid) {
					$si->geography->geographySetProperty('parentId', $parentId);
					$si->geography->geographySetProperty('name', $name);
					$si->geography->geographySetProperty('varname', $varname);
					$si->geography->geographySetProperty('rank', $rank);
					$si->geography->geographySetProperty('iso', $iso);
					if($si->geography->geographyUpdate()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(145)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'helpingscienceQueueList':
				$data['start'] = ($start != '') ? $start : 0;
				$data['limit'] = ($limit != '') ? $limit : 100;
				if(is_array($filter)) {
					$data['filter'] = $filter;
				} else {
					$data['filter'] = json_decode($filter,true);
				}
				$data['clientId'] = (!is_numeric($clientId)) ? json_decode(trim($clientId),true) : $clientId;
				$data['collectionId'] = (!is_numeric($collectionId)) ? json_decode(trim($collectionId),true) : $collectionId;
				$data['imageServerId'] = (!is_numeric($imageServerId)) ? json_decode(trim($imageServerId),true) : $imageServerId;
				$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
				$data['value'] = str_replace('%','%%',trim($value));
				$data['group'] = $group;
				$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';

				if($valid) {
					$si->bis->bis2HsSetData($data);
					$data = $si->bis->bis2HsList();
					$response = ( json_encode( array( 'success' => true, 'totalCount' => $si->bis->db->query_total(), 'records' => $data ) ) );
				}else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}

				break;
				
			case 'metadataPackageImport':
				if($url == '' || $key == '') {
					$valid = false;
					$errorCode = 106;
				} elseif(!$si->remoteAccess->remoteAccessCheck(ip2long($_SERVER['REMOTE_ADDR']), $key)) {
					$valid = false;
					$errorCode = 103;
				} elseif(!($fp = fopen($url, "r"))) {
					$valid = false;
					$errorCode = 107;
				}
				
				if(!($fp = fopen($url, "r"))) {
					$valid = false;
					$errorCode = 107;
				}
				
				if($valid) {
					$count = 0;
					while($data = fgetcsv($fp, NULL, ",")) {
						if($si->imageCategory->imageMetaDataPackageImport($data)) {
							$count++;
						}
					}
					if($count == 0) {
						$errorCode = 108;
						$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray($errorCode) ) ) );
					} else {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $count ) ) );
					}
				} else {
					$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray($errorCode) ) ) );
				}
				break;
				
			case 'metadataPackageList':
				if($config['path']['metadatapackages'] != '' && is_dir($config['path']['metadatapackages'])) {
					$list = array();
					$files = scandir($config['path']['metadatapackages']);
					foreach ($files as $file) {
						if ($file != "." && $file != "..") {
							$list[] = $file;
						}
					}
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => count($list), 'results' => $list ) ) );
				} else {
					$response = ( json_encode( array( 'success' => false,  'error' => $si->getErrorArray(192) ) ) );
				}
				break;

			case 'processQueue':
				$data['stop'] = $stop;
				$data['limit'] = $limit;
				if(is_numeric($imageId)) {
					$data['imageIds'] = array($imageId);
				} else {
					$data['imageIds'] = @json_decode($imageId,true);
				}
				$si->pqueue->processQueueSetData($data);
				$totalCount = $si->pqueue->processQueueProcess();
				$response = ( json_encode( array( 'success' => true, 'processTime' =>microtime(true) - $timeStart, 'totalCount' => $totalCount ) ) );
				break;

			case 'processQueueList':
				$data['start'] = ($start != '') ? $start : 0;
				$data['limit'] = ($limit != '') ? $limit : 100;
				$data['order'] = json_decode(trim($order),true);
				if(trim($sort) != '') {
					$data['sort'] = trim($sort);
				}
				if(is_array($filter)) {
					$data['filter'] = $filter;
				} else {
					$data['filter'] = json_decode(trim($filter),true);
				}
				$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
				$data['value'] = str_replace('%','%%',trim($value));
				$data['group'] = $group;
				$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';
				$si->pqueue->processQueueSetData($data);
				$data = $si->pqueue->processQueueList();
				$total = $si->pqueue->db->query_total();
				$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $total, 'results' => $data ) ) );
				break;

			case 'processQueueClear':
				$types = @json_decode(trim($types));
				if(is_numeric($imageId)) {
					$data['imageIds'] = array($imageId);
				} else {
					$data['imageIds'] = @json_decode($imageId,true);
				}

				if(is_array($types) && count($types)) {
					$data['processType'] = $types;
				}

				$si->pqueue->processQueueSetData($data);
				$allowedTypes = array('flickr_add', 'picassa_add', 'zoomify', 'google_tile', 'ocr_add', 'name_add', 'all', 'guess_add');
				if(false !== $ret = $si->pqueue->processQueueClear()) {
					$response = (json_encode(array('success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $ret)));
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(193)) ));
				}
				break;
				
			case 'remoteAccessKeyDelete':
				$whitelist=  array('localhost',  '127.0.0.1');
				if(!in_array($_SERVER['HTTP_HOST'],  $whitelist)){
					// $valid = false;
					$errorCode = 150;
				}
				if(!($userAccess->is_logged_in() && $userAccess->get_accessLevel() == 10)){
					$errorCode = 104;
					$valid = false;
				} else if($remoteAccessId == '') {
					$valid = false;
					$errorCode = 214;
				} else if(!$si->remoteAccess->remoteAccessLoadById($remoteAccessId)) {
					$valid = false;
					$errorCode = 215;
				}
				if($valid) {
					if($si->remoteAccess->remoteAccessDelete($remoteAccessId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(216)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'remoteAccessKeyDisable':
				$whitelist=  array('localhost',  '127.0.0.1');
				if(!in_array($_SERVER['HTTP_HOST'],  $whitelist)){
					// $valid = false;
					$errorCode = 150;
				}
				if(!($userAccess->is_logged_in() && $userAccess->get_accessLevel() == 10)){
					$errorCode = 104;
					$valid = false;
				} else if($remoteAccessId == '') {
					$valid = false;
					$errorCode = 214;
				} else if(!$si->remoteAccess->remoteAccessLoadById($remoteAccessId)) {
					$valid = false;
					$errorCode = 215;
				}
				if($valid) {
					$si->remoteAccess->remoteAccessSetProperty('active','false');
					if($si->remoteAccess->remoteAccessUpdate()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(217)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'remoteAccessKeyEnable':
				$whitelist=  array('localhost',  '127.0.0.1');
				if(!in_array($_SERVER['HTTP_HOST'],  $whitelist)){
					// $valid = false;
					$errorCode = 150;
				}
				if(!($userAccess->is_logged_in() && $userAccess->get_accessLevel() == 10)){
					$errorCode = 104;
					$valid = false;
				} else if($remoteAccessId == '') {
					$valid = false;
					$errorCode = 214;
				} else if(!$si->remoteAccess->remoteAccessLoadById($remoteAccessId)) {
					$valid = false;
					$errorCode = 215;
				}
				if($valid) {
					$si->remoteAccess->remoteAccessSetProperty('active','true');
					if($si->remoteAccess->remoteAccessUpdate()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(217)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
			
			case 'remoteAccessKeyList':
				$whitelist=  array('localhost',  '127.0.0.1');
				if(!in_array($_SERVER['HTTP_HOST'],  $whitelist)){
					//$valid = false;
					$errorCode = 150;
				}
				if($valid) {
					$data['start'] = (trim($start) == '') ? 0 : $start;
					$data['limit'] = (trim($limit) == '') ? 100 : $limit;
					$data['group'] = trim($group);
					$data['dir'] = (strtoupper(trim($dir)) == 'DESC') ? 'DESC' : 'ASC';

					$si->remoteAccess->remoteAccessSetData($data);
					$list = $si->remoteAccess->remoteAccessList();
					$listArray = array();
					while($record = $list->fetch_object())
					{
						$item['remoteAccessId'] = $record->remoteAccessId;
						$item['title'] = $record->title;
						$item['description'] = $record->description;
						$item['originalIp'] = $record->originalIp;
						$item['ip'] = $record->ip;
						$item['key'] = $record->key;
						$item['active'] = $record->active;
						$listArray[] = $item;
					}
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' => $si->remoteAccess->db->query_total(), 'records' => $listArray ) ) );
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'remoteAccessKeyGenerate':
				$whitelist=  array('localhost',  '127.0.0.1');
				if(!in_array($_SERVER['HTTP_HOST'],  $whitelist)){
					// $valid = false;
					$errorCode = 150;
				}
				if(!($userAccess->is_logged_in() && $userAccess->get_accessLevel() == 10)){
					$errorCode = 104;
					$valid = false;
				} else if($ip == '') {
					$valid = false;
					$errorCode = 213;
				} else if($title == '') {
					$valid = false;
					$errorCode = 112;
				} else if($si->remoteAccess->remoteAccessTitleExists($title)) {
					$valid = false;
					$errorCode = 219;
				}
				
				if($valid) {
					$active = ($active == 'false') ? 'false' : 'true';
					// $ip = ip2long($ip);
					$key = $si->remoteAccess->remoteAccessKeyGenerate();
					$si->remoteAccess->remoteAccessSetProperty('title',$title);
					$si->remoteAccess->remoteAccessSetProperty('description',$description);
					$si->remoteAccess->remoteAccessSetProperty('originalIp',$ip);
					$si->remoteAccess->remoteAccessSetProperty('ip',ip2long($ip));
					$si->remoteAccess->remoteAccessSetProperty('key',$key);
					$si->remoteAccess->remoteAccessSetProperty('active',$active);
					$id = $si->remoteAccess->remoteAccessSave();
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'remoteAccessId' => $id) ) );
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

				
			case 'setAdd':
				checkAuth();
				if($name == '') {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(148)) ));
				} else if($si->set->setLoadByName($name)) {
					$data['setId'] = $si->set->setGetProperty('setId');
					$data['name'] = $si->set->setGetProperty('name');
					$data['description'] = $si->set->setGetProperty('description');
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(195), 'results' => $data) ));
				} else {
					$si->set->setSetProperty('name',$name);
					$si->set->setSetProperty('description',$description);
					if(false !== $setId = $si->set->setAdd()) {
						$response = (json_encode(array('success' => true, 'processTime' => microtime(true) - $timeStart, 'setId' => $setId)));
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(196)) ));
					}
				}
				break;
				
			case 'setDelete':
				checkAuth();
				if($setId == '') {
					$valid = false;
					$errorCode = 197;
				} elseif(!$si->set->setLoadById($setId)) {
					$valid = false;
					$errorCode = 198;
				}
				if($valid) {
					if($si->set->setDelete($setId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(199)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'setList':
				$data['setId'] = (!is_numeric($setId)) ? json_decode(trim($setId),true) : array($setId);
				$data['searchFormat'] = in_array(strtolower(trim($searchFormat)),array('exact','left','right','both')) ? strtolower(trim($searchFormat)) : 'both';
				$data['value'] = str_replace('%','%%',trim($value));
				$si->set->setSetData($data);
				$data = $si->set->setList();
				$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'results' => $data ) ) );
				break;
				
			case 'setUpdate':
				checkAuth();
				if($setId == '') {
					$valid = false;
					$errorCode = 197;
				} else if(!$si->set->setLoadById($setId)) {
					$valid = false;
					$errorCode = 198;
				} else if($name == '') {
					$valid = false;
					$errorCode = 148;
				} else if($name != $si->set->setGetProperty('name') && $si->set->setNameExists($name)) {
					$valid = false;
					$errorCode = 195;
				}
				
				if($valid) {
					$si->set->setSetProperty('setId',$setId);
					$si->set->setSetProperty('name',$name);
					$si->set->setSetProperty('description',$description);
					if($si->set->setUpdate()) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(200)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'setValueAdd':
				checkAuth();
				if($setId == '') {
					$valid = false;
					$errorCode = 197;
				} else if(!$si->set->setLoadById($setId)) {
					$valid = false;
					$errorCode = 198;
				} else if($attributeId == "") {
					$valid = false;
					$errorCode = 109;
				} else if (!$si->imageAttribute->imageAttributeExists($attributeId)) {
					$valid = false;
					$errorCode = 149;
				}
				if($valid) {
					$rank = isset($rank)?$rank:0;
					if(false !== $setValueId = $si->set->setValuesAdd($setId, $attributeId, $rank)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'setValueId' => $setValueId ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(201)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'setValueDelete':
				checkAuth();
				if($setValueId == '') {
					$valid = false;
					$errorCode = 202;
				} else if(!$si->set->setValuesExistsById($setValueId)) {
					$valid = false;
					$errorCode = 203;
				}
				if($valid) {
					if($si->set->setValuesDelete($setValueId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(204)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
				
			case 'setValueUpdate':
				checkAuth();
				if($setId == '' || $attributeId == '' || $setValueId == '') {
					$valid = false;
					$errorCode = 206;
				} else if(!$si->set->setLoadById($setId)) {
					$valid = false;
					$errorCode = 198;
				} else if(!$si->imageAttribute->imageAttributeExists($attributeId)) {
					$valid = false;
					$errorCode = 149;
				} else if(!$si->set->setValuesExistsById($setValueId)) {
					$valid = false;
					$errorCode = 203;
				}
				if($valid) {
					$rank = isset($rank)?$rank:'';
					if($si->set->setValuesUpdate($setValueId, $setId, $attributeId, $rank)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(205)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
			case 'silvercollectionLoadLogs':
				switch($config['mode']) {
					case 's3':
						$data['mode'] = $config['mode'];
						$data['s3'] = $config['s3'];
						$data['obj'] = $si->amazon;
						$si->logger->loggerSetData($data);
						$ret = $si->logger->loggerLoadS3Logs();
						break;
					default:
						$data['path_files'] = $config['path']['files'];
						$data['processed_files'] = $config['path']['processed_files'];
						$si->logger->setDataloggerSetData($data);
						$ret = $si->logger->loggerLoadLogs();
						break;
				}
				if($ret !== false) {
					$response = ( json_encode ( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'totalCount' =>  $ret) ) );
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(209)) ));
				}
				break;
				
			case 'storageDeviceAdd':
				checkAuth();
				$data['name'] = trim($name);
				$data['description'] = trim($description);
				$data['type'] = trim($type);
				$data['baseUrl'] = trim($baseUrl);
				$data['basePath'] = trim($basePath);
				$data['userName'] = trim($userName);
				$data['password'] = trim($password);
				$data['key'] = trim($key);
				$data['extra2'] = trim($extra);
				$data['active'] = (trim($active) == 'false') ? false : true;
				$data['method'] = (trim(@strtolower($method)) == 'reference') ? 'reference' : '';
				$data['referencePath'] = trim($referencePath);
				$default = (trim($default) == 'true') ? true : false;
				if($name=='' || $type=='' || $baseUrl=='') {
					$errorCode = 151;
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				} else {
					$si->storage->storageDeviceSetAll($data);
					$id = $si->storage->storageDeviceSave();
					if($id) {
						if($default) {
							$si->storage->storageDeviceSetDefault($id);
						}
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'storageDeviceId' => $id ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(152)) ));
					}
				}
				break;
			
			case 'storageDeviceDelete':
				checkAuth();
				if(($storageDeviceId == '') || (!$si->storage->storageDeviceExists($storageDeviceId))) {
					$valid = false;
					$errorCode = 156;
				}
				if($valid){
					if($si->storage->storageDeviceDelete($storageDeviceId)) {
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(154)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;
			
			case 'storageDeviceList':
				if(is_array($si->storage->devices)) {
					$response = ( json_encode( array( 'success' => true, 'totalCount' => count($si->storage->devices), 'processTime' => microtime(true) - $timeStart, 'records' => $si->storage->devices ) ) );
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(155)) ));
				}
				break;
				
			case 'storageDeviceUpdate':
				checkAuth();
				if(($storageDeviceId == '') || (!$si->storage->storageDeviceExists($storageDeviceId))) {
					$valid = false;
					$errorCode = 156;
				}
				if($valid){
					$data = $si->storage->storageDeviceGet($storageDeviceId);
					(trim($name) != '' ) ? $data['name'] = trim($name) : '';
					(trim($description) != '' ) ? $data['description'] = trim($description) : '';
					(trim($type) != '' ) ? $data['type'] = trim($type) : '';
					(trim($baseUrl) != '' ) ? $data['baseUrl'] = trim($baseUrl) : '';
					(trim($basePath) != '' ) ? $data['basePath'] = trim($basePath) : '';
					(trim($method) != '' ) ? $data['method'] = trim($method) : '';
					(trim($referencePath) != '' ) ? $data['referencePath'] = trim($referencePath) : '';
					(trim($userName) != '' ) ? $data['userName'] = trim($userName) : '';
					(trim($password) != '' ) ? $data['password'] = trim($password) : '';
					(trim($key) != '' ) ? $data['key'] = trim($key) : '';
					(trim($extra) != '' ) ? $data['extra2'] = trim($extra) : '';
					in_array(@strtolower(trim($active)), array('true','false')) ? ($data['active'] = (@strtolower(trim($active) == 'false')) ? false : true) : '';
					$default = (trim($default) == 'true') ? true : false;
					$si->storage->storageDeviceSetAll($data);
					if($si->storage->storageDeviceUpdate()) {
						if($default) {
							$si->storage->storageDeviceSetDefault($storageDeviceId);
						}
						$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
					} else {
						$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray(153)) ));
					}
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
			
				break;
				
			case 'storageDeviceSetDefault':
				if(($storageDeviceId == '') || (!$si->storage->storageDeviceExists($storageDeviceId))) {
					$valid = false;
					$errorCode = 156;
				}
				if($valid){
					$si->storage->storageDeviceSetDefault($storageDeviceId);
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
				} else {
					$response =  (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			case 'userInfo':
				checkAuth();
				$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart, 'records' => array('user' => $_SESSION['user'], 'userId' => $_SESSION['user_id'], 'userRealName' => $_SESSION['userRealName']) ) ) );
				break;

			case 'userLogout':
				checkAuth();
				$userAccess->log_out();
				$response = ( json_encode( array( 'success' => true ) ) );
				break;
				
			case 'userSetTrusted':
				if(!($userAccess->is_logged_in() && $userAccess->get_accessLevel() == 10)){
					$errorCode = 104;
					$valid = false;
				} else if($userId == '') {
					$errorCode = 194;
					$valid = false;
				}
				if($valid) {
					$statusType = ($statusType == 'false') ? 0 : 1;
					$query = sprintf(" UPDATE `users` SET `statusType` = %d WHERE `userId` = '%s' ", $statusType, $userId);
					if($si->db->query($query)) {
						$si->imageRating->imageRatingUpdateTrustedUserImages($userId,$statusType);
					}
					$response = ( json_encode( array( 'success' => true, 'processTime' => microtime(true) - $timeStart ) ) );
				} else {
					$response = (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				}
				break;

			default:
				$errorCode = 100;
					$response = (json_encode( array( 'success' => false, 'error' => $si->getErrorArray($errorCode)) ));
				break;
		}
	    return($response); 
	}
	
	$cmd = $GET['cmd'];
	$callback = $GET['callback'];
	if ($cmd == "package") {
		$data = $GET['data'];
		$res = @json_decode(trim($data),true);
//		$res = @json_decode(trim($data),true);
		$packageResults = array();
		foreach($res as $request) {
			$packageResults[] = router($request);
		}
		// echo '<br>';
		// print_r($packageResults);
//		print_c (json_encode(array('success' => true, 'processTime' => microtime(true) - $timeStart, 'request' => $data, 'records' => $packageResults)));
		print_c (json_encode(array('success' => true, 'processTime' => microtime(true) - $timeStart, 'records' => $packageResults)));
	} else {
	  print_c (router());
	}	

	ob_end_flush();
?>
