<?php
/*
@version   v5.21.0-dev  ??-???-2016
@copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
  Set tabs to 8.

  This driver only supports the original MySQL driver in transactional mode. It
  is deprected in PHP version 5.5 and removed in PHP version 7. It is deprecated
  as of ADOdb version 5.20.0. Use the mysqli driver instead, which supports both
  transactional and non-transactional updates

  Requires mysql client. Works on Windows and Unix.
*/

namespace ADOdb\drivers\RecordSets;

class mysqlt extends mysql {
	var $databaseType = "mysqlt";

	function __construct($queryID,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}

		switch ($mode)
		{
		case ADODB_FETCH_NUM: $this->fetchMode = MYSQL_NUM; break;
		case ADODB_FETCH_ASSOC:$this->fetchMode = MYSQL_ASSOC; break;

		case ADODB_FETCH_DEFAULT:
		case ADODB_FETCH_BOTH:
		default: $this->fetchMode = MYSQL_BOTH; break;
		}

		$this->adodbFetchMode = $mode;
		parent::__construct($queryID);
	}

	function MoveNext()
	{
		if (@$this->fields = mysql_fetch_array($this->_queryID,$this->fetchMode)) {
			$this->_currentRow += 1;
			return true;
		}
		if (!$this->EOF) {
			$this->_currentRow += 1;
			$this->EOF = true;
		}
		return false;
	}
}

class ADORecordSet_ext_mysqlt extends mysqlt {

	function MoveNext()
	{
		return adodb_movenext($this);
	}
}

