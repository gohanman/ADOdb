<?php
/*
  @version   v5.21.0-dev  ??-???-2016
  @copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
  @copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community

  First cut at the Netezza Driver by Josh Eldridge joshuae74#hotmail.com
 Based on the previous postgres drivers.
 http://www.netezza.com/
 Major Additions/Changes:
    MetaDatabasesSQL, MetaTablesSQL, MetaColumnsSQL
    Note: You have to have admin privileges to access the system tables
    Removed non-working keys code (Netezza has no concept of keys)
    Fixed the way data types and lengths are returned in MetaColumns()
    as well as added the default lengths for certain types
    Updated public variables for Netezza
    Still need to remove blob functions, as Netezza doesn't suppport blob
*/

namespace ADOdb\drivers\RecordSets;

/*--------------------------------------------------------------------------------------
	 Class Name: Recordset
--------------------------------------------------------------------------------------*/

class netezza extends postgres64
{
	var $databaseType = "netezza";
	var $canSeek = true;

	// _initrs modified to disable blob handling
	function _initrs()
	{
	global $ADODB_COUNTRECS;
		$this->_numOfRows = ($ADODB_COUNTRECS)? @pg_num_rows($this->_queryID):-1;
		$this->_numOfFields = @pg_num_fields($this->_queryID);
	}

}

