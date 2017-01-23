<?php
/**
  @version   v5.21.0-dev  ??-???-2016
  @copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
  @copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community

  This is a version of the ADODB driver for DB2.  It uses the 'ibm_db2' PECL extension
  for PHP (http://pecl.php.net/package/ibm_db2), which in turn requires DB2 V8.2.2 or
  higher.

  Originally tested with PHP 5.1.1 and Apache 2.0.55 on Windows XP SP2.
  More recently tested with PHP 5.1.2 and Apache 2.0.55 on Windows XP SP2.

  This file was ported from "adodb-odbc.inc.php" by Larry Menard, "larry.menard#rogers.com".
  I ripped out what I believed to be a lot of redundant or obsolete code, but there are
  probably still some remnants of the ODBC support in this file; I'm relying on reviewers
  of this code to point out any other things that can be removed.
*/

namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;

/*--------------------------------------------------------------------------------------
	 Class Name: Recordset
--------------------------------------------------------------------------------------*/

class db2 extends ADORecordSet {

	var $bind = false;
	var $databaseType = "db2";
	var $dataProvider = "db2";
	var $useFetchArray;

	function __construct($id,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		$this->fetchMode = $mode;

		$this->_queryID = $id;
	}


	// returns the field object
	function FetchField($offset = -1)
	{
		$o= new ADOFieldObject();
		$o->name = @db2_field_name($this->_queryID,$offset);
		$o->type = @db2_field_type($this->_queryID,$offset);
		$o->max_length = db2_field_width($this->_queryID,$offset);
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
		$this->_numOfRows = ($ADODB_COUNTRECS) ? @db2_num_rows($this->_queryID) : -1;
		$this->_numOfFields = @db2_num_fields($this->_queryID);
		// some silly drivers such as db2 as/400 and intersystems cache return _numOfRows = 0
		if ($this->_numOfRows == 0) $this->_numOfRows = -1;
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

			$this->fields = @db2_fetch_array($this->_queryID);
			if ($this->fields) {
				if ($this->fetchMode & ADODB_FETCH_ASSOC) {
					$this->fields = $this->GetRowAssoc();
				}
				return true;
			}
		}
		$this->fields = false;
		$this->EOF = true;
		return false;
	}

	function _fetch()
	{

		$this->fields = db2_fetch_array($this->_queryID);
		if ($this->fields) {
			if ($this->fetchMode & ADODB_FETCH_ASSOC) {
				$this->fields = $this->GetRowAssoc();
			}
			return true;
		}
		$this->fields = false;
		return false;
	}

	function _close()
	{
		return @db2_free_result($this->_queryID);
	}

}

