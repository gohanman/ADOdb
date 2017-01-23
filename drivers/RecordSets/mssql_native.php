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

  Native mssql driver. Requires mssql client. Works on Windows.
    http://www.microsoft.com/sql/technologies/php/default.mspx
  To configure for Unix, see
   	http://phpbuilder.com/columns/alberto20000919.php3

    $stream = sqlsrv_get_field($stmt, $index, SQLSRV_SQLTYPE_STREAM(SQLSRV_ENC_BINARY));
    stream_filter_append($stream, "convert.iconv.ucs-2/utf-8"); // Voila, UTF-8 can be read directly from $stream

*/

namespace ADOdb\drivers\RecordSets;
use ADOdb\drivers\Arrays\mssql_native as MssqlNativeArray;
use \ADORecordSet;

/*--------------------------------------------------------------------------------------
	Class Name: Recordset
--------------------------------------------------------------------------------------*/

class mssql_native extends ADORecordSet {

	var $databaseType = "mssqlnative";
	var $canSeek = false;
	var $fieldOffset = 0;
	// _mths works only in non-localised system

	/*
	 * Holds a cached version of the metadata
	 */
	private $fieldObjects = false;

	/*
	 * Flags if we have retrieved the metadata
	 */
	private $fieldObjectsRetrieved = false;

	/*
	* Cross-reference the objects by name for easy access
	*/
	private $fieldObjectsIndex = array();


	/*
	 * Cross references the dateTime objects for faster decoding
	 */
	private $dateTimeObjects = array();

	/*
	 * flags that we have dateTimeObjects to handle
	 */
	private $hasDateTimeObjects = false;

	/*
	 * This is cross reference between how the types are stored
	 * in SQL Server and their english-language description
	 */
	private $_typeConversion = array(
			-155 => 'datetimeoffset',
			-154 => 'time',
			-152 => 'xml',
			-151 => 'udt',
			-11  => 'uniqueidentifier',
			-10  => 'ntext',
			-9   => 'nvarchar',
			-8   => 'nchar',
			-7   => 'bit',
			-6   => 'tinyint',
			-5   => 'bigint',
			-4   => 'image',
			-3   => 'varbinary',
			-2   => 'timestamp',
			-1   => 'text',
			 1   => 'char',
			 2   => 'numeric',
			 3   => 'decimal',
			 4   => 'int',
			 5   => 'smallint',
			 6   => 'float',
			 7   => 'real',
			 12  => 'varchar',
			 91  => 'date',
			 93  => 'datetime'
			);




