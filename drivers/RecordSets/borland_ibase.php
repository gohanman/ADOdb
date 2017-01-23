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

  Support Borland Interbase 6.5 and later

*/

namespace ADOdb\drivers\RecordSets;

// security - hide paths
if (!defined('ADODB_DIR')) die();

class  borland_ibase extends ibase {

	var $databaseType = "borland_ibase";

}

