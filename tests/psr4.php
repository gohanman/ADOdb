<?php

include(__DIR__ . '/../vendor/autoload.php');

$connections = array(
    'ADOdb\\drivers\\Connections\\ADO',
    'ADOdb\\drivers\\Connections\\ADO5',
    'ADOdb\\drivers\\Connections\\ADOAccess',
    'ADOdb\\drivers\\Connections\\ADOMssql',
    'ADOdb\\drivers\\Connections\\Ads',
    'ADOdb\\drivers\\Connections\\BorlandIBase',
    'ADOdb\\drivers\\Connections\\CSV',
    'ADOdb\\drivers\\Connections\\DB2',
    'ADOdb\\drivers\\Connections\\DB2OCI',
    'ADOdb\\drivers\\Connections\\FbSql',
    'ADOdb\\drivers\\Connections\\Firebird',
    'ADOdb\\drivers\\Connections\\IBase',
    'ADOdb\\drivers\\Connections\\Informix',
    'ADOdb\\drivers\\Connections\\Informix72',
    'ADOdb\\drivers\\Connections\\LDAP',
    'ADOdb\\drivers\\Connections\\Mssql',
    // causes die() if extension unavailable
    //'ADOdb\\drivers\\Connections\\MssqlNative',
    'ADOdb\\drivers\\Connections\\MssqlN',
    'ADOdb\\drivers\\Connections\\MssqlPo',
);

$record_sets = array(
    'ADOdb\\drivers\\RecordSets\\ADO',
    'ADOdb\\drivers\\RecordSets\\ADO5',
    'ADOdb\\drivers\\RecordSets\\ADOAccess',
    'ADOdb\\drivers\\RecordSets\\ADOMssql',
    'ADOdb\\drivers\\RecordSets\\Ads',
    'ADOdb\\drivers\\RecordSets\\BorlandIBase',
    'ADOdb\\drivers\\RecordSets\\CSV',
    'ADOdb\\drivers\\RecordSets\\DB2',
    'ADOdb\\drivers\\RecordSets\\DB2OCI',
    'ADOdb\\drivers\\RecordSets\\FbSql',
    'ADOdb\\drivers\\RecordSets\\Firebird',
    'ADOdb\\drivers\\RecordSets\\IBase',
    'ADOdb\\drivers\\RecordSets\\Informix',
    'ADOdb\\drivers\\RecordSets\\Informix72',
    'ADOdb\\drivers\\RecordSets\\LDAP',
    'ADOdb\\drivers\\RecordSets\\Mssql',
    'ADOdb\\drivers\\RecordSets\\MssqlNative',
    'ADOdb\\drivers\\RecordSets\\MssqlN',
    'ADOdb\\drivers\\RecordSets\\MssqlPo',
);

foreach ($connections as $con) {
    echo class_exists($con) ? "Found class {$con}" . PHP_EOL : "Missing class {$con}" . PHP_EOL;
}

foreach ($record_sets as $rs) {
    echo class_exists($rs) ? "Found class {$rs}" . PHP_EOL : "Missing class {$rs}" . PHP_EOL;
}
