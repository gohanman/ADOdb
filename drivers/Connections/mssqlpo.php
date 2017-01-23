<?php
/**
* @version   v5.21.0-dev  ??-???-2016
* @copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
* @copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
* Released under both BSD license and Lesser GPL library license.
* Whenever there is any discrepancy between the two licenses,
* the BSD license will take precedence.
*
* Set tabs to 4 for best viewing.
*
* Latest version is available at http://php.weblogs.com
*
*  Portable MSSQL Driver that supports || instead of +
*
*/

namespace ADOdb\drivers\Connections;
use \ADOConnection;

// security - hide paths
if (!defined('ADODB_DIR')) die();


/*
	The big difference between mssqlpo and it's parent mssql is that mssqlpo supports
	the more standard || string concatenation operator.
*/

class mssqlpo extends mssql {
	var $databaseType = "mssqlpo";
	var $concat_operator = '||';

	function PrepareSP($sql, $param = true)
	{
		if (!$this->_has_mssql_init) {
			ADOConnection::outp( "PrepareSP: mssql_init only available since PHP 4.1.0");
			return $sql;
		}
		if (is_string($sql)) $sql = str_replace('||','+',$sql);
		$stmt = mssql_init($sql,$this->_connectionID);
		if (!$stmt)  return $sql;
		return array($sql,$stmt);
	}

	function _query($sql,$inputarr=false)
	{
		if (is_string($sql)) $sql = str_replace('||','+',$sql);
		return mssql::_query($sql,$inputarr);
	}
}

