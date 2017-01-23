<?php
/*
@version   v5.21.0-dev  ??-???-2016
@copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
Released under both BSD license and Lesser GPL library license.
Whenever there is any discrepancy between the two licenses,
the BSD license will take precedence. See License.txt.
Set tabs to 4 for best viewing.

  Latest version is available at http://adodb.sourceforge.net

	Microsoft Access ADO data driver. Requires ADO and ODBC. Works only on MS Windows.
*/

namespace ADOdb\drivers\Connections;

// security - hide paths
if (!defined('ADODB_DIR')) die();

if (PHP_VERSION >= 5) {

    class ado_access extends ado5 {
        var $databaseType = 'ado_access';
        var $hasTop = 'top';		// support mssql SELECT TOP 10 * FROM TABLE
        var $fmtDate = "#Y-m-d#";
        var $fmtTimeStamp = "#Y-m-d h:i:sA#";// note no comma
        var $sysDate = "FORMAT(NOW,'yyyy-mm-dd')";
        var $sysTimeStamp = 'NOW';
        var $upperCase = 'ucase';

        /*function BeginTrans() { return false;}

        function CommitTrans() { return false;}

        function RollbackTrans() { return false;}*/

    }
} else {

    class ado_access extends ado {
        var $databaseType = 'ado_access';
        var $hasTop = 'top';		// support mssql SELECT TOP 10 * FROM TABLE
        var $fmtDate = "#Y-m-d#";
        var $fmtTimeStamp = "#Y-m-d h:i:sA#";// note no comma
        var $sysDate = "FORMAT(NOW,'yyyy-mm-dd')";
        var $sysTimeStamp = 'NOW';
        var $upperCase = 'ucase';

        /*function BeginTrans() { return false;}

        function CommitTrans() { return false;}

        function RollbackTrans() { return false;}*/

    }
}


