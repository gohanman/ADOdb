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

namespace ADOdb\drivers\Connections;
use \ADOConnection;

 // security - hide paths
if (!defined('ADODB_DIR')) die();

class sybase extends ADOConnection {
	var $databaseType = "sybase";
	var $dataProvider = 'sybase';
	var $replaceQuote = "''"; // string to use to replace quotes
	var $fmtDate = "'Y-m-d'";
	var $fmtTimeStamp = "'Y-m-d H:i:s'";
	var $hasInsertID = true;
	var $hasAffectedRows = true;
  	var $metaTablesSQL="select name from sysobjects where type='U' or type='V'";
	// see http://sybooks.sybase.com/onlinebooks/group-aw/awg0800e/dbrfen8/@ebt-link;pt=5981;uf=0?target=0;window=new;showtoc=true;book=dbrfen8
	var $metaColumnsSQL = "SELECT c.column_name, c.column_type, c.width FROM syscolumn c, systable t WHERE t.table_name='%s' AND c.table_id=t.table_id AND t.table_type='BASE'";
	/*
	"select c.name,t.name,c.length from
	syscolumns c join systypes t on t.xusertype=c.xusertype join sysobjects o on o.id=c.id
	where o.name='%s'";
	*/
	var $concat_operator = '+';
	var $arrayClass = 'ADOdb\\drivers\\Arrays\\sybase';
	var $sysDate = 'GetDate()';
	var $leftOuter = '*=';
	var $rightOuter = '=*';

	var $port;

	// might require begintrans -- committrans
	function _insertid()
	{
		return $this->GetOne('select @@identity');
	}
	  // might require begintrans -- committrans
	function _affectedrows()
	{
		return $this->GetOne('select @@rowcount');
	}


	function BeginTrans()
	{

		if ($this->transOff) return true;
		$this->transCnt += 1;

		$this->Execute('BEGIN TRAN');
		return true;
	}

	function CommitTrans($ok=true)
	{
		if ($this->transOff) return true;

		if (!$ok) return $this->RollbackTrans();

		$this->transCnt -= 1;
		$this->Execute('COMMIT TRAN');
		return true;
	}

	function RollbackTrans()
	{
		if ($this->transOff) return true;
		$this->transCnt -= 1;
		$this->Execute('ROLLBACK TRAN');
		return true;
	}

	// http://www.isug.com/Sybase_FAQ/ASE/section6.1.html#6.1.4
	function RowLock($tables,$where,$col='top 1 null as ignore')
	{
		if (!$this->_hastrans) $this->BeginTrans();
		$tables = str_replace(',',' HOLDLOCK,',$tables);
		return $this->GetOne("select $col from $tables HOLDLOCK where $where");

	}

	function SelectDB($dbName)
	{
		$this->database = $dbName;
		$this->databaseName = $dbName; # obsolete, retained for compat with older adodb versions
		if ($this->_connectionID) {
			return @sybase_select_db($dbName);
		}
		else return false;
	}

	/*	Returns: the last error message from previous database operation
		Note: This function is NOT available for Microsoft SQL Server.	*/


	function ErrorMsg()
	{
		if ($this->_logsql) return $this->_errorMsg;
		if (function_exists('sybase_get_last_message'))
			$this->_errorMsg = sybase_get_last_message();
		else
			$this->_errorMsg = isset($php_errormsg) ? $php_errormsg : 'SYBASE error messages not supported on this platform';
		return $this->_errorMsg;
	}

	// returns true or false
	function _connect($argHostname, $argUsername, $argPassword, $argDatabasename)
	{
		if (!function_exists('sybase_connect')) return null;

		// Sybase connection on custom port
		if ($this->port) {
			$argHostname .= ':' . $this->port;
		}

		if ($this->charSet) {
			$this->_connectionID = sybase_connect($argHostname,$argUsername,$argPassword, $this->charSet);
		} else {
			$this->_connectionID = sybase_connect($argHostname,$argUsername,$argPassword);
		}

		if ($this->_connectionID === false) return false;
		if ($argDatabasename) return $this->SelectDB($argDatabasename);
		return true;
	}

	// returns true or false
	function _pconnect($argHostname, $argUsername, $argPassword, $argDatabasename)
	{
		if (!function_exists('sybase_connect')) return null;

		// Sybase connection on custom port
		if ($this->port) {
			$argHostname .= ':' . $this->port;
		}

		if ($this->charSet) {
			$this->_connectionID = sybase_pconnect($argHostname,$argUsername,$argPassword, $this->charSet);
		} else {
			$this->_connectionID = sybase_pconnect($argHostname,$argUsername,$argPassword);
		}

		if ($this->_connectionID === false) return false;
		if ($argDatabasename) return $this->SelectDB($argDatabasename);
		return true;
	}

