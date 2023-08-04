<?php

namespace App\SSHCommands\Wordpress;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class InstallWordpressCommand extends Command
{
    public function __construct(
        protected string $path,
        protected string $domain,
        protected string $dbName,
        protected string $dbUser,
        protected string $dbPass,
        protected string $dbHost,
        protected string $dbPrefix,
        protected string $username,
        protected string $password,
        protected string $email,
        protected string $title
    ) {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/wordpress/install.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__path__', $this->path)
            ->replace('__domain__', $this->domain)
            ->replace('__db_name__', $this->dbName)
            ->replace('__db_user__', $this->dbUser)
            ->replace('__db_pass__', $this->dbPass)
            ->replace('__db_host__', $this->dbHost)
            ->replace('__db_prefix__', $this->dbPrefix)
            ->replace('__username__', $this->username)
            ->replace('__password__', $this->password)
            ->replace('__title__', $this->title)
            ->replace('__email__', $this->email)
            ->toString();
    }
}
