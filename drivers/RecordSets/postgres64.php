<?php
/*
 @version   v5.21.0-dev  ??-???-2016
 @copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
 @copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
  Set tabs to 8.

  Original version derived from Alberto Cerezal (acerezalp@dbnet.es) - DBNet Informatica & Comunicaciones.
  08 Nov 2000 jlim - Minor corrections, removing mysql stuff
  09 Nov 2000 jlim - added insertid support suggested by "Christopher Kings-Lynne" <chriskl@familyhealth.com.au>
			  jlim - changed concat operator to || and data types to MetaType to match documented pgsql types
					 see http://www.postgresql.org/devel-corner/docs/postgres/datatype.htm
  22 Nov 2000 jlim - added changes to FetchField() and MetaTables() contributed by "raser" <raser@mail.zen.com.tw>
  27 Nov 2000 jlim - added changes to _connect/_pconnect from ideas by "Lennie" <leen@wirehub.nl>
  15 Dec 2000 jlim - added changes suggested by Additional code changes by "Eric G. Werk" egw@netguide.dk.
  31 Jan 2002 jlim - finally installed postgresql. testing
  01 Mar 2001 jlim - Freek Dijkstra changes, also support for text type

  See http://www.varlena.com/varlena/GeneralBits/47.php

	-- What indexes are on my table?
	select * from pg_indexes where tablename = 'tablename';

	-- What triggers are on my table?
	select c.relname as "Table", t.tgname as "Trigger Name",
	   t.tgconstrname as "Constraint Name", t.tgenabled as "Enabled",
	   t.tgisconstraint as "Is Constraint", cc.relname as "Referenced Table",
	   p.proname as "Function Name"
	from pg_trigger t, pg_class c, pg_class cc, pg_proc p
	where t.tgfoid = p.oid and t.tgrelid = c.oid
	   and t.tgconstrrelid = cc.oid
	   and c.relname = 'tablename';

	-- What constraints are on my table?
	select r.relname as "Table", c.conname as "Constraint Name",
	   contype as "Constraint Type", conkey as "Key Columns",
	   confkey as "Foreign Columns", consrc as "Source"
	from pg_class r, pg_constraint c
	where r.oid = c.conrelid
	   and relname = 'tablename';

*/

namespace ADOdb\drivers\RecordSets;
use \ADORecordSet;

/*--------------------------------------------------------------------------------------
	Class Name: Recordset
--------------------------------------------------------------------------------------*/

class postgres64 extends ADORecordSet{
	var $_blobArr;
	var $databaseType = "postgres64";
	var $canSeek = true;

	function __construct($queryID, $mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		switch ($mode)
		{
		case ADODB_FETCH_NUM: $this->fetchMode = PGSQL_NUM; break;
		case ADODB_FETCH_ASSOC:$this->fetchMode = PGSQL_ASSOC; break;

		case ADODB_FETCH_DEFAULT:
		case ADODB_FETCH_BOTH:
		default: $this->fetchMode = PGSQL_BOTH; break;
		}
		$this->adodbFetchMode = $mode;

		// Parent's constructor
		parent::__construct($queryID);
	}

	function GetRowAssoc($upper = ADODB_ASSOC_CASE)
	{
		if ($this->fetchMode == PGSQL_ASSOC && $upper == ADODB_ASSOC_CASE_LOWER) {
			return $this->fields;
		}
		$row = ADORecordSet::GetRowAssoc($upper);
		return $row;
	}


	function _initrs()
	{
	global $ADODB_COUNTRECS;
		$qid = $this->_queryID;
		$this->_numOfRows = ($ADODB_COUNTRECS)? @pg_num_rows($qid):-1;
		$this->_numOfFields = @pg_num_fields($qid);

		// cache types for blob decode check
		// apparently pg_field_type actually performs an sql query on the database to get the type.
		if (empty($this->connection->noBlobs))
		for ($i=0, $max = $this->_numOfFields; $i < $max; $i++) {
			if (pg_field_type($qid,$i) == 'bytea') {
				$this->_blobArr[$i] = pg_field_name($qid,$i);
			}
		}
	}

