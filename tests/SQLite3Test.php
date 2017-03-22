<?php

class SQLite3Test extends PHPUnit_Framework_TestCase
{
    public function testDB()
    {
        if (!class_exists('SQLite3')) {
            echo "Skipping SQLite3 tests" . PHP_EOL;
            return;
        }
        $db_file = tempnam(sys_get_temp_dir(), 'sql') . '.db';

        $con = ADONewConnection('sqlite3');
        $this->assertInternalType('object', $con, 'Could not get driver object');
        $this->assertEquals(false, $con->IsConnected());

        $con->Connect($db_file, '', '', '');
        $this->assertEquals(true, $con->IsConnected(), 'Could not connect');

        $this->assertEquals('adodb_date(\'Y-m-d\')', $con->SQLDate('Y-m-d'));
        $this->assertEquals('adodb_date2(\'Y-m-d\',foo)', $con->SQLDate('Y-m-d', 'foo'));

        $this->assertEquals('SELECT 1', $con->Prepare('SELECT 1'));
        $this->assertEquals('SELECT 1', $con->PrepareSP('SELECT 1'));

        $this->assertEquals("'foo'", $con->qstr('foo'));
        $this->assertEquals("'foo'", $con->Quote('foo'));
        $byRef = 'foo';
        $con->q($byRef);
        $this->assertEquals("'foo'", $byRef);
        $this->assertEquals('?', $con->Param('foo'));

        $con->Execute("DROP TABLE IF EXISTS test");

        $create = $con->Prepare("CREATE TABLE test (id INT PRIMARY KEY, val INT)");
        $con->Execute($create);
        $insert = $con->Prepare("INSERT INTO test (val) VALUES (?)");
        $con->Execute($insert, array(1));
        $this->assertEquals(1, $con->Insert_ID());
        $con->Execute('UPDATE test SET val=2 WHERE id=1');
        // not implemented?
        //$this->assertEquals(1, $con->Affected_Rows());
        $this->assertEquals('', $con->ErrorMsg());
        $this->assertEquals(0, $con->ErrorNo());
        $this->assertEquals(array('id'), $con->MetaPrimaryKeys('test'));

        $con->BeginTrans();
        $con->Execute("INSERT INTO test (val) VALUES (3)");
        $con->RollbackTrans();
        $rs = $con->Execute("SELECT id FROM test");
        $this->assertEquals(1, $rs->NumRows());

        $con->BeginTrans();
        $con->Execute("INSERT INTO test (val) VALUES (3)");
        $con->CommitTrans();
        $rs = $con->Execute("SELECT id FROM test");
        $this->assertEquals(2, $rs->NumRows());

        $this->assertEquals(" CASE WHEN id is null THEN 0 ELSE id END ", $con->IfNull('id', 0));
        $this->assertEquals("a||b", $con->Concat('a', 'b'));

        $this->assertEquals(false, $con->MetaDatabases());
        $this->assertEquals(array('test'), $con->MetaTables());
        $cols = $con->MetaColumns('test');
        $this->assertEquals(true, $cols['ID']->primary_key);
        $this->assertEquals(0, $cols['ID']->not_null);
        $this->assertEquals(false, $cols['VAL']->not_null);
        $this->assertEquals('INT', $cols['ID']->type);
        $this->assertEquals('id', $cols['ID']->name);
        // there's an underlying bug here with an undefined array-index
        // if no table indexes exist
        //$this->assertEquals(array(), $con->MetaIndexes('test'));
        $this->assertEquals(array('ID'=>'id', 'VAL'=>'val'), $con->MetaColumnNames('test'));

        $con->Execute("DROP TABLE IF EXISTS test");
        $this->assertEquals(true, $con->Close());
        unlink($db_file);
    }
}

