<?php
/*
@version   v5.21.0-dev  ??-???-2016
@copyright (c) 2000-2013 John Lim. All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
  Set tabs to 4 for best viewing.

  Latest version is available at http://adodb.sourceforge.net

  Informix port by Mitchell T. Young (mitch@youngfamily.org)

  Further mods by "Samuel CARRIERE" <samuel_carriere@hotmail.com>

*/

namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;

/*--------------------------------------------------------------------------------------
	 Class Name: Recordset
--------------------------------------------------------------------------------------*/

class Informix72 extends ADORecordSet {

	var $databaseType = "informix72";
	var $canSeek = true;
	var $_fieldprops = false;

	function __construct($id,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		$this->fetchMode = $mode;
		return parent::__construct($id);
	}



	/*	Returns: an object containing field information.
		Get column information in the Recordset object. fetchField() can be used in order to obtain information about
		fields in a certain query result. If the field offset isn't specified, the next field that wasn't yet retrieved by
		fetchField() is retrieved.	*/
	function FetchField($fieldOffset = -1)
	{
		if (empty($this->_fieldprops)) {
			$fp = ifx_fieldproperties($this->_queryID);
			foreach($fp as $k => $v) {
				$o = new ADOFieldObject;
				$o->name = $k;
				$arr = explode(';',$v); //"SQLTYPE;length;precision;scale;ISNULLABLE"
				$o->type = $arr[0];
				$o->max_length = $arr[1];
				$this->_fieldprops[] = $o;
				$o->not_null = $arr[4]=="N";
			}
		}
		$ret = $this->_fieldprops[$fieldOffset];
		return $ret;
	}

	function _initrs()
	{
		$this->_numOfRows = -1; // ifx_affected_rows not reliable, only returns estimate -- ($ADODB_COUNTRECS)? ifx_affected_rows($this->_queryID):-1;
		$this->_numOfFields = ifx_num_fields($this->_queryID);
	}

	function _seek($row)
	{
		return @ifx_fetch_row($this->_queryID, (int) $row);
	}

   function MoveLast()
   {
	  $this->fields = @ifx_fetch_row($this->_queryID, "LAST");
	  if ($this->fields) $this->EOF = false;
	  $this->_currentRow = -1;

	  if ($this->fetchMode == ADODB_FETCH_NUM) {
		 foreach($this->fields as $v) {
			$arr[] = $v;
		 }
		 $this->fields = $arr;
	  }

	  return true;
   }

   function MoveFirst()
	{
	  $this->fields = @ifx_fetch_row($this->_queryID, "FIRST");
	  if ($this->fields) $this->EOF = false;
	  $this->_currentRow = 0;

	  if ($this->fetchMode == ADODB_FETCH_NUM) {
		 foreach($this->fields as $v) {
			$arr[] = $v;
		 }
		 $this->fields = $arr;
	  }

	  return true;
   }

   function _fetch($ignore_fields=false)
   {

		$this->fields = @ifx_fetch_row($this->_queryID);

		if (!is_array($this->fields)) return false;

		if ($this->fetchMode == ADODB_FETCH_NUM) {
			foreach($this->fields as $v) {
				$arr[] = $v;
			}
			$this->fields = $arr;
		}
		return true;
	}

	/*	close() only needs to be called if you are worried about using too much memory while your script
		is running. All associated result memory for the specified result identifier will automatically be freed.	*/
	function _close()
	{
		if($this->_queryID) {
			return ifx_free_result($this->_queryID);
		}
		return true;
	}

}

