<?php

use App\Facades\SSH;
use App\Services\Database\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function vitoPestUnitSSHServicesDatabaseGetCharsetsTestMysqlCharsets(): array
{
    return [
        'armscii8' => [
            'default' => 'armscii8_general_ci',
            'list' => [
                'armscii8_bin',
                'armscii8_general_ci',
            ],
        ],
        'ascii' => [
            'default' => 'ascii_general_ci',
            'list' => [
                'ascii_bin',
                'ascii_general_ci',
            ],
        ],
        'big5' => [
            'default' => 'big5_chinese_ci',
            'list' => [
                'big5_bin',
                'big5_chinese_ci',
            ],
        ],
    ];
}

test('update charsets', function (string $name, string $version, string $output, array $expected) {
    $database = $this->server->database();
    $database->name = $name;
    $database->version = $version;
    $database->save();

    SSH::fake($output);

    /** @var Database $databaseHandler */
    $databaseHandler = $database->handler();
    $charsets = $databaseHandler->getCharsets();

    expect($charsets['charsets'])->toEqual($expected);
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
                Collation	Charset	Id	Default	Compiled	Sortlen	Pad_attribute
                armscii8_bin	armscii8	64		Yes	1	PAD SPACE
                armscii8_general_ci	armscii8	32	Yes	Yes	1	PAD SPACE
                ascii_bin	ascii	65		Yes	1	PAD SPACE
                ascii_general_ci	ascii	11	Yes	Yes	1	PAD SPACE
                big5_bin	big5	84		Yes	1	PAD SPACE
                big5_chinese_ci	big5	1	Yes	Yes	1	PAD SPACE
                EOD,
            vitoPestUnitSSHServicesDatabaseGetCharsetsTestMysqlCharsets(),
        ],
        [
            'mysql',
            '5.7',
            <<<'EOD'
                Collation	Charset	Id	Default	Compiled	Sortlen	Pad_attribute
                armscii8_bin	armscii8	64		Yes	1	PAD SPACE
                armscii8_general_ci	armscii8	32	Yes	Yes	1	PAD SPACE
                ascii_bin	ascii	65		Yes	1	PAD SPACE
                ascii_general_ci	ascii	11	Yes	Yes	1	PAD SPACE
                big5_bin	big5	84		Yes	1	PAD SPACE
                big5_chinese_ci	big5	1	Yes	Yes	1	PAD SPACE
                EOD,
            vitoPestUnitSSHServicesDatabaseGetCharsetsTestMysqlCharsets(),
        ],
        [
            'mariadb',
            '10.5',
            <<<'EOD'
                Collation	Charset	Id	Default	Compiled	Sortlen	Pad_attribute
                armscii8_bin	armscii8	64		Yes	1	PAD SPACE
                armscii8_general_ci	armscii8	32	Yes	Yes	1	PAD SPACE
                ascii_bin	ascii	65		Yes	1	PAD SPACE
                ascii_general_ci	ascii	11	Yes	Yes	1	PAD SPACE
                big5_bin	big5	84		Yes	1	PAD SPACE
                big5_chinese_ci	big5	1	Yes	Yes	1	PAD SPACE
                EOD,
            vitoPestUnitSSHServicesDatabaseGetCharsetsTestMysqlCharsets(),
        ],
        [
            'mariadb',
            '11.4',
            <<<'EOD'
                Collation	Charset	Id	Default	Compiled	Sortlen
                big5_chinese_ci	big5	1	Yes	Yes	1
                big5_bin	big5	84		Yes	1
                utf8mb4_general_ci	utf8mb4	45		Yes	1
                utf8mb4_bin	utf8mb4	46		Yes	1
                uca1400_ai_ci	NULL	NULL	NULL	Yes	8
                uca1400_ai_cs	NULL	NULL	NULL	Yes	8
                EOD,
            [
                'big5' => [
                    'default' => 'big5_chinese_ci',
                    'list' => [
                        'big5_chinese_ci',
                        'big5_bin',
                    ],
                ],
                'utf8mb4' => [
                    'default' => null,
                    'list' => [
                        'utf8mb4_general_ci',
                        'utf8mb4_bin',
                    ],
                ],
            ],
        ],
        [
            'postgresql',
            '16',
            <<<'EOD'
                 collation  | charset | id | default | compiled | sortlen | pad_attribute
                ------------+---------+----+---------+----------+---------+---------------
                 ucs_basic  | UTF8    |    |         | Yes      |         |
                 C.utf8     | UTF8    |    |         | Yes      |         |
                 en_US.utf8 | UTF8    |    |         | Yes      |         |
                 en_US      | UTF8    |    |         | Yes      |         |
                (4 rows)
                EOD,
            [
                'UTF8' => [
                    'default' => null,
                    'list' => [
                        'ucs_basic',
                        'C.utf8',
                        'en_US.utf8',
                        'en_US',
                    ],
                ],
            ],
        ],
        [
            'postgresql',
            '18',
            <<<'EOD'
                 collation   | charset | id | default | compiled | sortlen | pad_attribute
                -------------+---------+----+---------+----------+---------+---------------
                 C           | UTF8    |    |         | Yes      |         |
                 POSIX       | UTF8    |    |         | Yes      |         |
                 ucs_basic   | UTF8    |    |         | Yes      |         |
                 pg_c_utf8   | UTF8    |    |         | Yes      |         |
                 unicode     | UTF8    |    |         | Yes      |         |
                 en-US-x-icu | UTF8    |    |         | Yes      |         |
                 en_US.utf8  | UTF8    |    |         | Yes      |         |
                (7 rows)
                EOD,
            [
                'UTF8' => [
                    'default' => null,
                    'list' => [
                        'C',
                        'POSIX',
                        'ucs_basic',
                        'pg_c_utf8',
                        'unicode',
                        'en-US-x-icu',
                        'en_US.utf8',
                    ],
                ],
            ],
        ],
    ];
});
