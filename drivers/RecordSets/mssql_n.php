<?php

/// $Id $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// ADOdb  - Database Abstraction Library for PHP                         //
//          http://adodb.sourceforge.net/                                //
//                                                                       //
// Copyright (c) 2000-2014 John Lim (jlim\@natsoft.com.my)               //
//          All rights reserved.                                         //
//          Released under both BSD license and LGPL library license.    //
//          Whenever there is any discrepancy between the two licenses,  //
//          the BSD license will take precedence                         //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 2001-3001 Martin Dougiamas        http://dougiamas.com  //
//           (C) 2001-3001 Eloy Lafuente (stronk7) http://contiento.com  //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
*  MSSQL Driver with auto-prepended "N" for correct unicode storage
*  of SQL literal strings. Intended to be used with MSSQL drivers that
*  are sending UCS-2 data to MSSQL (FreeTDS and ODBTP) in order to get
*  true cross-db compatibility from the application point of view.
*/

namespace ADOdb\drivers\RecordSets;

class mssql_n extends mssql {
	var $databaseType = "mssql_n";
}

