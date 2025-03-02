<?php

namespace Tests\Unit\SSH\Services\Database;

use App\Facades\SSH;
use App\SSH\Services\Database\Database;
use Tests\TestCase;

class UpdateCharsetsTest extends TestCase
{
    /**
     * @dataProvider data
     */
    public function test_update_charsets(string $name, string $version, string $output): void
    {
        $database = $this->server->database();
        $database->name = $name;
        $database->version = $version;
        $database->save();

        SSH::fake($output);

        /** @var Database $databaseHandler */
        $databaseHandler = $database->handler();
        $databaseHandler->updateCharsets();

        $database->refresh();
        $this->assertEquals([
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
                'default' => null,
                'list' => [
                    'big5_bin',
                    'big5_chinese_ci',
                ],
            ],
        ], $database->type_data['charsets']);
    }

    /**
     * @TODO Add more test cases
     *
     * @return array[]
     */
    public static function data(): array
    {
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
                EOD
            ],
        ];
    }
}
