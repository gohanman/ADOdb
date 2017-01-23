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

  Native mssql driver. Requires mssql client. Works on Windows.
  To configure for Unix, see
   	http://phpbuilder.com/columns/alberto20000919.php3

*/

namespace ADOdb\drivers\RecordSets;
use ADOdb\drivers\Arrays\mssql as MssqlArray;
use \ADORecordSet;

/*--------------------------------------------------------------------------------------
	Class Name: Recordset
--------------------------------------------------------------------------------------*/

class mssql extends ADORecordSet {

	var $databaseType = "mssql";
	var $canSeek = true;
	var $hasFetchAssoc; // see http://phplens.com/lens/lensforum/msgs.php?id=6083
	// _mths works only in non-localised system

	function __construct($id,$mode=false)
	{
		// freedts check...
		$this->hasFetchAssoc = function_exists('mssql_fetch_assoc');

		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;

		}
		$this->fetchMode = $mode;
		return parent::__construct($id,$mode);
	}


	function _initrs()
	{
	GLOBAL $ADODB_COUNTRECS;
		$this->_numOfRows = ($ADODB_COUNTRECS)? @mssql_num_rows($this->_queryID):-1;
		$this->_numOfFields = @mssql_num_fields($this->_queryID);
	}


	//Contributed by "Sven Axelsson" <sven.axelsson@bokochwebb.se>
	// get next resultset - requires PHP 4.0.5 or later
	function NextRecordSet()
	{
		if (!mssql_next_result($this->_queryID)) return false;
		$this->_inited = false;
		$this->bind = false;
		$this->_currentRow = -1;
		$this->Init();
		return true;
	}

	/* Use associative array to get fields array */
	function Fields($colname)
	{
		if ($this->fetchMode != ADODB_FETCH_NUM) return $this->fields[$colname];
		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$o = $this->FetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}

		return $this->fields[$this->bind[strtoupper($colname)]];
	}

	/*	Returns: an object containing field information.
		Get column information in the Recordset object. fetchField() can be used in order to obtain information about
		fields in a certain query result. If the field offset isn't specified, the next field that wasn't yet retrieved by
		fetchField() is retrieved.	*/

	function FetchField($fieldOffset = -1)
	{
		if ($fieldOffset != -1) {
			$f = @mssql_fetch_field($this->_queryID, $fieldOffset);
		}
		else if ($fieldOffset == -1) {	/*	The $fieldOffset argument is not provided thus its -1 	*/
			$f = @mssql_fetch_field($this->_queryID);
		}
		$false = false;
		if (empty($f)) return $false;
		return $f;
	}

	function _seek($row)
	{
		return @mssql_data_seek($this->_queryID, $row);
	}

	// speedup
	function MoveNext()
	{
		if ($this->EOF) return false;

		$this->_currentRow++;

		if ($this->fetchMode & ADODB_FETCH_ASSOC) {
			if ($this->fetchMode & ADODB_FETCH_NUM) {
				//ADODB_FETCH_BOTH mode
				$this->fields = @mssql_fetch_array($this->_queryID);
			}
			else {
				if ($this->hasFetchAssoc) {// only for PHP 4.2.0 or later
					$this->fields = @mssql_fetch_assoc($this->_queryID);
				} else {
					$flds = @mssql_fetch_array($this->_queryID);
					if (is_array($flds)) {
						$fassoc = array();
						foreach($flds as $k => $v) {
							if (is_numeric($k)) continue;
							$fassoc[$k] = $v;
						}
						$this->fields = $fassoc;
					} else
						$this->fields = false;
				}
			}

			if (is_array($this->fields)) {
				if (ADODB_ASSOC_CASE == 0) {
					foreach($this->fields as $k=>$v) {
						$kn = strtolower($k);
						if ($kn <> $k) {
							unset($this->fields[$k]);
							$this->fields[$kn] = $v;
						}
					}
				} else if (ADODB_ASSOC_CASE == 1) {
					foreach($this->fields as $k=>$v) {
						$kn = strtoupper($k);
						if ($kn <> $k) {
							unset($this->fields[$k]);
							$this->fields[$kn] = $v;
						}
					}
				}
			}
		} else {
			$this->fields = @mssql_fetch_row($this->_queryID);
		}
		if ($this->fields) return true;
		$this->EOF = true;

		return false;
	}


	// INSERT UPDATE DELETE returns false even if no error occurs in 4.0.4
	// also the date format has been changed from YYYY-mm-dd to dd MMM YYYY in 4.0.4. Idiot!
	function _fetch($ignore_fields=false)
	{
		if ($this->fetchMode & ADODB_FETCH_ASSOC) {
			if ($this->fetchMode & ADODB_FETCH_NUM) {
				//ADODB_FETCH_BOTH mode
				$this->fields = @mssql_fetch_array($this->_queryID);
			} else {
				if ($this->hasFetchAssoc) // only for PHP 4.2.0 or later
					$this->fields = @mssql_fetch_assoc($this->_queryID);
				else {
					$this->fields = @mssql_fetch_array($this->_queryID);
					if (@is_array($$this->fields)) {
						$fassoc = array();
						foreach($$this->fields as $k => $v) {
							if (is_integer($k)) continue;
							$fassoc[$k] = $v;
						}
						$this->fields = $fassoc;
					}
				}
			}

			if (!$this->fields) {
			} else if (ADODB_ASSOC_CASE == 0) {
				foreach($this->fields as $k=>$v) {
					$kn = strtolower($k);
					if ($kn <> $k) {
						unset($this->fields[$k]);
						$this->fields[$kn] = $v;
					}
				}
			} else if (ADODB_ASSOC_CASE == 1) {
				foreach($this->fields as $k=>$v) {
					$kn = strtoupper($k);
					if ($kn <> $k) {
						unset($this->fields[$k]);
						$this->fields[$kn] = $v;
					}
				}
			}
		} else {
			$this->fields = @mssql_fetch_row($this->_queryID);
		}
		return $this->fields;
	}

	/*	close() only needs to be called if you are worried about using too much memory while your script
		is running. All associated result memory for the specified result identifier will automatically be freed.	*/

	function _close()
	{
		if($this->_queryID) {
			$rez = mssql_free_result($this->_queryID);
			$this->_queryID = false;
			return $rez;
		}
		return true;
	}

	// mssql uses a default date like Dec 30 2000 12:00AM
	static function UnixDate($v)
	{
		return MssqlArray::UnixDate($v);
	}

	static function UnixTimeStamp($v)
	{
		return MssqlArray::UnixTimeStamp($v);
	}

	/**
	* Returns the maximum size of a MetaType C field. Because of the
	* database design, SQL Server places no limits on the size of data inserted
	* Although the actual limit is 2^31-1 bytes.
	*
	* @return int
	*/
	function charMax()
	{
		return ADODB_STRINGMAX_NOLIMIT;
	}

	/**
	* Returns the maximum size of a MetaType X field. Because of the
	* database design, SQL Server places no limits on the size of data inserted
	* Although the actual limit is 2^31-1 bytes.
	*
	* @return int
	*/
	function textMax()
	{
		return ADODB_STRINGMAX_NOLIMIT;
	}
}


