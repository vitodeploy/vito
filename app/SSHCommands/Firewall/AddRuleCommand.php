<?php

namespace App\SSHCommands\Firewall;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class AddRuleCommand extends Command
{
    use CommandContent;

    /**
     * @var string
     */
    protected $provider;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $protocol;

    /**
     * @var string
     */
    protected $port;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $mask;

    public function __construct($provider, $type, $protocol, $port, $source, $mask)
    {
        $this->provider = $provider;
        $this->type = $type;
        $this->protocol = $protocol;
        $this->port = $port;
        $this->source = $source;
        $this->mask = $mask;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/firewall/'.$this->provider.'/add-rule.sh'));
    }
}
