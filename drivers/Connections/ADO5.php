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

	Microsoft ADO data driver. Requires ADO. Works only on MS Windows. PHP5 compat version.
*/

namespace ADOdb\drivers\Connections;
use \ADOConnection;

// security - hide paths
if (!defined('ADODB_DIR')) die();

if (!defined('_ADODB_ADO_LAYER')) define("_ADODB_ADO_LAYER", 1 );
/*--------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------*/

class ADO5 extends ADOConnection {
	var $databaseType = "ado";
	var $_bindInputArray = false;
	var $fmtDate = "'Y-m-d'";
	var $fmtTimeStamp = "'Y-m-d, h:i:sA'";
	var $replaceQuote = "''"; // string to use to replace quotes
	var $dataProvider = "ado";
	var $hasAffectedRows = true;
	var $adoParameterType = 201; // 201 = long varchar, 203=long wide varchar, 205 = long varbinary
	var $_affectedRows = false;
	var $_thisTransactions;
	var $_cursor_type = 3; // 3=adOpenStatic,0=adOpenForwardOnly,1=adOpenKeyset,2=adOpenDynamic
	var $_cursor_location = 3; // 2=adUseServer, 3 = adUseClient;
	var $_lock_type = -1;
	var $_execute_option = -1;
	var $poorAffectedRows = true;
	var $charPage;

	function __construct()
	{
		$this->_affectedRows = new VARIANT;
	}

	function ServerInfo()
	{
		if (!empty($this->_connectionID)) $desc = $this->_connectionID->provider;
		return array('description' => $desc, 'version' => '');
	}

	function _affectedrows()
	{
		if (PHP_VERSION >= 5) return $this->_affectedRows;

		return $this->_affectedRows->value;
	}

	// you can also pass a connection string like this:
	//
	// $DB->Connect('USER ID=sa;PASSWORD=pwd;SERVER=mangrove;DATABASE=ai',false,false,'SQLOLEDB');
	function _connect($argHostname, $argUsername, $argPassword,$argDBorProvider, $argProvider= '')
	{
	// two modes
	//	-	if $argProvider is empty, we assume that $argDBorProvider holds provider -- this is for backward compat
	//	- 	if $argProvider is not empty, then $argDBorProvider holds db


		 if ($argProvider) {
		 	$argDatabasename = $argDBorProvider;
		 } else {
		 	$argDatabasename = '';
		 	if ($argDBorProvider) $argProvider = $argDBorProvider;
			else if (stripos($argHostname,'PROVIDER') === false) /* full conn string is not in $argHostname */
				$argProvider = 'MSDASQL';
		}


		try {
		$u = 'UID';
		$p = 'PWD';

		if (!empty($this->charPage))
			$dbc = new COM('ADODB.Connection',null,$this->charPage);
		else
			$dbc = new COM('ADODB.Connection');

		if (! $dbc) return false;

		/* special support if provider is mssql or access */
		if ($argProvider=='mssql') {
			$u = 'User Id';  //User parameter name for OLEDB
			$p = 'Password';
			$argProvider = "SQLOLEDB"; // SQL Server Provider

			// not yet
			//if ($argDatabasename) $argHostname .= ";Initial Catalog=$argDatabasename";

			//use trusted conection for SQL if username not specified
			if (!$argUsername) $argHostname .= ";Trusted_Connection=Yes";
		} else if ($argProvider=='access')
			$argProvider = "Microsoft.Jet.OLEDB.4.0"; // Microsoft Jet Provider

		if ($argProvider) $dbc->Provider = $argProvider;

		if ($argProvider) $argHostname = "PROVIDER=$argProvider;DRIVER={SQL Server};SERVER=$argHostname";


		if ($argDatabasename) $argHostname .= ";DATABASE=$argDatabasename";
		if ($argUsername) $argHostname .= ";$u=$argUsername";
		if ($argPassword)$argHostname .= ";$p=$argPassword";

		if ($this->debug) ADOConnection::outp( "Host=".$argHostname."<BR>\n version=$dbc->version");
		// @ added below for php 4.0.1 and earlier
		@$dbc->Open((string) $argHostname);

		$this->_connectionID = $dbc;

		$dbc->CursorLocation = $this->_cursor_location;
		return  $dbc->State > 0;
		} catch (exception $e) {
			if ($this->debug) echo "<pre>",$argHostname,"\n",$e,"</pre>\n";
		}

		return false;
	}

	// returns true or false
	function _pconnect($argHostname, $argUsername, $argPassword, $argProvider='MSDASQL')
	{
		return $this->_connect($argHostname,$argUsername,$argPassword,$argProvider);
	}

/*
	adSchemaCatalogs	= 1,
	adSchemaCharacterSets	= 2,
	adSchemaCollations	= 3,
	adSchemaColumns	= 4,
	adSchemaCheckConstraints	= 5,
	adSchemaConstraintColumnUsage	= 6,
	adSchemaConstraintTableUsage	= 7,
	adSchemaKeyColumnUsage	= 8,
	adSchemaReferentialContraints	= 9,
	adSchemaTableConstraints	= 10,
	adSchemaColumnsDomainUsage	= 11,
	adSchemaIndexes	= 12,
	adSchemaColumnPrivileges	= 13,
	adSchemaTablePrivileges	= 14,
	adSchemaUsagePrivileges	= 15,
	adSchemaProcedures	= 16,
	adSchemaSchemata	= 17,
	adSchemaSQLLanguages	= 18,
	adSchemaStatistics	= 19,
	adSchemaTables	= 20,
	adSchemaTranslations	= 21,
	adSchemaProviderTypes	= 22,
	adSchemaViews	= 23,
	adSchemaViewColumnUsage	= 24,
	adSchemaViewTableUsage	= 25,
	adSchemaProcedureParameters	= 26,
	adSchemaForeignKeys	= 27,
	adSchemaPrimaryKeys	= 28,
	adSchemaProcedureColumns	= 29,
	adSchemaDBInfoKeywords	= 30,
	adSchemaDBInfoLiterals	= 31,
	adSchemaCubes	= 32,
	adSchemaDimensions	= 33,
	adSchemaHierarchies	= 34,
	adSchemaLevels	= 35,
	adSchemaMeasures	= 36,
	adSchemaProperties	= 37,
	adSchemaMembers	= 38

*/