	function __construct($id,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;

		}
		$this->fetchMode = $mode;
		return parent::__construct($id,$mode);
	}


	function _initrs()
	{
		$this->_numOfRows = -1;//not supported
		$fieldmeta = sqlsrv_field_metadata($this->_queryID);
		$this->_numOfFields = ($fieldmeta)? count($fieldmeta):-1;
		/*
		* Cache the metadata right now
		 */
		$this->_fetchField();

	}


	//Contributed by "Sven Axelsson" <sven.axelsson@bokochwebb.se>
	// get next resultset - requires PHP 4.0.5 or later
	function NextRecordSet()
	{
		if (!sqlsrv_next_result($this->_queryID)) return false;
		$this->_inited = false;
		$this->bind = false;
		$this->_currentRow = -1;
		$this->Init();
		return true;
	}

	/* Use associative array to get fields array */
	function Fields($colname)
	{
		if ($this->fetchMode != ADODB_FETCH_NUM) return $this->fields[$colname];
		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$o = $this->FetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}

		return $this->fields[$this->bind[strtoupper($colname)]];
	}

	/**
	* Returns: an object containing field information.
	*
	* Get column information in the Recordset object. fetchField()
	* can be used in order to obtain information about fields in a
	* certain query result. If the field offset isn't specified,
	* the next field that wasn't yet retrieved by fetchField()
	* is retrieved.
	*
	* $param int $fieldOffset (optional default=-1 for all
	* @return mixed an ADOFieldObject, or array of objects
	*/
	private function _fetchField($fieldOffset = -1)
	{
		if ($this->fieldObjectsRetrieved){
			if ($this->fieldObjects) {
				/*
				 * Already got the information
				 */
				if ($fieldOffset == -1)
					return $this->fieldObjects;
				else
					return $this->fieldObjects[$fieldOffset];
			}
			else
				/*
			     * No metadata available
				 */
				return false;
		}

		$this->fieldObjectsRetrieved = true;
		/*
		 * Retrieve all metadata in one go. This is always returned as a
		 * numeric array.
		 */
		$fieldMetaData = sqlsrv_field_metadata($this->_queryID);

		if (!$fieldMetaData)
			/*
		     * Not a statement that gives us metaData
			 */
			return false;

		$this->_numOfFields = count($fieldMetaData);
		foreach ($fieldMetaData as $key=>$value)
		{

			$fld = new ADOFieldObject;
			/*
			 * Caution - keys are case-sensitive, must respect
			 * casing of values
			 */

			$fld->name          = $value['Name'];
			$fld->max_length    = $value['Size'];
			$fld->column_source = $value['Name'];
			$fld->type          = $this->_typeConversion[$value['Type']];

			$this->fieldObjects[$key] = $fld;

			$this->fieldObjectsIndex[$fld->name] = $key;

		}
		if ($fieldOffset == -1)
			return $this->fieldObjects;

		return $this->fieldObjects[$fieldOffset];
	}

	/*
	 * Fetchfield copies the oracle method, it loads the field information
	 * into the _fieldobjs array once, to save multiple calls to the
	 * sqlsrv_field_metadata function
	 *
	 * @param int $fieldOffset	(optional)
	 *
	 * @return adoFieldObject
	 *
	 * @author 	KM Newnham
	 * @date 	02/20/2013
	 */
	function fetchField($fieldOffset = -1)
	{
		return $this->fieldObjects[$fieldOffset];
	}

	function _seek($row)
	{
		return false;//There is no support for cursors in the driver at this time.  All data is returned via forward-only streams.
	}

	// speedup
	function MoveNext()
	{
		if ($this->EOF)
			return false;

		$this->_currentRow++;

		if ($this->_fetch())
			return true;
		$this->EOF = true;

		return false;
	}

	function _fetch($ignore_fields=false)
	{
		if ($this->fetchMode & ADODB_FETCH_ASSOC) {
			if ($this->fetchMode & ADODB_FETCH_NUM)
				$this->fields = @sqlsrv_fetch_array($this->_queryID,SQLSRV_FETCH_BOTH);
			else
				$this->fields = @sqlsrv_fetch_array($this->_queryID,SQLSRV_FETCH_ASSOC);

			if (is_array($this->fields))
			{

				if (ADODB_ASSOC_CASE == ADODB_ASSOC_CASE_LOWER)
					$this->fields = array_change_key_case($this->fields,CASE_LOWER);
				else if (ADODB_ASSOC_CASE == ADODB_ASSOC_CASE_UPPER)
					$this->fields = array_change_key_case($this->fields,CASE_UPPER);

			}
		}
		else
			$this->fields = @sqlsrv_fetch_array($this->_queryID,SQLSRV_FETCH_NUMERIC);

		if (!$this->fields)
			return false;

		return $this->fields;
	}

	/**
	 * close() only needs to be called if you are worried about using too much
	 * memory while your script is running. All associated result memory for
	 * the specified result identifier will automatically be freed.
	 */
	function _close()
	{
		if(is_object($this->_queryID)) {
			$rez = sqlsrv_free_stmt($this->_queryID);
			$this->_queryID = false;
			return $rez;
		}
		return true;
	}

	// mssql uses a default date like Dec 30 2000 12:00AM
	static function UnixDate($v)
	{
		return MssqlNativeArray::UnixDate($v);
	}

	static function UnixTimeStamp($v)
	{
		return MssqlNativeArray::UnixTimeStamp($v);
	}
}


