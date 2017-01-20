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

namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;
use \Exception;

/*--------------------------------------------------------------------------------------
	 Class Name: Recordset
--------------------------------------------------------------------------------------*/

class ADO5 extends ADORecordSet {

	var $bind = false;
	var $databaseType = "ado";
	var $dataProvider = "ado";
	var $_tarr = false; // caches the types
	var $_flds; // and field objects
	var $canSeek = true;
  	var $hideErrors = true;

	function __construct($id,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		$this->fetchMode = $mode;
		return parent::__construct($id,$mode);
	}


	// returns the field object
	function FetchField($fieldOffset = -1) {
		$off=$fieldOffset+1; // offsets begin at 1

		$o= new ADOFieldObject();
		$rs = $this->_queryID;
		if (!$rs) return false;

		$f = $rs->Fields($fieldOffset);
		$o->name = $f->Name;
		$t = $f->Type;
		$o->type = $this->MetaType($t);
		$o->max_length = $f->DefinedSize;
		$o->ado_type = $t;


		//print "off=$off name=$o->name type=$o->type len=$o->max_length<br>";
		return $o;
	}

	/* Use associative array to get fields array */
	function Fields($colname)
	{
		if ($this->fetchMode & ADODB_FETCH_ASSOC) return $this->fields[$colname];
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
		$rs = $this->_queryID;

		try {
			$this->_numOfRows = $rs->RecordCount;
		} catch (Exception $e) {
			$this->_numOfRows = -1;
		}
		$f = $rs->Fields;
		$this->_numOfFields = $f->Count;
	}


	 // should only be used to move forward as we normally use forward-only cursors
	function _seek($row)
	{
	   $rs = $this->_queryID;
		// absoluteposition doesn't work -- my maths is wrong ?
		//	$rs->AbsolutePosition->$row-2;
		//	return true;
		if ($this->_currentRow > $row) return false;
		@$rs->Move((integer)$row - $this->_currentRow-1); //adBookmarkFirst
		return true;
	}

/*
	OLEDB types

	 enum DBTYPEENUM
	{	DBTYPE_EMPTY	= 0,
	DBTYPE_NULL	= 1,
	DBTYPE_I2	= 2,
	DBTYPE_I4	= 3,
	DBTYPE_R4	= 4,
	DBTYPE_R8	= 5,
	DBTYPE_CY	= 6,
	DBTYPE_DATE	= 7,
	DBTYPE_BSTR	= 8,
	DBTYPE_IDISPATCH	= 9,
	DBTYPE_ERROR	= 10,
	DBTYPE_BOOL	= 11,
	DBTYPE_VARIANT	= 12,
	DBTYPE_IUNKNOWN	= 13,
	DBTYPE_DECIMAL	= 14,
	DBTYPE_UI1	= 17,
	DBTYPE_ARRAY	= 0x2000,
	DBTYPE_BYREF	= 0x4000,
	DBTYPE_I1	= 16,
	DBTYPE_UI2	= 18,
	DBTYPE_UI4	= 19,
	DBTYPE_I8	= 20,
	DBTYPE_UI8	= 21,
	DBTYPE_GUID	= 72,
	DBTYPE_VECTOR	= 0x1000,
	DBTYPE_RESERVED	= 0x8000,
	DBTYPE_BYTES	= 128,
	DBTYPE_STR	= 129,
	DBTYPE_WSTR	= 130,
	DBTYPE_NUMERIC	= 131,
	DBTYPE_UDT	= 132,
	DBTYPE_DBDATE	= 133,
	DBTYPE_DBTIME	= 134,
	DBTYPE_DBTIMESTAMP	= 135

	ADO Types

   	adEmpty	= 0,
	adTinyInt	= 16,
	adSmallInt	= 2,
	adInteger	= 3,
	adBigInt	= 20,
	adUnsignedTinyInt	= 17,
	adUnsignedSmallInt	= 18,
	adUnsignedInt	= 19,
	adUnsignedBigInt	= 21,
	adSingle	= 4,
	adDouble	= 5,
	adCurrency	= 6,
	adDecimal	= 14,
	adNumeric	= 131,
	adBoolean	= 11,
	adError	= 10,
	adUserDefined	= 132,
	adVariant	= 12,
	adIDispatch	= 9,
	adIUnknown	= 13,
	adGUID	= 72,
	adDate	= 7,
	adDBDate	= 133,
	adDBTime	= 134,
	adDBTimeStamp	= 135,
	adBSTR	= 8,
	adChar	= 129,
	adVarChar	= 200,
	adLongVarChar	= 201,
	adWChar	= 130,
	adVarWChar	= 202,
	adLongVarWChar	= 203,
	adBinary	= 128,
	adVarBinary	= 204,
	adLongVarBinary	= 205,
	adChapter	= 136,
	adFileTime	= 64,
	adDBFileTime	= 137,
	adPropVariant	= 138,
	adVarNumeric	= 139
*/
	function MetaType($t,$len=-1,$fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}

