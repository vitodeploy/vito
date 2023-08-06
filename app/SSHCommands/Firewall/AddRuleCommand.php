<?php

namespace App\SSHCommands\Firewall;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class AddRuleCommand extends Command
{
    use CommandContent;

    public function __construct(
        protected string $provider,
        protected string $type,
        protected string $protocol,
        protected string $port,
        protected string $source,
        protected ?string $mask = null
    ) {
    }

    public function file(): string
    {
        return File::get(resource_path(sprintf("commands/firewall/%s/add-rule.sh", $this->provider)));
    }
}
