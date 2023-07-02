<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpdatePHPSettingsCommand extends Command
{
    /**
     * @var string
     */
    protected $version;

    protected $variable;

    protected $value;

    /**
     * InstallPHPCommand constructor.
     */
    public function __construct($version, $variable, $value)
    {
        $this->version = $version;
        $this->variable = $variable;
        $this->value = $value;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/update-php-settings.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__version__', $this->version, $this->file($os));
        $command = Str::replace('__variable__', $this->variable, $command);

        return Str::replace('__value__', $this->value, $command);
    }
}