		if (!is_numeric($t)) return $t;

		switch ($t) {
		case 0:
		case 12: // variant
		case 8: // bstr
		case 129: //char
		case 130: //wc
		case 200: // varc
		case 202:// varWC
		case 128: // bin
		case 204: // varBin
		case 72: // guid
			if ($len <= $this->blobSize) return 'C';

		case 201:
		case 203:
			return 'X';
		case 128:
		case 204:
		case 205:
			 return 'B';
		case 7:
		case 133: return 'D';

		case 134:
		case 135: return 'T';

		case 11: return 'L';

		case 16://	adTinyInt	= 16,
		case 2://adSmallInt	= 2,
		case 3://adInteger	= 3,
		case 4://adBigInt	= 20,
		case 17://adUnsignedTinyInt	= 17,
		case 18://adUnsignedSmallInt	= 18,
		case 19://adUnsignedInt	= 19,
		case 20://adUnsignedBigInt	= 21,
			return 'I';
		default: return ADODB_DEFAULT_METATYPE;
		}
	}

	// time stamp not supported yet
	function _fetch()
	{
		$rs = $this->_queryID;
		if (!$rs or $rs->EOF) {
			$this->fields = false;
			return false;
		}
		$this->fields = array();

		if (!$this->_tarr) {
			$tarr = array();
			$flds = array();
			for ($i=0,$max = $this->_numOfFields; $i < $max; $i++) {
				$f = $rs->Fields($i);
				$flds[] = $f;
				$tarr[] = $f->Type;
			}
			// bind types and flds only once
			$this->_tarr = $tarr;
			$this->_flds = $flds;
		}
		$t = reset($this->_tarr);
		$f = reset($this->_flds);

		if ($this->hideErrors)  $olde = error_reporting(E_ERROR|E_CORE_ERROR);// sometimes $f->value be null
		for ($i=0,$max = $this->_numOfFields; $i < $max; $i++) {
			//echo "<p>",$t,' ';var_dump($f->value); echo '</p>';
			switch($t) {
			case 135: // timestamp
				if (!strlen((string)$f->value)) $this->fields[] = false;
				else {
					if (!is_numeric($f->value)) # $val = variant_date_to_timestamp($f->value);
						// VT_DATE stores dates as (float) fractional days since 1899/12/30 00:00:00
						$val= (float) variant_cast($f->value,VT_R8)*3600*24-2209161600;
					else
						$val = $f->value;
					$this->fields[] = adodb_date('Y-m-d H:i:s',$val);
				}
				break;
			case 133:// A date value (yyyymmdd)
				if ($val = $f->value) {
					$this->fields[] = substr($val,0,4).'-'.substr($val,4,2).'-'.substr($val,6,2);
				} else
					$this->fields[] = false;
				break;
			case 7: // adDate
				if (!strlen((string)$f->value)) $this->fields[] = false;
				else {
					if (!is_numeric($f->value)) $val = variant_date_to_timestamp($f->value);
					else $val = $f->value;

					if (($val % 86400) == 0) $this->fields[] = adodb_date('Y-m-d',$val);
					else $this->fields[] = adodb_date('Y-m-d H:i:s',$val);
				}
				break;
			case 1: // null
				$this->fields[] = false;
				break;
			case 20:
			case 21: // bigint (64 bit)
    			$this->fields[] = (float) $f->value; // if 64 bit PHP, could use (int)
    			break;
			case 6: // currency is not supported properly;
				ADOConnection::outp( '<b>'.$f->Name.': currency type not supported by PHP</b>');
				$this->fields[] = (float) $f->value;
				break;
			case 11: //BIT;
				$val = "";
				if(is_bool($f->value))	{
					if($f->value==true) $val = 1;
					else $val = 0;
				}
				if(is_null($f->value)) $val = null;

				$this->fields[] = $val;
				break;
			default:
				$this->fields[] = $f->value;
				break;
			}
			//print " $f->value $t, ";
			$f = next($this->_flds);
			$t = next($this->_tarr);
		} // for
		if ($this->hideErrors) error_reporting($olde);
		@$rs->MoveNext(); // @ needed for some versions of PHP!

		if ($this->fetchMode & ADODB_FETCH_ASSOC) {
			$this->fields = $this->GetRowAssoc();
		}
		return true;
	}

		function NextRecordSet()
		{
			$rs = $this->_queryID;
			$this->_queryID = $rs->NextRecordSet();
			//$this->_queryID = $this->_QueryId->NextRecordSet();
			if ($this->_queryID == null) return false;

			$this->_currentRow = -1;
			$this->_currentPage = -1;
			$this->bind = false;
			$this->fields = false;
			$this->_flds = false;
			$this->_tarr = false;

			$this->_inited = false;
			$this->Init();
			return true;
		}

	function _close() {
		$this->_flds = false;
		try {
		@$this->_queryID->Close();// by Pete Dishman (peterd@telephonetics.co.uk)
		} catch (Exception $e) {
		}
		$this->_queryID = false;
	}

}

