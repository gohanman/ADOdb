<?php

class SQLite3Test extends PHPUnit_Framework_TestCase
{
    public function testDB()
    {
        $db_file = tempnam(sys_get_temp_dir(), 'sql') . '.db';

        $con = ADONewConnection('sqlite3');
        $this->assertInternalType('object', $con, 'Could not get driver object');
        $this->assertEquals(false, $con->IsConnected());

        $con->Connect($db_file, '', '', '');
        $this->assertEquals(true, $con->IsConnected(), 'Could not connect');

        $info = $con->ServerInfo();
        $this->assertArrayHasKey('description', $info);
        $this->assertArrayHasKey('version', $info);

        $this->assertEquals(true, is_numeric($con->Time()), 'Could not get time');
        $this->assertEquals('DATE_FORMAT(NOW(),\'%Y-%m-%d\')', $con->SQLDate('Y-m-d'));
        $this->assertEquals('DATE_FORMAT(foo,\'%Y-%m-%d\')', $con->SQLDate('Y-m-d', 'foo'));

        $this->assertInternalType('array', $con->Prepare('SELECT 1'));
        $this->assertInternalType('array', $con->PrepareSP('SELECT 1'));

        $this->assertEquals("'foo'", $con->qstr('foo'));
        $this->assertEquals('?', $con->Param('foo'));

        $con->Execute("DROP TABLE IF EXISTS test");

        $create = $con->Prepare("CREATE TABLE test (id INT PRIMARY KEY, val INT)");
        $con->Execute($create);
        $insert = $con->Prepare("INSERT INTO test (val) VALUES (?)");
        $con->Execute($insert, array(1));
        $this->assertEquals(1, $con->Insert_ID());
        $con->Execute('UPDATE test SET val=2 WHERE id=1');
        $this->assertEquals(1, $con->Affected_Rows());
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

        $con->Execute("DROP TABLE IF EXISTS test");
        unlink($db_file);
    }
}

