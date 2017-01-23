<?php
/*
@version   v5.21.0-dev  ??-???-2016
@copyright (c) 2000-2013  John Lim (jlim#natsoft.com).  All rights
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
reserved.
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
Set tabs to 4 for best viewing.

  Latest version is available at http://adodb.sourceforge.net

  21.02.2002 - Wade Johnson wade@wadejohnson.de
			   Extended ODBC class for Sybase SQLAnywhere.
   1) Added support to retrieve the last row insert ID on tables with
	  primary key column using autoincrement function.

   2) Added blob support.  Usage:
		 a) create blob variable on db server:

		$dbconn->create_blobvar($blobVarName);

	  b) load blob var from file.  $filename must be complete path

	  $dbcon->load_blobvar_from_file($blobVarName, $filename);

	  c) Use the $blobVarName in SQL insert or update statement in the values
	  clause:

		$recordSet = $dbconn->Execute('INSERT INTO tabname (idcol, blobcol) '
		.
	   'VALUES (\'test\', ' . $blobVarName . ')');

	 instead of loading blob from a file, you can also load from
	  an unformatted (raw) blob variable:
	  $dbcon->load_blobvar_from_var($blobVarName, $varName);

	  d) drop blob variable on db server to free up resources:
	  $dbconn->drop_blobvar($blobVarName);

  Sybase_SQLAnywhere data driver. Requires ODBC.

*/
namespace ADOdb\drivers\RecordSets;

class  sqlanywhere extends odbc {

var $databaseType = "sqlanywhere";


}; //class

