<?php
/*
@version   v5.21.0-dev  ??-???-2016
@copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
  Set tabs to 4.

  Currently unsupported: MetaDatabases, MetaTables and MetaColumns, and also inputarr in Execute.
  Native types have been converted to MetaTypes.
  Transactions not supported yet.

	  http://support.microsoft.com/default.aspx?scid=kb;en-us;260694
*/

namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;

class CSV extends ADORecordset {

	function _close()
	{
		return true;
	}
}

