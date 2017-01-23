<?php

namespace ADOdb\drivers\Arrays;

class pdo_sqlsrv extends pdo
{

	/**
	 * returns the field object
	 *
	 * Note that this is a direct copy of the ADORecordSet_pdo_sqlsrv method
	 *
	 * @param  int $fieldOffset Optional field offset
	 *
	 * @return object The ADOfieldobject describing the field
	 */
	public function fetchField($fieldOffset = 0)
	{
		static $fieldObjects = array();
		// Default behavior allows passing in of -1 offset, which crashes the method
		if ($fieldOffset == -1) {
			$fieldOffset++;
		}

		if (isset($fieldObjects[$fieldOffset])) {
			// Look for cached field offset
			return $fieldObjects[$fieldOffset];
		}

		$o = new ADOFieldObject();
		$arr = @$this->_queryID->getColumnMeta($fieldOffset);

		if (!$arr) {
			$o->name = 'bad getColumnMeta()';
			$o->max_length = -1;
			$o->type = 'VARCHAR';
			$o->precision = 0;
			return $o;
		}
		$o->name = $arr['name'];
		if (isset($arr['sqlsrv:decl_type']) && $arr['sqlsrv:decl_type'] <> "null") {
			// Use the SQL Server driver specific value
			$o->type = $arr['sqlsrv:decl_type'];
		} else {
			$o->type = adodb_pdo_type($arr['pdo_type']);
		}
		$o->max_length = $arr['len'];
		$o->precision = $arr['precision'];

		switch (ADODB_ASSOC_CASE) {
			case ADODB_ASSOC_CASE_LOWER:
				$o->name = strtolower($o->name);
				break;
			case ADODB_ASSOC_CASE_UPPER:
				$o->name = strtoupper($o->name);
				break;
		}

		// Add to the cache
		$fieldObjects[$fieldOffset] = $o;
		return $o;
	}
}