	// returns query ID if successful, otherwise false
	function _query($sql,$inputarr=false)
	{
	global $ADODB_COUNTRECS;

		if ($ADODB_COUNTRECS == false && ADODB_PHPVER >= 0x4300)
			return sybase_unbuffered_query($sql,$this->_connectionID);
		else
			return sybase_query($sql,$this->_connectionID);
	}

	// See http://www.isug.com/Sybase_FAQ/ASE/section6.2.html#6.2.12
	function SelectLimit($sql,$nrows=-1,$offset=-1,$inputarr=false,$secs2cache=0)
	{
		if ($secs2cache > 0) {// we do not cache rowcount, so we have to load entire recordset
			$rs = ADOConnection::SelectLimit($sql,$nrows,$offset,$inputarr,$secs2cache);
			return $rs;
		}

		$nrows = (integer) $nrows;
		$offset = (integer) $offset;

		$cnt = ($nrows >= 0) ? $nrows : 999999999;
		if ($offset > 0 && $cnt) $cnt += $offset;

		$this->Execute("set rowcount $cnt");
		$rs = ADOConnection::SelectLimit($sql,$nrows,$offset,$inputarr,0);
		$this->Execute("set rowcount 0");

		return $rs;
	}

	// returns true or false
	function _close()
	{
		return @sybase_close($this->_connectionID);
	}

	static function UnixDate($v)
	{
		return ADORecordSet_array_sybase::UnixDate($v);
	}

	static function UnixTimeStamp($v)
	{
		return ADORecordSet_array_sybase::UnixTimeStamp($v);
	}



	# Added 2003-10-05 by Chris Phillipson
	# Used ASA SQL Reference Manual -- http://sybooks.sybase.com/onlinebooks/group-aw/awg0800e/dbrfen8/@ebt-link;pt=16756?target=%25N%15_12018_START_RESTART_N%25
	# to convert similar Microsoft SQL*Server (mssql) API into Sybase compatible version
	// Format date column in sql string given an input format that understands Y M D
	function SQLDate($fmt, $col=false)
	{
		if (!$col) $col = $this->sysTimeStamp;
		$s = '';

		$len = strlen($fmt);
		for ($i=0; $i < $len; $i++) {
			if ($s) $s .= '+';
			$ch = $fmt[$i];
			switch($ch) {
			case 'Y':
			case 'y':
				$s .= "datename(yy,$col)";
				break;
			case 'M':
				$s .= "convert(char(3),$col,0)";
				break;
			case 'm':
				$s .= "str_replace(str(month($col),2),' ','0')";
				break;
			case 'Q':
			case 'q':
				$s .= "datename(qq,$col)";
				break;
			case 'D':
			case 'd':
				$s .= "str_replace(str(datepart(dd,$col),2),' ','0')";
				break;
			case 'h':
				$s .= "substring(convert(char(14),$col,0),13,2)";
				break;

			case 'H':
				$s .= "str_replace(str(datepart(hh,$col),2),' ','0')";
				break;

			case 'i':
				$s .= "str_replace(str(datepart(mi,$col),2),' ','0')";
				break;
			case 's':
				$s .= "str_replace(str(datepart(ss,$col),2),' ','0')";
				break;
			case 'a':
			case 'A':
				$s .= "substring(convert(char(19),$col,0),18,2)";
				break;

			default:
				if ($ch == '\\') {
					$i++;
					$ch = substr($fmt,$i,1);
				}
				$s .= $this->qstr($ch);
				break;
			}
		}
		return $s;
	}

	# Added 2003-10-07 by Chris Phillipson
	# Used ASA SQL Reference Manual -- http://sybooks.sybase.com/onlinebooks/group-aw/awg0800e/dbrfen8/@ebt-link;pt=5981;uf=0?target=0;window=new;showtoc=true;book=dbrfen8
	# to convert similar Microsoft SQL*Server (mssql) API into Sybase compatible version
	function MetaPrimaryKeys($table, $owner = false)
	{
		$sql = "SELECT c.column_name " .
			   "FROM syscolumn c, systable t " .
			   "WHERE t.table_name='$table' AND c.table_id=t.table_id " .
			   "AND t.table_type='BASE' " .
			   "AND c.pkey = 'Y' " .
			   "ORDER BY c.column_id";

		$a = $this->GetCol($sql);
		if ($a && sizeof($a)>0) return $a;
		return false;
	}
}


