<?php

Class RemoteAccess {

	public $db, $record;

	public function __construct($db = null) {
		$this->db = $db;
		$this->record['active'] = 'true';
	}
	
	public function remoteAccessSetProperty( $field, $value ) {
		$this->record[$field] = $value;
		return(true);
	}
	
	public function remoteAccessGetProperty( $field ) {
		if (isset($this->record[$field])) {
			return( $this->record[$field] );
		} else {
			return(false);
		}
	}
	
	public function remoteAccessSave() {
		if($this->remoteAccessCheckDuplicate($this->remoteAccessGetProperty('ip'),$this->remoteAccessGetProperty('key'))) {
			return true;
		} else {
			$query = sprintf("INSERT IGNORE INTO `remoteAccess` SET `ip` = '%s', `key` = '%s', `active` = '%s' ;"
			, mysql_escape_string($this->remoteAccessGetProperty('ip'))
			, mysql_escape_string($this->remoteAccessGetProperty('key'))
			, mysql_escape_string($this->remoteAccessGetProperty('active'))
			);
			if($this->db->query($query)) {
				return(true);
			} else {
				return (false);
			}
		}
	}
	
	public function remoteAccessList() {
		$query = "SELECT * FROM remoteAccess";
		$ret = $this->db->query($query);
		return $ret;
	}
	
	public function remoteAccessCheck($ip, $tmpKey) {
		return true; //To temporarly disable this check and always validate
		$query = sprintf("SELECT count(*) AS cnt FROM `remoteAccess` WHERE `ip` = '%s' AND `key` = '%s' AND `active` = '%s' ;"
		, mysql_escape_string($ip)
		, mysql_escape_string($tmpKey)
		, "true"
		);
		$ret = $this->db->query_one($query);
		if($ret->cnt) {
			return true;
		} else {
			return false;
		}
	}
	
	public function remoteAccessCheckDuplicate($ip, $tmpKey) {
		$query = sprintf("SELECT count(*) AS cnt FROM `remoteAccess` WHERE `ip` = '%s' AND `key` = '%s' ;"
		, mysql_escape_string($ip)
		, mysql_escape_string($tmpKey)
		);
		$ret = $this->db->query_one($query);
		if($ret->cnt) {
			return true;
		} else {
			return false;
		}
	}

}

?>