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

  Sybase driver contributed by Toni (toni.tunkkari@finebyte.com)

  - MSSQL date patch applied.

  Date patch by Toni 15 Feb 2002
*/

namespace ADOdb\drivers\RecordSets;
use ADOdb\drivers\Arrays\sybase as SybaseArray;
use \ADORecordSet;

/*--------------------------------------------------------------------------------------
	 Class Name: Recordset
--------------------------------------------------------------------------------------*/

class sybase extends ADORecordSet {

	var $databaseType = "sybase";
	var $canSeek = true;
	// _mths works only in non-localised system
	var  $_mths = array('JAN'=>1,'FEB'=>2,'MAR'=>3,'APR'=>4,'MAY'=>5,'JUN'=>6,'JUL'=>7,'AUG'=>8,'SEP'=>9,'OCT'=>10,'NOV'=>11,'DEC'=>12);

	function __construct($id,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		if (!$mode) $this->fetchMode = ADODB_FETCH_ASSOC;
		else $this->fetchMode = $mode;
		parent::__construct($id,$mode);
	}

	/*	Returns: an object containing field information.
		Get column information in the Recordset object. fetchField() can be used in order to obtain information about
		fields in a certain query result. If the field offset isn't specified, the next field that wasn't yet retrieved by
		fetchField() is retrieved.	*/
	function FetchField($fieldOffset = -1)
	{
		if ($fieldOffset != -1) {
			$o = @sybase_fetch_field($this->_queryID, $fieldOffset);
		}
		else if ($fieldOffset == -1) {	/*	The $fieldOffset argument is not provided thus its -1 	*/
			$o = @sybase_fetch_field($this->_queryID);
		}
		// older versions of PHP did not support type, only numeric
		if ($o && !isset($o->type)) $o->type = ($o->numeric) ? 'float' : 'varchar';
		return $o;
	}

	function _initrs()
	{
	global $ADODB_COUNTRECS;
		$this->_numOfRows = ($ADODB_COUNTRECS)? @sybase_num_rows($this->_queryID):-1;
		$this->_numOfFields = @sybase_num_fields($this->_queryID);
	}

	function _seek($row)
	{
		return @sybase_data_seek($this->_queryID, $row);
	}

	function _fetch($ignore_fields=false)
	{
		if ($this->fetchMode == ADODB_FETCH_NUM) {
			$this->fields = @sybase_fetch_row($this->_queryID);
		} else if ($this->fetchMode == ADODB_FETCH_ASSOC) {
			$this->fields = @sybase_fetch_assoc($this->_queryID);

			if (is_array($this->fields)) {
				$this->fields = $this->GetRowAssoc();
				return true;
			}
			return false;
		}  else {
			$this->fields = @sybase_fetch_array($this->_queryID);
		}
		if ( is_array($this->fields)) {
			return true;
		}

		return false;
	}

	/*	close() only needs to be called if you are worried about using too much memory while your script
		is running. All associated result memory for the specified result identifier will automatically be freed.	*/
	function _close() {
		return @sybase_free_result($this->_queryID);
	}

	// sybase/mssql uses a default date like Dec 30 2000 12:00AM
	static function UnixDate($v)
	{
		return SybaseArray::UnixDate($v);
	}

	static function UnixTimeStamp($v)
	{
		return SybaseArray::UnixTimeStamp($v);
	}
}