	function MetaTables($ttype = false, $showSchema = false, $mask = false)
	{
		$arr= array();
		$dbc = $this->_connectionID;

		$adors=@$dbc->OpenSchema(20);//tables
		if ($adors){
			$f = $adors->Fields(2);//table/view name
			$t = $adors->Fields(3);//table type
			while (!$adors->EOF){
				$tt=substr($t->value,0,6);
				if ($tt!='SYSTEM' && $tt !='ACCESS')
					$arr[]=$f->value;
				//print $f->value . ' ' . $t->value.'<br>';
				$adors->MoveNext();
			}
			$adors->Close();
		}

		return $arr;
	}

	function MetaColumns($table, $normalize=true)
	{
		$table = strtoupper($table);
		$arr= array();
		$dbc = $this->_connectionID;

		$adors=@$dbc->OpenSchema(4);//tables

		if ($adors){
			$t = $adors->Fields(2);//table/view name
			while (!$adors->EOF){


				if (strtoupper($t->Value) == $table) {

					$fld = new ADOFieldObject();
					$c = $adors->Fields(3);
					$fld->name = $c->Value;
					$fld->type = 'CHAR'; // cannot discover type in ADO!
					$fld->max_length = -1;
					$arr[strtoupper($fld->name)]=$fld;
				}

				$adors->MoveNext();
			}
			$adors->Close();
		}

		return $arr;
	}

	/* returns queryID or false */
	function _query($sql,$inputarr=false)
	{
		try { // In PHP5, all COM errors are exceptions, so to maintain old behaviour...

		$dbc = $this->_connectionID;

	//	return rs

		$false = false;

		if ($inputarr) {

			if (!empty($this->charPage))
				$oCmd = new COM('ADODB.Command',null,$this->charPage);
			else
				$oCmd = new COM('ADODB.Command');
			$oCmd->ActiveConnection = $dbc;
			$oCmd->CommandText = $sql;
			$oCmd->CommandType = 1;

			while(list(, $val) = each($inputarr)) {
				$type = gettype($val);
				$len=strlen($val);
				if ($type == 'boolean')
					$this->adoParameterType = 11;
				else if ($type == 'integer')
					$this->adoParameterType = 3;
				else if ($type == 'double')
					$this->adoParameterType = 5;
				elseif ($type == 'string')
					$this->adoParameterType = 202;
				else if (($val === null) || (!defined($val)))
					$len=1;
				else
					$this->adoParameterType = 130;

				// name, type, direction 1 = input, len,
        		$p = $oCmd->CreateParameter('name',$this->adoParameterType,1,$len,$val);

				$oCmd->Parameters->Append($p);
			}

			$p = false;
			$rs = $oCmd->Execute();
			$e = $dbc->Errors;
			if ($dbc->Errors->Count > 0) return $false;
			return $rs;
		}

		$rs = @$dbc->Execute($sql,$this->_affectedRows, $this->_execute_option);

		if ($dbc->Errors->Count > 0) return $false;
		if (! $rs) return $false;

		if ($rs->State == 0) {
			$true = true;
			return $true; // 0 = adStateClosed means no records returned
		}
		return $rs;

		} catch (exception $e) {

		}
		return $false;
	}


	function BeginTrans()
	{
		if ($this->transOff) return true;

		if (isset($this->_thisTransactions))
			if (!$this->_thisTransactions) return false;
		else {
			$o = $this->_connectionID->Properties("Transaction DDL");
			$this->_thisTransactions = $o ? true : false;
			if (!$o) return false;
		}
		@$this->_connectionID->BeginTrans();
		$this->transCnt += 1;
		return true;
	}
	function CommitTrans($ok=true)
	{
		if (!$ok) return $this->RollbackTrans();
		if ($this->transOff) return true;

		@$this->_connectionID->CommitTrans();
		if ($this->transCnt) @$this->transCnt -= 1;
		return true;
	}
	function RollbackTrans() {
		if ($this->transOff) return true;
		@$this->_connectionID->RollbackTrans();
		if ($this->transCnt) @$this->transCnt -= 1;
		return true;
	}

	/*	Returns: the last error message from previous database operation	*/

	function ErrorMsg()
	{
		if (!$this->_connectionID) return "No connection established";
		$errmsg = '';

		try {
			$errc = $this->_connectionID->Errors;
			if (!$errc) return "No Errors object found";
			if ($errc->Count == 0) return '';
			$err = $errc->Item($errc->Count-1);
			$errmsg = $err->Description;
		}catch(exception $e) {
		}
		return $errmsg;
	}

	function ErrorNo()
	{
		$errc = $this->_connectionID->Errors;
		if ($errc->Count == 0) return 0;
		$err = $errc->Item($errc->Count-1);
		return $err->NativeError;
	}

	// returns true or false
	function _close()
	{
		if ($this->_connectionID) $this->_connectionID->Close();
		$this->_connectionID = false;
		return true;
	}


}

