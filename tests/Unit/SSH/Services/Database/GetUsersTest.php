<?php

namespace Tests\Unit\SSH\Services\Database;

use App\Facades\SSH;
use App\Services\Database\Database;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GetUsersTest extends TestCase
{
    #[DataProvider('data')]
    public function test_get_users(string $name, string $version, string $output): void
    {
        $database = $this->server->database();
        $database->name = $name;
        $database->version = $version;
        $database->save();

        SSH::fake($output);

        /** @var Database $databaseHandler */
        $databaseHandler = $database->handler();
        $users = $databaseHandler->getUsers();

        $this->assertEquals('vito', $users[0][0]);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function data(): array
    {
        return [
            [
                'mysql',
                '8.0',
                <<<'EOD'
                User	Host	Privileges
                vito	localhost	mydb,testdb
                mysql.infoschema	localhost	NULL
                mysql.session	localhost	performance_schema
                mysql.sys	localhost	sys
                root	localhost	NULL
                EOD
            ],
            [
                'mysql',
                '5.7',
                <<<'EOD'
                User	Host	Privileges
                vito	localhost	mydb,testdb
                mysql.infoschema	localhost	NULL
                mysql.session	localhost	performance_schema
                mysql.sys	localhost	sys
                root	localhost	NULL
                EOD
            ],
            [
                'mariadb',
                '11.4',
                <<<'EOD'
                User	Host	Privileges
                mariadb.sys	localhost	NULL
                mysql	localhost	NULL
                root	localhost	NULL
                vito	localhost	NULL
                EOD
            ],
            [
                'postgresql',
                '16',
                <<<'EOD'
                 username | host |                databases
                ----------+------+------------------------------------------
                 postgres |      | template1,template0,postgres,test,vitodb
                 vito     |      | template1,template0,postgres,test,vitodb
                (2 rows)
                EOD
            ],
        ];
    }
}
