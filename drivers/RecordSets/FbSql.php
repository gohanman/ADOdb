<?php
/*
 @version   v5.21.0-dev  ??-???-2016
 @copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
 @copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
 Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
 Contribution by Frank M. Kromann <frank@frontbase.com>.
  Set tabs to 8.
*/

namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;

// security - hide paths
if (!defined('ADODB_DIR')) die();

/*--------------------------------------------------------------------------------------
	 Class Name: Recordset
--------------------------------------------------------------------------------------*/

class FbSql extends ADORecordSet{

	var $databaseType = "fbsql";
	var $canSeek = true;

	function __construct($queryID,$mode=false)
	{
		if (!$mode) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		switch ($mode) {
		case ADODB_FETCH_NUM: $this->fetchMode = FBSQL_NUM; break;
		case ADODB_FETCH_ASSOC: $this->fetchMode = FBSQL_ASSOC; break;
		case ADODB_FETCH_BOTH:
		default:
		$this->fetchMode = FBSQL_BOTH; break;
		}
		return parent::__construct($queryID);
	}

	function _initrs()
	{
	GLOBAL $ADODB_COUNTRECS;
		$this->_numOfRows = ($ADODB_COUNTRECS) ? @fbsql_num_rows($this->_queryID):-1;
		$this->_numOfFields = @fbsql_num_fields($this->_queryID);
	}



	function FetchField($fieldOffset = -1) {
		if ($fieldOffset != -1) {
			$o =  @fbsql_fetch_field($this->_queryID, $fieldOffset);
			//$o->max_length = -1; // fbsql returns the max length less spaces -- so it is unrealiable
			$f = @fbsql_field_flags($this->_queryID,$fieldOffset);
			$o->binary = (strpos($f,'binary')!== false);
		}
		else if ($fieldOffset == -1) {	/*	The $fieldOffset argument is not provided thus its -1 	*/
			$o = @fbsql_fetch_field($this->_queryID);// fbsql returns the max length less spaces -- so it is unrealiable
			//$o->max_length = -1;
		}

		return $o;
	}

	function _seek($row)
	{
		return @fbsql_data_seek($this->_queryID,$row);
	}

	function _fetch($ignore_fields=false)
	{
		$this->fields = @fbsql_fetch_array($this->_queryID,$this->fetchMode);
		return ($this->fields == true);
	}

	function _close() {
		return @fbsql_free_result($this->_queryID);
	}

	function MetaType($t,$len=-1,$fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}
		$len = -1; // fbsql max_length is not accurate
		switch (strtoupper($t)) {
		case 'CHARACTER':
		case 'CHARACTER VARYING':
		case 'BLOB':
		case 'CLOB':
		case 'BIT':
		case 'BIT VARYING':
			if ($len <= $this->blobSize) return 'C';

		// so we have to check whether binary...
		case 'IMAGE':
		case 'LONGBLOB':
		case 'BLOB':
		case 'MEDIUMBLOB':
			return !empty($fieldobj->binary) ? 'B' : 'X';

		case 'DATE': return 'D';

		case 'TIME':
		case 'TIME WITH TIME ZONE':
		case 'TIMESTAMP':
		case 'TIMESTAMP WITH TIME ZONE': return 'T';

		case 'PRIMARY_KEY':
			return 'R';
		case 'INTEGER':
		case 'SMALLINT':
		case 'BOOLEAN':

			if (!empty($fieldobj->primary_key)) return 'R';
			else return 'I';

		default: return ADODB_DEFAULT_METATYPE;
		}
	}

} //class

