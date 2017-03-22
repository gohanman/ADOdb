<?php

class AllTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        foreach (array('mysqli', 'pdo_mysql', 'postgres9', 'pdo_pgsql', 'sqlite3', 'pdo_sqlite') as $driver) {
            $con = $this->getConnection($driver);
            if ($con === false) {
                echo "No {$driver} connection; skipping those tests" . PHP_EOL;
            }

            // generic tests go here
        }
    }

    private function getConnection($type)
    {
        switch ($type) {
            case 'mysqli':
                if (!function_exists('mysqli_connect')) {
                    return false;
                }
                $credentials = json_decode(file_get_contents(__DIR__ . '/credentials.json'), true);
                $credentials = $credentials['mysql'];
                $con = ADONewConnection('mysqli');
                $con->Connect('localhost', $credentials['user'], $credentials['password'], 'adodb_test');
                return $con->IsConected() ? true : false;

            case 'pdo_mysql':
                if (!class_exists('pdo')) {
                    return false;
                }
                $credentials = json_decode(file_get_contents(__DIR__ . '/credentials.json'), true);
                $credentials = $credentials['mysql'];
                $con = ADONewConnection('pdo');
                $con->Connect('mysql:host=localhost;dbname=adodb_test', $credentials['user'], $credentials['password']);
                return $con->IsConected() ? true : false;

            case 'postgres9':
                if (!function_exists('pg_connect')) {
                    return false;
                }
                $credentials = json_decode(file_get_contents(__DIR__ . '/credentials.json'), true);
                $credentials = $credentials['postgres'];
                $con = ADONewConnection('postgres9');
                $con->Connect('localhost', $credentials['user'], $credentials['password'], 'adodb_test');
                return $con->IsConected() ? true : false;

            case 'pdo_pgsql':
                if (!class_exists('pdo')) {
                    return false;
                }
                $credentials = json_decode(file_get_contents(__DIR__ . '/credentials.json'), true);
                $credentials = $credentials['mysql'];
                $con = ADONewConnection('pdo');
                $con->Connect('pgsql:host=localhost;dbname=adodb_test', $credentials['user'], $credentials['password']);
                return $con->IsConected() ? true : false;

            case 'sqlite3':
                if (!class_exists('pdo')) {
                    return false;
                }
                $db_file = tempnam(sys_get_temp_dir(), 'sql') . '.db';
                $con = ADONewConnection('sqlite3');
                $con->Connect($db_file, '', '', '');
                return $con->IsConected() ? true : false;

            case 'pdo_sqlite':
                if (!class_exists('pdo')) {
                    return false;
                }
                $db_file = tempnam(sys_get_temp_dir(), 'sql') . '.db';
                $con = ADONewConnection('pdo');
                $con->Connect('sqlite:' . $db_file, '', '', '');
                return $con->IsConected() ? true : false;
        }

        return false;
    }
}

