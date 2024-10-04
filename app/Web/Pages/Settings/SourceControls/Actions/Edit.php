<?php

namespace App\Web\Pages\Settings\SourceControls\Actions;

use App\Actions\SourceControl\EditSourceControl;
use App\Models\SourceControl;
use Exception;
use Filament\Notifications\Notification;

class Edit
{
    public static function form(): array
    {
        return Create::form();
    }

    /**
     * @throws Exception
     */
    public static function action(SourceControl $provider, array $data): void
    {
        try {
            app(EditSourceControl::class)->edit($provider, auth()->user(), $data);
        } catch (Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
