<?php
/*
@version   v5.21.0-dev  ??-???-2016
@copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
   Set tabs to 4.
*/

/*
Setup:

 	$db = NewADOConnection('text');
 	$db->Connect($array,[$types],[$colnames]);

	Parameter $array is the 2 dimensional array of data. The first row can contain the
	column names. If column names is not defined in first row, you MUST define $colnames,
	the 3rd parameter.

	Parameter $types is optional. If defined, it should contain an array matching
	the number of columns in $array, with each element matching the correct type defined
	by MetaType: (B,C,I,L,N). If undefined, we will probe for $this->_proberows rows
	to guess the type. Only C,I and N are recognised.

	Parameter $colnames is optional. If defined, it is an array that contains the
	column names of $array. If undefined, we assume the first row of $array holds the
	column names.

 The Execute() function will return a recordset. The recordset works like a normal recordset.
 We have partial support for SQL parsing. We process the SQL using the following rules:

 1. SQL order by's always work for the first column ordered. Subsequent cols are ignored

 2. All operations take place on the same table. No joins possible. In fact the FROM clause
	is ignored! You can use any name for the table.

 3. To simplify code, all columns are returned, except when selecting 1 column

 	$rs = $db->Execute('select col1,col2 from table'); // sql ignored, will generate all cols

	We special case handling of 1 column because it is used in filter popups

	$rs = $db->Execute('select col1 from table');
	// sql accepted and processed -- any table name is accepted

	$rs = $db->Execute('select distinct col1 from table');
	// sql accepted and processed

4. Where clauses are ignored, but searching with the 3rd parameter of Execute is permitted.
   This has to use PHP syntax and we will eval() it. You can even use PHP functions.

	 $rs = $db->Execute('select * from table',false,"\$COL1='abc' and $\COL2=3")
 	// the 3rd param is searched -- make sure that $COL1 is a legal column name
	// and all column names must be in upper case.

4. Group by, having, other clauses are ignored

5. Expression columns, min(), max() are ignored

6. All data is readonly. Only SELECTs permitted.
*/

namespace ADOdb\drivers\RecordSets;
use \ADORecordSet_array;

/*--------------------------------------------------------------------------------------
	 Class Name: Recordset
--------------------------------------------------------------------------------------*/


class text extends ADORecordSet_array
{

	var $databaseType = "text";

	function __construct(&$conn,$mode=false)
	{
		parent::__construct();
		$this->InitArray($conn->_rezarray,$conn->_reztypes,$conn->_reznames);
		$conn->_rezarray = false;
	}

} // class ADORecordSet_text

