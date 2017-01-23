<?php
/*
  @version   v5.21.0-dev  ??-???-2016
  @copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
  @copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence. See License.txt.
  Set tabs to 4 for best viewing.
  Latest version is available at http://adodb.sourceforge.net
*/
// Code contributed by "stefan bogdan" <sbogdan#rsb.ro>

namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;

// security - hide paths
if (!defined('ADODB_DIR')) die();

class odbtp extends ADORecordSet {

	var $databaseType = 'odbtp';
	var $canSeek = true;

	function __construct($queryID,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		$this->fetchMode = $mode;
		parent::__construct($queryID);
	}

	function _initrs()
	{
		$this->_numOfFields = @odbtp_num_fields($this->_queryID);
		if (!($this->_numOfRows = @odbtp_num_rows($this->_queryID)))
			$this->_numOfRows = -1;

		if (!$this->connection->_useUnicodeSQL) return;

		if ($this->connection->odbc_driver == ODB_DRIVER_JET) {
			if (!@odbtp_get_attr(ODB_ATTR_MAPCHARTOWCHAR,
			                     $this->connection->_connectionID))
			{
				for ($f = 0; $f < $this->_numOfFields; $f++) {
					if (@odbtp_field_bindtype($this->_queryID, $f) == ODB_CHAR)
						@odbtp_bind_field($this->_queryID, $f, ODB_WCHAR);
				}
			}
		}
	}

	function FetchField($fieldOffset = 0)
	{
		$off=$fieldOffset; // offsets begin at 0
		$o= new ADOFieldObject();
		$o->name = @odbtp_field_name($this->_queryID,$off);
		$o->type = @odbtp_field_type($this->_queryID,$off);
        $o->max_length = @odbtp_field_length($this->_queryID,$off);
		if (ADODB_ASSOC_CASE == 0) $o->name = strtolower($o->name);
		else if (ADODB_ASSOC_CASE == 1) $o->name = strtoupper($o->name);
		return $o;
	}

	function _seek($row)
	{
		return @odbtp_data_seek($this->_queryID, $row);
	}

	function fields($colname)
	{
		if ($this->fetchMode & ADODB_FETCH_ASSOC) return $this->fields[$colname];

		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$name = @odbtp_field_name( $this->_queryID, $i );
				$this->bind[strtoupper($name)] = $i;
			}
		}
		return $this->fields[$this->bind[strtoupper($colname)]];
	}

	function _fetch_odbtp($type=0)
	{
		switch ($this->fetchMode) {
			case ADODB_FETCH_NUM:
				$this->fields = @odbtp_fetch_row($this->_queryID, $type);
				break;
			case ADODB_FETCH_ASSOC:
				$this->fields = @odbtp_fetch_assoc($this->_queryID, $type);
				break;
            default:
				$this->fields = @odbtp_fetch_array($this->_queryID, $type);
		}
		if ($this->databaseType = 'odbtp_vfp') {
			if ($this->fields)
			foreach($this->fields as $k => $v) {
				if (strncmp($v,'1899-12-30',10) == 0) $this->fields[$k] = '';
			}
		}
		return is_array($this->fields);
	}

	function _fetch()
	{
		return $this->_fetch_odbtp();
	}

	function MoveFirst()
	{
		if (!$this->_fetch_odbtp(ODB_FETCH_FIRST)) return false;
		$this->EOF = false;
		$this->_currentRow = 0;
		return true;
    }

	function MoveLast()
	{
		if (!$this->_fetch_odbtp(ODB_FETCH_LAST)) return false;
		$this->EOF = false;
		$this->_currentRow = $this->_numOfRows - 1;
		return true;
	}

	function NextRecordSet()
	{
		if (!@odbtp_next_result($this->_queryID)) return false;
		$this->_inited = false;
		$this->bind = false;
		$this->_currentRow = -1;
		$this->Init();
		return true;
	}

	function _close()
	{
		return @odbtp_free_query($this->_queryID);
	}
}

class ADORecordSet_odbtp_mssql extends odbtp {

	var $databaseType = 'odbtp_mssql';

}

class ADORecordSet_odbtp_access extends odbtp {

	var $databaseType = 'odbtp_access';

}

class ADORecordSet_odbtp_vfp extends odbtp {

	var $databaseType = 'odbtp_vfp';

}

class ADORecordSet_odbtp_oci8 extends odbtp {

	var $databaseType = 'odbtp_oci8';

}

class ADORecordSet_odbtp_sybase extends odbtp {

	var $databaseType = 'odbtp_sybase';

}

