<?php
/*
@version   v5.21.0-dev  ??-???-2016
@copyright (c) 2000-2013 John Lim. All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.

  Latest version is available at http://adodb.sourceforge.net

  Portable version of oci8 driver, to make it more similar to other database drivers.
  The main differences are

   1. that the OCI_ASSOC names are in lowercase instead of uppercase.
   2. bind variables are mapped using ? instead of :<bindvar>

   Should some emulation of RecordCount() be implemented?

*/

namespace ADOdb\drivers\Connections;
use \ADOConnection;

// security - hide paths
if (!defined('ADODB_DIR')) die();

class oci8po extends oci8 {
	var $databaseType = 'oci8po';
	var $dataProvider = 'oci8';
	var $metaColumnsSQL = "select lower(cname),coltype,width, SCALE, PRECISION, NULLS, DEFAULTVAL from col where tname='%s' order by colno"; //changed by smondino@users.sourceforge. net
	var $metaTablesSQL = "select lower(table_name),table_type from cat where table_type in ('TABLE','VIEW')";

	function __construct()
	{
		$this->_hasOCIFetchStatement = ADODB_PHPVER >= 0x4200;
	}

	function Param($name,$type='C')
	{
		return '?';
	}

	function Prepare($sql,$cursor=false)
	{
		$sqlarr = explode('?',$sql);
		$sql = $sqlarr[0];
		for ($i = 1, $max = sizeof($sqlarr); $i < $max; $i++) {
			$sql .=  ':'.($i-1) . $sqlarr[$i];
		}
		return oci8::Prepare($sql,$cursor);
	}

	function Execute($sql,$inputarr=false)
	{
		return \ADOConnection::Execute($sql,$inputarr);
	}

	/**
	 * The optimizations performed by oci8::SelectLimit() are not
	 * compatible with the oci8po driver, so we rely on the slower method
	 * from the base class.
	 * We can't properly handle prepared statements either due to preprocessing
	 * of query parameters, so we treat them as regular SQL statements.
	 */
	function SelectLimit($sql, $nrows=-1, $offset=-1, $inputarr=false, $secs2cache=0)
	{
		if(is_array($sql)) {
//			$sql = $sql[0];
		}
		return ADOConnection::SelectLimit($sql, $nrows, $offset, $inputarr, $secs2cache);
	}

	// emulate handling of parameters ? ?, replacing with :bind0 :bind1
	function _query($sql,$inputarr=false)
	{
		if (is_array($inputarr)) {
			$i = 0;
			if (is_array($sql)) {
				foreach($inputarr as $v) {
					$arr['bind'.$i++] = $v;
				}
			} else {
				// Need to identify if the ? is inside a quoted string, and if
				// so not use it as a bind variable
				preg_match_all('/".*\??"|\'.*\?.*?\'/', $sql, $matches);
				foreach($matches[0] as $qmMatch){
					$qmReplace = str_replace('?', '-QUESTIONMARK-', $qmMatch);
					$sql = str_replace($qmMatch, $qmReplace, $sql);
				}

				// Replace parameters if any were found
				$sqlarr = explode('?',$sql);
				if(count($sqlarr) > 1) {
					$sql = $sqlarr[0];

					foreach ($inputarr as $k => $v) {
						$sql .= ":$k" . $sqlarr[++$i];
					}
				}

				$sql = str_replace('-QUESTIONMARK-', '?', $sql);
			}
		}
		return oci8::_query($sql,$inputarr);
	}
}

