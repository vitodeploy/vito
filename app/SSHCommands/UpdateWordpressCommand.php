<?php

namespace App\SSHCommands;

class UpdateWordpressCommand extends Command
{
    protected $path;

    protected $url;

    protected $username;

    protected $password;

    protected $email;

    protected $title;

    /**
     * ComposerInstallCommand constructor.
     */
    public function __construct($path, $url, $username, $password, $email, $title)
    {
        $this->path = $path;
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->title = $title;
    }

    public function file(string $os): string
    {
        return '';
    }

    public function content(string $os): string
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
