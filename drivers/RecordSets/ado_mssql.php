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

namespace ADOdb\drivers\RecordSets;

// security - hide paths
if (!defined('ADODB_DIR')) die();

if (PHP_VERSION >= 5) {
    class ado_mssql extends ado5 {

        var $databaseType = 'ado_mssql';

    }
} else {
    class ado_mssql extends ado {

        var $databaseType = 'ado_mssql';

    }
}

