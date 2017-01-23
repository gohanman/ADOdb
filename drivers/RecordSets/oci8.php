<?php
/*

  @version   v5.21.0-dev  ??-???-2016
  @copyright (c) 2000-2013 John Lim. All rights reserved.
  @copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community

  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.

  Latest version is available at http://adodb.sourceforge.net

  Code contributed by George Fourlanos <fou@infomap.gr>

  13 Nov 2000 jlim - removed all ora_* references.
*/

namespace ADOdb\drivers\RecordSets;
use \ADOConnection;
use \ADORecordSet;

/*--------------------------------------------------------------------------------------
	Class Name: Recordset
--------------------------------------------------------------------------------------*/

class oci8 extends ADORecordSet {

	var $databaseType = 'oci8';
	var $bind=false;
	var $_fieldobjs;

	function __construct($queryID,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		switch ($mode) {
			case ADODB_FETCH_ASSOC:
				$this->fetchMode = OCI_ASSOC;
				break;
			case ADODB_FETCH_DEFAULT:
			case ADODB_FETCH_BOTH:
				$this->fetchMode = OCI_NUM + OCI_ASSOC;
				break;
			case ADODB_FETCH_NUM:
			default:
				$this->fetchMode = OCI_NUM;
				break;
		}
		$this->fetchMode += OCI_RETURN_NULLS + OCI_RETURN_LOBS;
		$this->adodbFetchMode = $mode;
		$this->_queryID = $queryID;
	}


	function Init()
	{
		if ($this->_inited) {
			return;
		}

		$this->_inited = true;
		if ($this->_queryID) {

			$this->_currentRow = 0;
			@$this->_initrs();
			if ($this->_numOfFields) {
				$this->EOF = !$this->_fetch();
			}
			else $this->EOF = true;

			/*
			// based on idea by Gaetano Giunta to detect unusual oracle errors
			// see http://phplens.com/lens/lensforum/msgs.php?id=6771
			$err = oci_error($this->_queryID);
			if ($err && $this->connection->debug) {
				ADOConnection::outp($err);
			}
			*/

			if (!is_array($this->fields)) {
				$this->_numOfRows = 0;
				$this->fields = array();
			}
		} else {
			$this->fields = array();
			$this->_numOfRows = 0;
			$this->_numOfFields = 0;
			$this->EOF = true;
		}
	}

	function _initrs()
	{
		$this->_numOfRows = -1;
		$this->_numOfFields = oci_num_fields($this->_queryID);
		if ($this->_numOfFields>0) {
			$this->_fieldobjs = array();
			$max = $this->_numOfFields;
			for ($i=0;$i<$max; $i++) $this->_fieldobjs[] = $this->_FetchField($i);
		}
	}

	/**
	 * Get column information in the Recordset object.
	 * fetchField() can be used in order to obtain information about fields
	 * in a certain query result. If the field offset isn't specified, the next
	 * field that wasn't yet retrieved by fetchField() is retrieved
	 *
	 * @return object containing field information
	 */
	function _FetchField($fieldOffset = -1)
	{
		$fld = new ADOFieldObject;
		$fieldOffset += 1;
		$fld->name =oci_field_name($this->_queryID, $fieldOffset);
		if (ADODB_ASSOC_CASE == ADODB_ASSOC_CASE_LOWER) {
			$fld->name = strtolower($fld->name);
		}
		$fld->type = oci_field_type($this->_queryID, $fieldOffset);
		$fld->max_length = oci_field_size($this->_queryID, $fieldOffset);

		switch($fld->type) {
			case 'NUMBER':
				$p = oci_field_precision($this->_queryID, $fieldOffset);
				$sc = oci_field_scale($this->_queryID, $fieldOffset);
				if ($p != 0 && $sc == 0) {
					$fld->type = 'INT';
				}
				$fld->scale = $p;
				break;

			case 'CLOB':
			case 'NCLOB':
			case 'BLOB':
				$fld->max_length = -1;
				break;
		}
		return $fld;
	}

	/* For some reason, oci_field_name fails when called after _initrs() so we cache it */
	function FetchField($fieldOffset = -1)
	{
		return $this->_fieldobjs[$fieldOffset];
	}


	function MoveNext()
	{
		if ($this->fields = @oci_fetch_array($this->_queryID,$this->fetchMode)) {
			$this->_currentRow += 1;
			$this->_updatefields();
			return true;
		}
		if (!$this->EOF) {
			$this->_currentRow += 1;
			$this->EOF = true;
		}
		return false;
	}

	// Optimize SelectLimit() by using oci_fetch()
	function GetArrayLimit($nrows,$offset=-1)
	{
		if ($offset <= 0) {
			$arr = $this->GetArray($nrows);
			return $arr;
		}
		$arr = array();
		for ($i=1; $i < $offset; $i++) {
			if (!@oci_fetch($this->_queryID)) {
				return $arr;
			}
		}

		if (!$this->fields = @oci_fetch_array($this->_queryID,$this->fetchMode)) {
			return $arr;
		}
		$this->_updatefields();
		$results = array();
		$cnt = 0;
		while (!$this->EOF && $nrows != $cnt) {
			$results[$cnt++] = $this->fields;
			$this->MoveNext();
		}

		return $results;
	}


	// Use associative array to get fields array
	function Fields($colname)
	{
		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$o = $this->FetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}

		return $this->fields[$this->bind[strtoupper($colname)]];
	}


	function _seek($row)
	{
		return false;
	}

	function _fetch()
	{
		$this->fields = @oci_fetch_array($this->_queryID,$this->fetchMode);
		$this->_updatefields();

		return $this->fields;
	}

	/**
	 * close() only needs to be called if you are worried about using too much
	 * memory while your script is running. All associated result memory for the
	 * specified result identifier will automatically be freed.
	 */
	function _close()
	{
		if ($this->connection->_stmt === $this->_queryID) {
			$this->connection->_stmt = false;
		}
		if (!empty($this->_refcursor)) {
			oci_free_cursor($this->_refcursor);
			$this->_refcursor = false;
		}
		if (is_resource($this->_queryID))
		   @oci_free_statement($this->_queryID);
		$this->_queryID = false;
	}

	/**
	 * not the fastest implementation - quick and dirty - jlim
	 * for best performance, use the actual $rs->MetaType().
	 *
	 * @param	mixed	$t
	 * @param	int		$len		[optional] Length of blobsize
	 * @param	bool	$fieldobj	[optional][discarded]
	 * @return	str					The metatype of the field
	 */
	function MetaType($t, $len=-1, $fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}

		switch (strtoupper($t)) {
		case 'VARCHAR':
		case 'VARCHAR2':
		case 'CHAR':
		case 'VARBINARY':
		case 'BINARY':
		case 'NCHAR':
		case 'NVARCHAR':
		case 'NVARCHAR2':
			if ($len <= $this->blobSize) {
				return 'C';
			}

		case 'NCLOB':
		case 'LONG':
		case 'LONG VARCHAR':
		case 'CLOB':
		return 'X';

		case 'LONG RAW':
		case 'LONG VARBINARY':
		case 'BLOB':
			return 'B';

		case 'DATE':
			return  ($this->connection->datetime) ? 'T' : 'D';


		case 'TIMESTAMP': return 'T';

		case 'INT':
		case 'SMALLINT':
		case 'INTEGER':
			return 'I';

		default:
			return ADODB_DEFAULT_METATYPE;
		}
	}
}

class ADORecordSet_ext_oci8 extends oci8 {

	function MoveNext()
	{
		return adodb_movenext($this);
	}
}

