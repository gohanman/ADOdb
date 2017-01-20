<?php
/*
  @version   v5.21.0-dev  ??-???-2016
  @copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
  @copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
   Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
  Set tabs to 8.

  Revision 1: (02/25/2005) Updated codebase to include the _inject_bind_options function. This allows
  users to access the options in the ldap_set_option function appropriately. Most importantly
  LDAP Version 3 is now supported. See the examples for more information. Also fixed some minor
  bugs that surfaced when PHP error levels were set high.

  Joshua Eldridge (joshuae74#hotmail.com)
*/

namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;

/*--------------------------------------------------------------------------------------
	Class Name: Recordset
--------------------------------------------------------------------------------------*/

class LDAP extends ADORecordSet{

	var $databaseType = "ldap";
	var $canSeek = false;
	var $_entryID; /* keeps track of the entry resource identifier */

	function __construct($queryID,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		switch ($mode)
		{
		case ADODB_FETCH_NUM:
			$this->fetchMode = LDAP_NUM;
			break;
		case ADODB_FETCH_ASSOC:
			$this->fetchMode = LDAP_ASSOC;
			break;
		case ADODB_FETCH_DEFAULT:
		case ADODB_FETCH_BOTH:
		default:
			$this->fetchMode = LDAP_BOTH;
			break;
		}

		parent::__construct($queryID);
	}

	function _initrs()
	{
		/*
		This could be teaked to respect the $COUNTRECS directive from ADODB
		It's currently being used in the _fetch() function and the
		GetAssoc() function
		*/
		$this->_numOfRows = ldap_count_entries( $this->connection->_connectionID, $this->_queryID );
	}

	/*
	Return whole recordset as a multi-dimensional associative array
	*/
	function GetAssoc($force_array = false, $first2cols = false, $fetchMode = -1)
	{
		$records = $this->_numOfRows;
		$results = array();
		for ( $i=0; $i < $records; $i++ ) {
			foreach ( $this->fields as $k=>$v ) {
				if ( is_array( $v ) ) {
					if ( $v['count'] == 1 ) {
						$results[$i][$k] = $v[0];
					} else {
						array_shift( $v );
						$results[$i][$k] = $v;
					}
				}
			}
		}

		return $results;
	}

	function GetRowAssoc($upper = ADODB_ASSOC_CASE)
	{
		$results = array();
		foreach ( $this->fields as $k=>$v ) {
			if ( is_array( $v ) ) {
				if ( $v['count'] == 1 ) {
					$results[$k] = $v[0];
				} else {
					array_shift( $v );
					$results[$k] = $v;
				}
			}
		}

		return $results;
	}

	function GetRowNums()
	{
		$results = array();
		foreach ( $this->fields as $k=>$v ) {
			static $i = 0;
			if (is_array( $v )) {
				if ( $v['count'] == 1 ) {
					$results[$i] = $v[0];
				} else {
					array_shift( $v );
					$results[$i] = $v;
				}
				$i++;
			}
		}
		return $results;
	}

	function _fetch()
	{
		if ( $this->_currentRow >= $this->_numOfRows && $this->_numOfRows >= 0 ) {
			return false;
		}

		if ( $this->_currentRow == 0 ) {
			$this->_entryID = ldap_first_entry( $this->connection->_connectionID, $this->_queryID );
		} else {
			$this->_entryID = ldap_next_entry( $this->connection->_connectionID, $this->_entryID );
		}

		$this->fields = ldap_get_attributes( $this->connection->_connectionID, $this->_entryID );
		$this->_numOfFields = $this->fields['count'];

		switch ( $this->fetchMode ) {

			case LDAP_ASSOC:
				$this->fields = $this->GetRowAssoc();
				break;

			case LDAP_NUM:
				$this->fields = array_merge($this->GetRowNums(),$this->GetRowAssoc());
				break;

			case LDAP_BOTH:
			default:
				$this->fields = $this->GetRowNums();
				break;
		}

		return is_array( $this->fields );
	}

	function _close() {
		@ldap_free_result( $this->_queryID );
		$this->_queryID = false;
	}
}

