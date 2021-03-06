<?php

include_once __DIR__ . '/fixtures/Database.php';

use Pgdbsync\Db;
use Pgdbsync\DbConn;

class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    private $database;
    private $conf;

    public function __construct()
    {
        $this->conf = parse_ini_file(__DIR__ . "/fixtures/conf.ini", true);
    }

    public function test_compare_same_squema()
    {
        $dbVc = new Db();
        $dbVc->setMaster(new DbConn($this->conf['devel']));
        $dbVc->setSlave(new DbConn($this->conf['devel']));

        $diff = $dbVc->raw('public');

        $this->assertCount(1, $diff);
        $this->assertCount(0, $diff[0]['diff']);
    }

    public function test_compare_same_squema_diff()
    {
        $dbVc = new Db();
        $dbVc->setMaster(new DbConn($this->conf['devel']));
        $dbVc->setSlave(new DbConn($this->conf['devel']));

        $diff    = $dbVc->diff('public');

        $this->assertEquals("Already sync : db1\n", $diff);
    }

    public function test_compare_same_squema_summary()
    {
        $dbVc = new Db();
        $dbVc->setMaster(new DbConn($this->conf['devel']));
        $dbVc->setSlave(new DbConn($this->conf['devel']));

        $diff    = $dbVc->raw('public');
        $summary = $dbVc->summary('public');

        $this->assertEquals(implode("\n", $diff[0]['summary']) . "\n", $summary);

    }

    public function test_compare_same_squema_run()
    {
        $dbVc = new Db();
        $dbVc->setMaster(new DbConn($this->conf['devel']));
        $dbVc->setSlave(new DbConn($this->conf['devel']));

        $run = $dbVc->run('public');

        $this->assertEquals([], $run);

    }

    public function test_compare_different_databases()
    {
        $dbVc = new Db();
        $dbVc->setMaster(new DbConn($this->conf['devel']));
        $dbVc->setSlave(new DbConn($this->conf['devel2']));

        $diff = $dbVc->raw('public');

        $this->assertCount(1, $diff);
        $this->assertCount(1, $diff[0]['diff']);

        $this->assertEquals("DROP TABLE public.testtable2;", trim($diff[0]['diff'][0]));
    }

    public function setUp()
    {
        $this->database = new Database($this->conf);
        $this->database->executeInDatabase('devel', function (PDO $conn) {
            $conn->exec("CREATE TABLE testTable (
                userid VARCHAR PRIMARY KEY  NOT NULL ,
                password VARCHAR NOT NULL ,
                name VARCHAR,
                surname VARCHAR
            );");
        });

        $this->database->executeInDatabase('devel2', function (PDO $conn) {
            $conn->exec("CREATE TABLE testTable (
                userid VARCHAR PRIMARY KEY  NOT NULL ,
                password VARCHAR NOT NULL ,
                name VARCHAR,
                surname VARCHAR
            );");
            $conn->exec("CREATE TABLE testTable2 (
                userid VARCHAR PRIMARY KEY  NOT NULL ,
                password VARCHAR NOT NULL ,
                name VARCHAR,
                surname VARCHAR
            );");
        });
    }

    public function tearDown()
    {
        $this->database->executeInDatabase('devel', function (PDO $conn) {
            $conn->exec("DROP TABLE testTable");
        });

        $this->database->executeInDatabase('devel2', function (PDO $conn) {
            $conn->exec("DROP TABLE testTable");
            $conn->exec("DROP TABLE testTable2");
        });
    }
}