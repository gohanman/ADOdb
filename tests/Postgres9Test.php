<?php

class Postgres9Test extends PHPUnit_Framework_TestCase
{
    public function testDB()
    {
        $credentials = json_decode(__DIR__ . '/credentials.json');
        $credentials = $credentials['postgres'];

        $con = ADONewConnection('postgres9');
        $this->assertInternalType('object', $con, 'Could not get driver object');
        $this->assertEquals(false, $con->IsConnected());

        $info = $con->ServerInfo();
        $this->assertArrayHasKey('description', $info);
        $this->assertArrayHasKey('version', $info);

        $con->Connect('localhost', $credentials['user'], $credentials['password'], 'adodb_test');
        $this->assertEquals(true, $con->IsConnected(), 'Could not connect');

        $this->assertEquals(true, is_numeric($con->Time()), 'Could not get time');
        $this->assertEquals('CURRENT_DATE', $con->SQLDate('Y-m-d'));
        $this->assertEquals('TO_CHAR(foo,\'YYYY-MM-DD\')', $con->SQLDate('Y-m-d', 'foo'));

        $this->assertEquals('foo', $con->Prepare('foo'));
        $this->assertEquals('foo', $con->PrepareSP('foo'));

        $this->assertEquals("'foo'", $con->qstr('foo'));
        $this->assertEquals('$1', $con->Param('foo'));

        $con->Execute("DROP TABLE IF EXISTS test");

        $create = $con->Prepare("CREATE TABLE test (id SERIAL, val INT, PRIMARY KEY(id))");
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
    }
}

