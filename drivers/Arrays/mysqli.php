<?php
/*
@version   v5.21.0-dev  ??-???-2016
@copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
  Set tabs to 8.

  This is the preferred driver for MySQL connections, and supports both transactional
  and non-transactional table types. You can use this as a drop-in replacement for both
  the mysql and mysqlt drivers. As of ADOdb Version 5.20.0, all other native MySQL drivers
  are deprecated

  Requires mysql client. Works on Windows and Unix.

21 October 2003: MySQLi extension implementation by Arjen de Rijke (a.de.rijke@xs4all.nl)
Based on adodb 3.40
*/

namespace ADOdb\drivers\Arrays;
use \ADORecordSet_array;

class mysqli extends ADORecordSet_array {

	function MetaType($t, $len = -1, $fieldobj = false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}


		$len = -1; // mysql max_length is not accurate
		switch (strtoupper($t)) {
		case 'STRING':
		case 'CHAR':
		case 'VARCHAR':
		case 'TINYBLOB':
		case 'TINYTEXT':
		case 'ENUM':
		case 'SET':

		case MYSQLI_TYPE_TINY_BLOB :
		#case MYSQLI_TYPE_CHAR :
		case MYSQLI_TYPE_STRING :
		case MYSQLI_TYPE_ENUM :
		case MYSQLI_TYPE_SET :
		case 253 :
			if ($len <= $this->blobSize) return 'C';

		case 'TEXT':
		case 'LONGTEXT':
		case 'MEDIUMTEXT':
			return 'X';

		// php_mysql extension always returns 'blob' even if 'text'
		// so we have to check whether binary...
		case 'IMAGE':
		case 'LONGBLOB':
		case 'BLOB':
		case 'MEDIUMBLOB':

		case MYSQLI_TYPE_BLOB :
		case MYSQLI_TYPE_LONG_BLOB :
		case MYSQLI_TYPE_MEDIUM_BLOB :

			return !empty($fieldobj->binary) ? 'B' : 'X';
		case 'YEAR':
		case 'DATE':
		case MYSQLI_TYPE_DATE :
		case MYSQLI_TYPE_YEAR :

			return 'D';

		case 'TIME':
		case 'DATETIME':
		case 'TIMESTAMP':

		case MYSQLI_TYPE_DATETIME :
		case MYSQLI_TYPE_NEWDATE :
		case MYSQLI_TYPE_TIME :
		case MYSQLI_TYPE_TIMESTAMP :

			return 'T';

		case 'INT':
		case 'INTEGER':
		case 'BIGINT':
		case 'TINYINT':
		case 'MEDIUMINT':
		case 'SMALLINT':

		case MYSQLI_TYPE_INT24 :
		case MYSQLI_TYPE_LONG :
		case MYSQLI_TYPE_LONGLONG :
		case MYSQLI_TYPE_SHORT :
		case MYSQLI_TYPE_TINY :

			if (!empty($fieldobj->primary_key)) return 'R';

			return 'I';


		// Added floating-point types
		// Maybe not necessery.
		case 'FLOAT':
		case 'DOUBLE':
//		case 'DOUBLE PRECISION':
		case 'DECIMAL':
		case 'DEC':
		case 'FIXED':
		default:
			//if (!is_numeric($t)) echo "<p>--- Error in type matching $t -----</p>";
			return 'N';
		}
	} // function

}

