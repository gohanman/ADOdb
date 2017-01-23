<?php
/*
@version   v5.21.0-dev  ??-???-2016
@copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
Set tabs to 4 for best viewing.

  Latest version is available at http://adodb.sourceforge.net

  Requires ODBC. Works on Windows and Unix.
*/
namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;

// security - hide paths
if (!defined('ADODB_DIR')) die();

/*--------------------------------------------------------------------------------------
	 Class Name: Recordset
--------------------------------------------------------------------------------------*/

class odbc extends ADORecordSet {

	var $bind = false;
	var $databaseType = "odbc";
	var $dataProvider = "odbc";
	var $useFetchArray;
	var $_has_stupid_odbc_fetch_api_change;

	function __construct($id,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		$this->fetchMode = $mode;

		$this->_queryID = $id;

		// the following is required for mysql odbc driver in 4.3.1 -- why?
		$this->EOF = false;
		$this->_currentRow = -1;
		//parent::__construct($id);
	}


	// returns the field object
	function FetchField($fieldOffset = -1)
	{

		$off=$fieldOffset+1; // offsets begin at 1

		$o= new ADOFieldObject();
		$o->name = @odbc_field_name($this->_queryID,$off);
		$o->type = @odbc_field_type($this->_queryID,$off);
		$o->max_length = @odbc_field_len($this->_queryID,$off);
		if (ADODB_ASSOC_CASE == 0) $o->name = strtolower($o->name);
		else if (ADODB_ASSOC_CASE == 1) $o->name = strtoupper($o->name);
		return $o;
	}

	/* Use associative array to get fields array */
	function Fields($colname)
	{
		if ($this->fetchMode & ADODB_FETCH_ASSOC) return $this->fields[$colname];
		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$o = $this->FetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}

		 return $this->fields[$this->bind[strtoupper($colname)]];
	}


	function _initrs()
	{
	global $ADODB_COUNTRECS;
		$this->_numOfRows = ($ADODB_COUNTRECS) ? @odbc_num_rows($this->_queryID) : -1;
		$this->_numOfFields = @odbc_num_fields($this->_queryID);
		// some silly drivers such as db2 as/400 and intersystems cache return _numOfRows = 0
		if ($this->_numOfRows == 0) $this->_numOfRows = -1;
		//$this->useFetchArray = $this->connection->useFetchArray;
		$this->_has_stupid_odbc_fetch_api_change = ADODB_PHPVER >= 0x4200;
	}

	function _seek($row)
	{
		return false;
	}

	// speed up SelectLimit() by switching to ADODB_FETCH_NUM as ADODB_FETCH_ASSOC is emulated
	function GetArrayLimit($nrows,$offset=-1)
	{
		if ($offset <= 0) {
			$rs = $this->GetArray($nrows);
			return $rs;
		}
		$savem = $this->fetchMode;
		$this->fetchMode = ADODB_FETCH_NUM;
		$this->Move($offset);
		$this->fetchMode = $savem;

		if ($this->fetchMode & ADODB_FETCH_ASSOC) {
			$this->fields = $this->GetRowAssoc();
		}

		$results = array();
		$cnt = 0;
		while (!$this->EOF && $nrows != $cnt) {
			$results[$cnt++] = $this->fields;
			$this->MoveNext();
		}

		return $results;
	}


	function MoveNext()
	{
		if ($this->_numOfRows != 0 && !$this->EOF) {
			$this->_currentRow++;
			if( $this->_fetch() ) {
				return true;
			}
		}
		$this->fields = false;
		$this->EOF = true;
		return false;
	}

	function _fetch()
	{
		$this->fields = false;
		if ($this->_has_stupid_odbc_fetch_api_change)
			$rez = @odbc_fetch_into($this->_queryID,$this->fields);
		else {
			$row = 0;
			$rez = @odbc_fetch_into($this->_queryID,$row,$this->fields);
		}
		if ($rez) {
			if ($this->fetchMode & ADODB_FETCH_ASSOC) {
				$this->fields = $this->GetRowAssoc();
			}
			return true;
		}
		return false;
	}

	function _close()
	{
		return @odbc_free_result($this->_queryID);
	}

}

