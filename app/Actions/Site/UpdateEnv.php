<?php

namespace App\Actions\Site;

use App\Models\Site;

class UpdateEnv
{
    public function handle(Site $site, array $input): void
    {
        $typeData = $site->type_data;
        $typeData['env'] = $input['env'];
        $site->type_data = $typeData;
        $site->save();

        $site->deployEnv();
    }
}
