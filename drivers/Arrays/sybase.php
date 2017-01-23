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

namespace ADOdb\drivers\Arrays;
use \ADORecordSet_array;

global $ADODB_sybase_mths;
$ADODB_sybase_mths = array(
	'JAN'=>1,'FEB'=>2,'MAR'=>3,'APR'=>4,'MAY'=>5,'JUN'=>6,
	'JUL'=>7,'AUG'=>8,'SEP'=>9,'OCT'=>10,'NOV'=>11,'DEC'=>12);

class sybase extends ADORecordSet_array {

	// sybase/mssql uses a default date like Dec 30 2000 12:00AM
	static function UnixDate($v)
	{
	global $ADODB_sybase_mths;

		//Dec 30 2000 12:00AM
		if (!preg_match( "/([A-Za-z]{3})[-/\. ]+([0-9]{1,2})[-/\. ]+([0-9]{4})/"
			,$v, $rr)) return parent::UnixDate($v);

		if ($rr[3] <= TIMESTAMP_FIRST_YEAR) return 0;

		$themth = substr(strtoupper($rr[1]),0,3);
		$themth = $ADODB_sybase_mths[$themth];
		if ($themth <= 0) return false;
		// h-m-s-MM-DD-YY
		return  adodb_mktime(0,0,0,$themth,$rr[2],$rr[3]);
	}

	static function UnixTimeStamp($v)
	{
	global $ADODB_sybase_mths;
		//11.02.2001 Toni Tunkkari toni.tunkkari@finebyte.com
		//Changed [0-9] to [0-9 ] in day conversion
		if (!preg_match( "/([A-Za-z]{3})[-/\. ]([0-9 ]{1,2})[-/\. ]([0-9]{4}) +([0-9]{1,2}):([0-9]{1,2}) *([apAP]{0,1})/"
			,$v, $rr)) return parent::UnixTimeStamp($v);
		if ($rr[3] <= TIMESTAMP_FIRST_YEAR) return 0;

		$themth = substr(strtoupper($rr[1]),0,3);
		$themth = $ADODB_sybase_mths[$themth];
		if ($themth <= 0) return false;

		switch (strtoupper($rr[6])) {
		case 'P':
			if ($rr[4]<12) $rr[4] += 12;
			break;
		case 'A':
			if ($rr[4]==12) $rr[4] = 0;
			break;
		default:
			break;
		}
		// h-m-s-MM-DD-YY
		return  adodb_mktime($rr[4],$rr[5],0,$themth,$rr[2],$rr[3]);
	}
}

