<?php

namespace Tests\Unit\SSH\Services\Database;

use App\Facades\SSH;
use App\Services\Database\Database;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GetDatabasesTest extends TestCase
{
    #[DataProvider('data')]
    public function test_get_databases(string $name, string $version, string $output): void
    {
        $database = $this->server->database();
        $database->name = $name;
        $database->version = $version;
        $database->save();

        SSH::fake($output);

        /** @var Database $databaseHandler */
        $databaseHandler = $database->handler();
        $databases = $databaseHandler->getDatabases();

        $this->assertEquals('vito', $databases[0][0]);
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
                database_name	charset	collation
                mysql	utf8mb4	utf8mb4_0900_ai_ci
                information_schema	utf8mb3	utf8mb3_general_ci
                performance_schema	utf8mb4	utf8mb4_0900_ai_ci
                sys	utf8mb4	utf8mb4_0900_ai_ci
                vito	utf8mb3	utf8mb3_general_ci
                EOD
            ],
            [
                'mysql',
                '5.7',
                <<<'EOD'
                database_name	charset	collation
                mysql	utf8mb4	utf8mb4_0900_ai_ci
                information_schema	utf8mb3	utf8mb3_general_ci
                performance_schema	utf8mb4	utf8mb4_0900_ai_ci
                sys	utf8mb4	utf8mb4_0900_ai_ci
                vito	utf8mb3	utf8mb3_general_ci
                EOD
            ],
            [
                'mariadb',
                '11.4',
                <<<'EOD'
                database_name	charset	collation
                information_schema	utf8mb3	utf8mb3_general_ci
                mysql	utf8mb4	utf8mb4_uca1400_ai_ci
                performance_schema	utf8mb3	utf8mb3_general_ci
                sys	utf8mb3	utf8mb3_general_ci
                vito	utf8mb3	utf8mb3_general_ci
                EOD
            ],
            [
                'postgresql',
                '16',
                <<<'EOD'
                 database_name | charset |  collation
                ---------------+---------+-------------
                 postgres      | UTF8    | en_US.UTF-8
                 template1     | UTF8    | en_US.UTF-8
                 template0     | UTF8    | en_US.UTF-8
                 vito          | UTF8    | en_US.UTF-8
                (3 rows)
                EOD
            ],
        ];
    }
}
