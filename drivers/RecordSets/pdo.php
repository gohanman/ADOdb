<?php
/**
	@version   v5.21.0-dev  ??-???-2016
	@copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
	@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community

	Released under both BSD license and Lesser GPL library license.
	Whenever there is any discrepancy between the two licenses,
	the BSD license will take precedence.

	Set tabs to 4 for best viewing.

	Latest version is available at http://adodb.sourceforge.net

	Requires ODBC. Works on Windows and Unix.

	Problems:
		Where is float/decimal type in pdo_param_type
		LOB handling for CLOB/BLOB differs significantly
*/

namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;
use \PDO as NativePDO;

/*--------------------------------------------------------------------------------------
	Class Name: Recordset
--------------------------------------------------------------------------------------*/

class pdo extends ADORecordSet {

	var $bind = false;
	var $databaseType = "pdo";
	var $dataProvider = "pdo";

	function __construct($id,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		$this->adodbFetchMode = $mode;
		switch($mode) {
		case ADODB_FETCH_NUM: $mode = NativePDO::FETCH_NUM; break;
		case ADODB_FETCH_ASSOC:  $mode = NativePDO::FETCH_ASSOC; break;

		case ADODB_FETCH_BOTH:
		default: $mode = NativePDO::FETCH_BOTH; break;
		}
		$this->fetchMode = $mode;

		$this->_queryID = $id;
		parent::__construct($id);
	}


	function Init()
	{
		if ($this->_inited) {
			return;
		}
		$this->_inited = true;
		if ($this->_queryID) {
			@$this->_initrs();
		}
		else {
			$this->_numOfRows = 0;
			$this->_numOfFields = 0;
		}
		if ($this->_numOfRows != 0 && $this->_currentRow == -1) {
			$this->_currentRow = 0;
			if ($this->EOF = ($this->_fetch() === false)) {
				$this->_numOfRows = 0; // _numOfRows could be -1
			}
		} else {
			$this->EOF = true;
		}
	}

	function _initrs()
	{
	global $ADODB_COUNTRECS;

		$this->_numOfRows = ($ADODB_COUNTRECS) ? @$this->_queryID->rowCount() : -1;
		if (!$this->_numOfRows) {
			$this->_numOfRows = -1;
		}
		$this->_numOfFields = $this->_queryID->columnCount();
	}

	// returns the field object
	function FetchField($fieldOffset = -1)
	{
		$off=$fieldOffset+1; // offsets begin at 1

		$o= new ADOFieldObject();
		$arr = @$this->_queryID->getColumnMeta($fieldOffset);
		if (!$arr) {
			$o->name = 'bad getColumnMeta()';
			$o->max_length = -1;
			$o->type = 'VARCHAR';
			$o->precision = 0;
	#		$false = false;
			return $o;
		}
		//adodb_pr($arr);
		$o->name = $arr['name'];
		if (isset($arr['sqlsrv:decl_type']) && $arr['sqlsrv:decl_type'] <> "null") 
		{
		    /*
		    * If the database is SQL server, use the native built-ins
		    */
		    $o->type = $arr['sqlsrv:decl_type'];
		}
		elseif (isset($arr['native_type']) && $arr['native_type'] <> "null") 
		{
		    $o->type = $arr['native_type'];
		}
		else 
		{
		     $o->type = $this->adodb_pdo_type($arr['pdo_type']);
		}
		
		$o->max_length = $arr['len'];
		$o->precision = $arr['precision'];

		switch(ADODB_ASSOC_CASE) {
			case ADODB_ASSOC_CASE_LOWER:
				$o->name = strtolower($o->name);
				break;
			case ADODB_ASSOC_CASE_UPPER:
				$o->name = strtoupper($o->name);
				break;
		}
		return $o;
	}

	function _seek($row)
	{
		return false;
	}

	function _fetch()
	{
		if (!$this->_queryID) {
			return false;
		}

		$this->fields = $this->_queryID->fetch($this->fetchMode);
		return !empty($this->fields);
	}

	function _close()
	{
		$this->_queryID = false;
	}

	function Fields($colname)
	{
		if ($this->adodbFetchMode != ADODB_FETCH_NUM) {
			return @$this->fields[$colname];
		}

		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$o = $this->FetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}
		return $this->fields[$this->bind[strtoupper($colname)]];
	}

    /*
    enum pdo_param_type {
    NativePDO::PARAM_NULL, 0

    /* int as in long (the php native int type).
     * If you mark a column as an int, PDO expects get_col to return
     * a pointer to a long
    NativePDO::PARAM_INT, 1

    /* get_col ptr should point to start of the string buffer
    NativePDO::PARAM_STR, 2

    /* get_col: when len is 0 ptr should point to a php_stream *,
     * otherwise it should behave like a string. Indicate a NULL field
     * value by setting the ptr to NULL
    NativePDO::PARAM_LOB, 3

    /* get_col: will expect the ptr to point to a new PDOStatement object handle,
     * but this isn't wired up yet
    NativePDO::PARAM_STMT, 4 /* hierarchical result set

    /* get_col ptr should point to a zend_bool
    NativePDO::PARAM_BOOL, 5


    /* magic flag to denote a parameter as being input/output
    NativePDO::PARAM_INPUT_OUTPUT = 0x80000000
    };
    */

    function adodb_pdo_type($t)
    {
        switch($t) {
        case 2: return 'VARCHAR';
        case 3: return 'BLOB';
        default: return 'NUMERIC';
        }
    }
}

