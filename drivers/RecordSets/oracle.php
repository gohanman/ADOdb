<?php
/*
@version   v5.21.0-dev  ??-???-2016
@copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.

  Latest version is available at http://adodb.sourceforge.net

  Oracle data driver. Requires Oracle client. Works on Windows and Unix and Oracle 7.

  If you are using Oracle 8 or later, use the oci8 driver which is much better and more reliable.
*/

namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;

/*--------------------------------------------------------------------------------------
		 Class Name: Recordset
--------------------------------------------------------------------------------------*/

class oracle extends ADORecordSet {

	var $databaseType = "oracle";
	var $bind = false;

	function __construct($queryID,$mode=false)
	{

		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		$this->fetchMode = $mode;

		$this->_queryID = $queryID;

		$this->_inited = true;
		$this->fields = array();
		if ($queryID) {
			$this->_currentRow = 0;
			$this->EOF = !$this->_fetch();
			@$this->_initrs();
		} else {
			$this->_numOfRows = 0;
			$this->_numOfFields = 0;
			$this->EOF = true;
		}

		return $this->_queryID;
	}



	   /*		Returns: an object containing field information.
			   Get column information in the Recordset object. fetchField() can be used in order to obtain information about
			   fields in a certain query result. If the field offset isn't specified, the next field that wasn't yet retrieved by
			   fetchField() is retrieved.		*/

	   function FetchField($fieldOffset = -1)
	   {
			$fld = new ADOFieldObject;
			$fld->name = ora_columnname($this->_queryID, $fieldOffset);
			$fld->type = ora_columntype($this->_queryID, $fieldOffset);
			$fld->max_length = ora_columnsize($this->_queryID, $fieldOffset);
			return $fld;
	   }

	/* Use associative array to get fields array */
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

   function _initrs()
   {
		   $this->_numOfRows = -1;
		   $this->_numOfFields = @ora_numcols($this->_queryID);
   }


   function _seek($row)
   {
		   return false;
   }

   function _fetch($ignore_fields=false) {
// should remove call by reference, but ora_fetch_into requires it in 4.0.3pl1
		if ($this->fetchMode & ADODB_FETCH_ASSOC)
			return @ora_fetch_into($this->_queryID,$this->fields,ORA_FETCHINTO_NULLS|ORA_FETCHINTO_ASSOC);
   		else
			return @ora_fetch_into($this->_queryID,$this->fields,ORA_FETCHINTO_NULLS);
   }

   /*		close() only needs to be called if you are worried about using too much memory while your script
		   is running. All associated result memory for the specified result identifier will automatically be freed.		*/

   function _close()
{
		   return @ora_close($this->_queryID);
   }

	function MetaType($t, $len = -1, $fieldobj = false)
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
				if ($len <= $this->blobSize) return 'C';
		case 'LONG':
		case 'LONG VARCHAR':
		case 'CLOB':
		return 'X';
		case 'LONG RAW':
		case 'LONG VARBINARY':
		case 'BLOB':
				return 'B';

		case 'DATE': return 'D';

		//case 'T': return 'T';

		case 'BIT': return 'L';
		case 'INT':
		case 'SMALLINT':
		case 'INTEGER': return 'I';
		default: return 'N';
		}
	}
}

