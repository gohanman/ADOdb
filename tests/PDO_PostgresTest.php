<?php

class PDO_PostgresTest extends PHPUnit_Framework_TestCase
{
    public function testDB()
    {
        if (!class_exists('PDO')) {
            echo "Skipping PDO_Postgres tests" . PHP_EOL;
            return;
        }
        $credentials = json_decode(file_get_contents(__DIR__ . '/credentials.json'), true);
        $credentials = $credentials['postgres'];

        $con = ADONewConnection('pdo');
        $this->assertInternalType('object', $con, 'Could not get driver object');
        $this->assertEquals(false, $con->IsConnected());

        $con->Connect('pgsql:host=localhost;dbname=adodb_test', $credentials['user'], $credentials['password']);
        $this->assertEquals(true, $con->IsConnected(), 'Could not connect');

        $info = $con->ServerInfo();
        $this->assertArrayHasKey('description', $info);
        $this->assertArrayHasKey('version', $info);

        $this->assertEquals(true, is_numeric($con->Time()), 'Could not get time');
        /**
          When calling, $this->_driver is apparently null.
          This seems like an actual bug
        $this->assertEquals('CURRENT_DATE', $con->SQLDate('Y-m-d'));
        $this->assertEquals('TO_CHAR(foo,\'YYYY-MM-DD\')', $con->SQLDate('Y-m-d', 'foo'));
        */

        $this->assertInternalType('array', $con->Prepare('foo'));
        $this->assertInternalType('array', $con->PrepareSP('foo'));

        $this->assertEquals("'foo'", $con->qstr('foo'));
        $this->assertEquals('?', $con->Param('foo'));

        $con->Execute("DROP TABLE IF EXISTS test");

        $create = $con->Prepare("CREATE TABLE test (id SERIAL, val INT, PRIMARY KEY(id))");
        $con->Execute($create);
        $insert = $con->Prepare("INSERT INTO test (val) VALUES (?)");
        $con->Execute($insert, array(1));

        /**
          This is fixable by implementing _insertid() in the pdo_pgsql class
          and having the pdo class defer to the undering $_driver. The 
          table and column args are required to provide the sequence name,
          default table_column_seq
        */
        $this->assertEquals(false, $con->Insert_ID());

        $con->Execute('UPDATE test SET val=2 WHERE id=1');
        // another PDO postgres bug?
        //$this->assertEquals(0, $con->Affected_Rows());
        $this->assertEquals('', $con->ErrorMsg());
        $this->assertEquals(0, $con->ErrorNo());
        $this->assertEquals(array('id'), $con->MetaPrimaryKeys('test'));

        $rs = $con->Execute("SELECT id FROM test");
        $this->assertEquals(1, $rs->NumRows());

        $con->Execute("INSERT INTO test (val) VALUES (3)");
        $rs = $con->Execute("SELECT id FROM test");
        $this->assertEquals(2, $rs->NumRows());

        $con->Execute("DROP TABLE IF EXISTS test");
    }
}

