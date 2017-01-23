<?php
/*
@version   v5.21.0-dev  ??-???-2016
@copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
  Set tabs to 4.

  Synonym for csv driver.
*/

namespace ADOdb\drivers\Connections;

// security - hide paths
if (!defined('ADODB_DIR')) die();

if (! defined("_ADODB_PROXY_LAYER")) {
	define("_ADODB_PROXY_LAYER", 1 );

class proxy extends csv {
	var $databaseType = 'proxy';
	var $databaseProvider = 'csv';
}

} // define
