<?php

namespace App\SSHCommands\Wordpress;

use App\SSHCommands\Command;

class UpdateWordpressCommand extends Command
{
    public function __construct(
        protected string $path,
        protected string $url,
        protected string $username,
        protected string $password,
        protected string $email,
        protected string $title
    ) {
    }

    public function file(): string
    {
        return '';
    }

    public function content(): string
    {
        $command = '';
        if ($this->title) {
            $command .= 'wp --path='.$this->path.' option update blogname "'.addslashes($this->title).'"'."\n";
        }
        if ($this->url) {
            $command .= 'wp --path='.$this->path.' option update siteurl "'.addslashes($this->url).'"'."\n";
            $command .= 'wp --path='.$this->path.' option update home "'.addslashes($this->url).'"'."\n";
        }

        return $command;
    }
}
