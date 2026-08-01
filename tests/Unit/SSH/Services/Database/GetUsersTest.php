<?php

use App\Facades\SSH;
use App\Services\Database\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('get users', function (string $name, string $version, string $output) {
    $database = $this->server->database();
    $database->name = $name;
    $database->version = $version;
    $database->save();

    SSH::fake($output);

    /** @var Database $databaseHandler */
    $databaseHandler = $database->handler();
    $users = $databaseHandler->getUsers();

    expect($users[0][0])->toEqual('vito');
})->with('data');

/**
 * @return array<int, array<int, mixed>>
 */
dataset('data', function () {
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
});
