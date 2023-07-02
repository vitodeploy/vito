<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;

class InstallWordpressCommand extends Command
{
    protected $path;

    protected $domain;

    protected $dbName;

    protected $dbUser;

    protected $dbPass;

    protected $dbHost;

    protected $dbPrefix;

    protected $username;

    protected $password;

    protected $email;

    protected $title;

    /**
     * ComposerInstallCommand constructor.
     */
    public function __construct($path, $domain, $dbName, $dbUser, $dbPass, $dbHost, $dbPrefix, $username, $password, $email, $title)
    {
        $this->path = $path;
        $this->domain = $domain;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
        $this->dbHost = $dbHost;
        $this->dbPrefix = $dbPrefix;
        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->title = $title;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/wordpress/install.sh'));
    }

    public function content(string $os): string
    {
        $command = $this->file($os);

        $command = str_replace('__path__', $this->path, $command);
        $command = str_replace('__domain__', $this->domain, $command);
        $command = str_replace('__db_name__', $this->dbName, $command);
        $command = str_replace('__db_user__', $this->dbUser, $command);
        $command = str_replace('__db_pass__', $this->dbPass, $command);
        $command = str_replace('__db_host__', $this->dbHost, $command);
        $command = str_replace('__db_prefix__', $this->dbPrefix, $command);
        $command = str_replace('__username__', $this->username, $command);
        $command = str_replace('__password__', $this->password, $command);
        $command = str_replace('__title__', $this->title, $command);

        return str_replace('__email__', $this->email, $command);
    }
}
