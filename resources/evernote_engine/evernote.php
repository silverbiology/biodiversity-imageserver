<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors',1);
/**
 * Evernote engine interface
 */

require_once('./config.php');
require_once('classes/class.evernote.php');
require_once('classes/class.xml2json.php');

$expected=array(
	  'cmd'
	, 'api'
	, 'auth'
	, 'label'
	, 'title'
	, 'noteGuid'
	, 'searchWord'
	, 'start'
	, 'limit'
	, 'tag'
);

// Initialize allowed variables
foreach ($expected as $formvar)
	$$formvar = (isset(${"_$_SERVER[REQUEST_METHOD]"}[$formvar])) ? ${"_$_SERVER[REQUEST_METHOD]"}[$formvar]:NULL;


$evernote = new EverNote($EverNoteValues);

$valid = true;

# echo '<pre>';

switch($cmd) {

	case 'add_note':

		if(trim($auth) == '') {
			$valid = false;
			$code = 101;
		} else {
			$auth = json_decode(stripslashes(trim($auth)),true);
		}

		if(trim($label) == '') {
			$valid = false;
			$code = 102;
		}

		if(trim($title) == '') {
			$valid = false;
			$code = 103;
		}

		header('Content-type: application/json');
		if($valid) {

			$tag = (trim($tag)!='') ? json_decode(stripslashes(trim($tag)), true) : null;
			$evernote->authenticate($auth);
			$note = $evernote->createNote($label,$title, $tag);
			$noteRet = $evernote->addNote($note);
			print json_encode( array( 'success' => true, 'noteRet' => $noteRet ) );
		} else {
			print json_encode( array( 'success' => false, 'error' => array( 'msg' => getError($code), 'code' => $code ) ) );
		}

		break;

	case 'get_recognition':
		if(trim($auth) == '') {
			$valid = false;
			$code = 101;
		} else {
			$auth = json_decode(stripslashes(trim($auth)),true);
		}

		if(trim($noteGuid) == '') {
			$valid = false;
			$code = 104;
		}

		header('Content-type: application/json');
		if($valid) {
			$evernote->authenticate($auth);

			$note = $evernote->listNotes($noteGuid);
			$resources = $note->resources;
			$resource_id = $resources[0]->guid;

			if( !is_null($resource_id) &&  $resource_id != '' ) {
				$ocr_xml = $evernote->getRecognition($resource_id);
				$ocr_json = xml2json::transformXmlStringToJson($ocr_xml);
				$ocr_data = json_decode($ocr_json, true );

				$ocr_data = $evernote->filterOcrData($ocr_data);

			}

			print json_encode( array( 'success' => true, 'ocr_data' =>  $ocr_data) );
		} else {
			print json_encode( array( 'success' => false, 'error' => array( 'msg' => getError($code), 'code' => $code ) ) );
		}

		break;

	case 'get_usage':
		if(trim($auth) == '') {
			$valid = false;
			$code = 101;
		} else {
			$auth = json_decode(stripslashes(trim($auth)),true);
		}

		header('Content-type: application/json');
		if($valid) {
			$evernote->authenticate($auth);
			$syncState = $evernote->getUsage();

			print json_encode( array( 'success' => true, 'sync_state' =>  $syncState) );
		} else {
			print json_encode( array( 'success' => false, 'error' => array( 'msg' => getError($code), 'code' => $code ) ) );
		}

		break;

	case 'findNotes':
		if(trim($auth) == '') {
			$valid = false;
			$code = 101;
		} else {
			$auth = json_decode(stripslashes(trim($auth)),true);
		}
		$start = ($start == '') ? 0 : $start;
		$limit = ($limit == '') ? 25 : $limit;
		header('Content-type: application/json');
		if($valid) {
			$evernote->authenticate($auth);
			$tag = (trim($tag)!='') ? json_decode(stripslashes(trim($tag)), true) : null;
			$filter = array('order' => null
					, 'ascending' => null
					, 'words' => $searchWord
					, 'notebookGuid' => $auth[0]['notebookGuid']
					, 'tagGuids' => $tag
					, 'timeZone' => null
					, 'inactive' => null);
					
			$filter = $evernote->createFilter($filter);
		
			$ret = $evernote->findEverNotes($filter,$start,$limit);
			$labelArray = array();
			if($ret['success']) {
				$notes = $ret['noteRet']->notes;
				if(is_array($notes) && count($notes)) {
					foreach($notes as $note) {
						$labelArray[] = $note->updateSequenceNum;
					}
				}
			}
			$totalNotes = (is_null($ret['noteRet']->totalNotes) || $ret['noteRet']->totalNotes == '') ? 0 : $ret['noteRet']->totalNotes;
			print json_encode(array("success" => true,'totalNotes' => $totalNotes, 'data' => $labelArray));
		} else {
			print json_encode( array( 'success' => false, 'error' => array( 'msg' => getError($code), 'code' => $code ) ) );
		}

		break;
		
	case 'listNotebooks':
		if(trim($auth) == '') {
			$valid = false;
			$code = 101;
		} else {
			$auth = json_decode(stripslashes(trim($auth)),true);
		}
		$evernote->authenticate($auth);
		$ret = $evernote->listNotebooks();
		echo '<pre>';
		var_dump($ret);
		break;

	default:
			$code = 105;
			print json_encode( array( 'success' => false, 'error' => array( 'msg' => getError($code), 'code' => $code ) ) );
		break;

}

function getError($error_code) {
	$ar = array (
		    101 => 'Authentication details must be provided!.'
		  , 102 => 'Label details must be provided!.'
		  , 103 => 'Title must be provided!.'
		  , 104 => 'noteGuid must be provided!.'
		  , 105 => 'No Command Provided!.'
	);
	return $ar[$error_code];
}


?>