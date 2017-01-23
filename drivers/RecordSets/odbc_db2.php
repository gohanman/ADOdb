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

  DB2 data driver. Requires ODBC.

From phpdb list:

Hi Andrew,

thanks a lot for your help. Today we discovered what
our real problem was:

After "playing" a little bit with the php-scripts that try
to connect to the IBM DB2, we set the optional parameter
Cursortype when calling odbc_pconnect(....).

And the exciting thing: When we set the cursor type
to SQL_CUR_USE_ODBC Cursor Type, then
the whole query speed up from 1 till 10 seconds
to 0.2 till 0.3 seconds for 100 records. Amazing!!!

Therfore, PHP is just almost fast as calling the DB2
from Servlets using JDBC (don't take too much care
about the speed at whole: the database was on a
completely other location, so the whole connection
was made over a slow network connection).

I hope this helps when other encounter the same
problem when trying to connect to DB2 from
PHP.

Kind regards,
Christian Szardenings

2 Oct 2001
Mark Newnham has discovered that the SQL_CUR_USE_ODBC is not supported by
IBM's DB2 ODBC driver, so this must be a 3rd party ODBC driver.

From the IBM CLI Reference:

SQL_ATTR_ODBC_CURSORS (DB2 CLI v5)
This connection attribute is defined by ODBC, but is not supported by DB2
CLI. Any attempt to set or get this attribute will result in an SQLSTATE of
HYC00 (Driver not capable).

A 32-bit option specifying how the Driver Manager uses the ODBC cursor
library.

So I guess this means the message [above] was related to using a 3rd party
odbc driver.

Setting SQL_CUR_USE_ODBC
========================
To set SQL_CUR_USE_ODBC for drivers that require it, do this:

$db = NewADOConnection('odbc_db2');
$db->curMode = SQL_CUR_USE_ODBC;
$db->Connect($dsn, $userid, $pwd);



USING CLI INTERFACE
===================

I have had reports that the $host and $database params have to be reversed in
Connect() when using the CLI interface. From Halmai Csongor csongor.halmai#nexum.hu:

> The symptom is that if I change the database engine from postgres or any other to DB2 then the following
> connection command becomes wrong despite being described this version to be correct in the docs.
>
> $connection_object->Connect( $DATABASE_HOST, $DATABASE_AUTH_USER_NAME, $DATABASE_AUTH_PASSWORD, $DATABASE_NAME )
>
> In case of DB2 I had to swap the first and last arguments in order to connect properly.


System Error 5
==============
IF you get a System Error 5 when trying to Connect/Load, it could be a permission problem. Give the user connecting
to DB2 full rights to the DB2 SQLLIB directory, and place the user in the DBUSERS group.
*/

namespace ADOdb\drivers\RecordSets;

class  odbc_db2 extends odbc {

	var $databaseType = "db2";

	function MetaType($t,$len=-1,$fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}

		switch (strtoupper($t)) {
		case 'VARCHAR':
		case 'CHAR':
		case 'CHARACTER':
		case 'C':
			if ($len <= $this->blobSize) return 'C';

		case 'LONGCHAR':
		case 'TEXT':
		case 'CLOB':
		case 'DBCLOB': // double-byte
		case 'X':
			return 'X';

		case 'BLOB':
		case 'GRAPHIC':
		case 'VARGRAPHIC':
			return 'B';

		case 'DATE':
		case 'D':
			return 'D';

		case 'TIME':
		case 'TIMESTAMP':
		case 'T':
			return 'T';

		//case 'BOOLEAN':
		//case 'BIT':
		//	return 'L';

		//case 'COUNTER':
		//	return 'R';

		case 'INT':
		case 'INTEGER':
		case 'BIGINT':
		case 'SMALLINT':
		case 'I':
			return 'I';

		default: return ADODB_DEFAULT_METATYPE;
		}
	}
}

