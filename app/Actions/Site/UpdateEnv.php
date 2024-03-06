<?php

namespace App\Actions\Site;

use App\Models\Site;
use App\SSHCommands\System\EditFileCommand;

class UpdateEnv
{
    public function update(Site $site, array $input): void
    {
        $typeData = $site->type_data;
        $typeData['env'] = $input['env'];
        $site->type_data = $typeData;
        $site->save();

        $site->server->ssh()->exec(
            new EditFileCommand(
                $site->path.'/.env',
                $site->env
            )
        );
    }
}