		/* Use associative array to get fields array */
	function Fields($colname)
	{
		if ($this->fetchMode != PGSQL_NUM) return @$this->fields[$colname];

		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$o = $this->FetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}
		return $this->fields[$this->bind[strtoupper($colname)]];
	}

	function FetchField($off = 0)
	{
		// offsets begin at 0

		$o= new ADOFieldObject();
		$o->name = @pg_field_name($this->_queryID,$off);
		$o->type = @pg_field_type($this->_queryID,$off);
		$o->max_length = @pg_fieldsize($this->_queryID,$off);
		return $o;
	}

	function _seek($row)
	{
		return @pg_fetch_row($this->_queryID,$row);
	}

	function _decode($blob)
	{
		if ($blob === NULL) return NULL;
//		eval('$realblob="'.adodb_str_replace(array('"','$'),array('\"','\$'),$blob).'";');
		return pg_unescape_bytea($blob);
	}

	function _fixblobs()
	{
		if ($this->fetchMode == PGSQL_NUM || $this->fetchMode == PGSQL_BOTH) {
			foreach($this->_blobArr as $k => $v) {
				$this->fields[$k] = postgres64::_decode($this->fields[$k]);
			}
		}
		if ($this->fetchMode == PGSQL_ASSOC || $this->fetchMode == PGSQL_BOTH) {
			foreach($this->_blobArr as $k => $v) {
				$this->fields[$v] = postgres64::_decode($this->fields[$v]);
			}
		}
	}

	// 10% speedup to move MoveNext to child class
	function MoveNext()
	{
		if (!$this->EOF) {
			$this->_currentRow++;
			if ($this->_numOfRows < 0 || $this->_numOfRows > $this->_currentRow) {
				$this->fields = @pg_fetch_array($this->_queryID,$this->_currentRow,$this->fetchMode);
				if (is_array($this->fields) && $this->fields) {
					if (isset($this->_blobArr)) $this->_fixblobs();
					return true;
				}
			}
			$this->fields = false;
			$this->EOF = true;
		}
		return false;
	}

	function _fetch()
	{

		if ($this->_currentRow >= $this->_numOfRows && $this->_numOfRows >= 0)
			return false;

		$this->fields = @pg_fetch_array($this->_queryID,$this->_currentRow,$this->fetchMode);

		if ($this->fields && isset($this->_blobArr)) $this->_fixblobs();

		return (is_array($this->fields));
	}

	function _close()
	{
		return @pg_free_result($this->_queryID);
	}

	function MetaType($t,$len=-1,$fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}
		switch (strtoupper($t)) {
				case 'MONEY': // stupid, postgres expects money to be a string
				case 'INTERVAL':
				case 'CHAR':
				case 'CHARACTER':
				case 'VARCHAR':
				case 'NAME':
				case 'BPCHAR':
				case '_VARCHAR':
				case 'CIDR':
				case 'INET':
				case 'MACADDR':
					if ($len <= $this->blobSize) return 'C';

				case 'TEXT':
					return 'X';

				case 'IMAGE': // user defined type
				case 'BLOB': // user defined type
				case 'BIT':	// This is a bit string, not a single bit, so don't return 'L'
				case 'VARBIT':
				case 'BYTEA':
					return 'B';

				case 'BOOL':
				case 'BOOLEAN':
					return 'L';

				case 'DATE':
					return 'D';


				case 'TIMESTAMP WITHOUT TIME ZONE':
				case 'TIME':
				case 'DATETIME':
				case 'TIMESTAMP':
				case 'TIMESTAMPTZ':
					return 'T';

				case 'SMALLINT':
				case 'BIGINT':
				case 'INTEGER':
				case 'INT8':
				case 'INT4':
				case 'INT2':
					if (isset($fieldobj) &&
				empty($fieldobj->primary_key) && (!$this->connection->uniqueIisR || empty($fieldobj->unique))) return 'I';

				case 'OID':
				case 'SERIAL':
					return 'R';

				default:
					return ADODB_DEFAULT_METATYPE;
			}
	}

}

