<?php

namespace App\Actions\PHP;

use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InstallPHPExtension
{
    /**
     * @throws ValidationException
     */
    public function handle(Service $service, array $input): Service
    {
        $typeData = $service->type_data;
        $typeData['extensions'] = $typeData['extensions'] ?? [];
        $service->type_data = $typeData;
        $service->save();

        $this->validate($service, $input);

        $service->handler()->installExtension($input['name']);

        return $service;
    }

    /**
     * @throws ValidationException
     */
    private function validate(Service $service, array $input): void
    {
        Validator::make($input, [
            'name' => [
                'required',
                'in:'.implode(',', config('core.php_extensions')),
            ],
        ])->validateWithBag('installPHPExtension');

        if (in_array($input['name'], $service->type_data['extensions'])) {
            throw ValidationException::withMessages(
                ['name' => __('This extension already installed')]
            )->errorBag('installPHPExtension');
        }
    }
}
