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

  firebird data driver. Requires firebird client. Works on Windows and Unix.

*/
namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;

/*--------------------------------------------------------------------------------------
		 Class Name: Recordset
--------------------------------------------------------------------------------------*/

class Firebird extends ADORecordSet
{

	var $databaseType = "firebird";
	var $bind=false;
	var $_cacheType;

	function __construct($id,$mode=false)
	{
	global $ADODB_FETCH_MODE;

			$this->fetchMode = ($mode === false) ? $ADODB_FETCH_MODE : $mode;
			parent::__construct($id);
	}

	/**
	 * Get column information in the Recordset object.
	 * fetchField() can be used in order to obtain information about fields in
	 * a certain query result. If the field offset isn't specified, the next
	 * field that wasn't yet retrieved by fetchField() is retrieved.
	 * @return object containing field information.
	*/
	function FetchField($fieldOffset = -1)
	{
			$fld = new ADOFieldObject;
			 $ibf = fbird_field_info($this->_queryID,$fieldOffset);

			$name = empty($ibf['alias']) ? $ibf['name'] : $ibf['alias'];

			switch (ADODB_ASSOC_CASE) {
				case ADODB_ASSOC_CASE_UPPER:
					$fld->name = strtoupper($name);
					break;
				case ADODB_ASSOC_CASE_LOWER:
					$fld->name = strtolower($name);
					break;
				case ADODB_ASSOC_CASE_NATIVE:
				default:
					$fld->name = $name;
					break;
			}

			$fld->type = $ibf['type'];
			$fld->max_length = $ibf['length'];

			/*       This needs to be populated from the metadata */
			$fld->not_null = false;
			$fld->has_default = false;
			$fld->default_value = 'null';
			return $fld;
	}

	function _initrs()
	{
		$this->_numOfRows = -1;
		$this->_numOfFields = @fbird_num_fields($this->_queryID);

		// cache types for blob decode check
		for ($i=0, $max = $this->_numOfFields; $i < $max; $i++) {
			$f1 = $this->FetchField($i);
			$this->_cacheType[] = $f1->type;
		}
	}

	function _seek($row)
	{
		return false;
	}

	function _fetch()
	{
		$f = @fbird_fetch_row($this->_queryID);
		if ($f === false) {
			$this->fields = false;
			return false;
		}
		// OPN stuff start - optimized
		// fix missing nulls and decode blobs automatically

		global $ADODB_ANSI_PADDING_OFF;
		//$ADODB_ANSI_PADDING_OFF=1;
		$rtrim = !empty($ADODB_ANSI_PADDING_OFF);

		for ($i=0, $max = $this->_numOfFields; $i < $max; $i++) {
			if ($this->_cacheType[$i]=="BLOB") {
				if (isset($f[$i])) {
					$f[$i] = $this->connection->_BlobDecode($f[$i]);
				} else {
					$f[$i] = null;
				}
			} else {
				if (!isset($f[$i])) {
					$f[$i] = null;
				} else if ($rtrim && is_string($f[$i])) {
					$f[$i] = rtrim($f[$i]);
				}
			}
		}
		// OPN stuff end

		$this->fields = $f;
		if ($this->fetchMode == ADODB_FETCH_ASSOC) {
			$this->fields = $this->GetRowAssoc();
		} else if ($this->fetchMode == ADODB_FETCH_BOTH) {
			$this->fields = array_merge($this->fields,$this->GetRowAssoc());
		}
		return true;
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


	function _close()
	{
			return @fbird_free_result($this->_queryID);
	}

	function MetaType($t,$len=-1,$fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}
		switch (strtoupper($t)) {
		case 'CHAR':
			return 'C';

		case 'TEXT':
		case 'VARCHAR':
		case 'VARYING':
		if ($len <= $this->blobSize) return 'C';
			return 'X';
		case 'BLOB':
			return 'B';

		case 'TIMESTAMP':
		case 'DATE': return 'D';
		case 'TIME': return 'T';
				//case 'T': return 'T';

				//case 'L': return 'L';
		case 'INT':
		case 'SHORT':
		case 'INTEGER': return 'I';
		default: return ADODB_DEFAULT_METATYPE;
		}
	}
}

