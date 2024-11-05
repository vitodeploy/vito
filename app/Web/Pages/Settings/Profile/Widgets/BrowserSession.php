<?php

namespace App\Web\Pages\Settings\Profile\Widgets;

use App\Helpers\Agent;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BrowserSession extends Widget implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static bool $isLazy = false;

    protected static string $view = 'components.infolist';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Browser Sessions')
                    ->description('Manage and log out your active sessions on other browsers and devices.')
                    ->schema([
                        TextEntry::make('session_content')
                            ->hiddenLabel()
                            ->state('If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.'),
                        ...$this->getDynamicSchema(),
                    ])
                    ->footerActions([
                        Action::make('deleteBrowserSessions')
                            ->label('Log Out Other Browser Sessions')
                            ->requiresConfirmation()
                            ->modalHeading('Log Out Other Browser Sessions')
                            ->modalDescription('Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.')
                            ->modalSubmitActionLabel('Log Out Other Browser Sessions')
                            ->form([
                                TextInput::make('password')
                                    ->password()
                                    ->revealable()
                                    ->label('Password')
                                    ->required(),
                            ])
                            ->action(function (array $data) {
                                self::logoutOtherBrowserSessions($data['password']);
                            })
                            ->modalWidth('2xl'),
                    ]),
            ]);
    }

    private function getDynamicSchema(): array
    {
        $sections = [];

        foreach ($this->getSessions() as $session) {
            $sections[] = Section::make()
                ->schema([
                    TextEntry::make('device')
                        ->hiddenLabel()
                        ->icon($session->device['desktop'] ? 'heroicon-o-computer-desktop' : 'heroicon-o-device-phone-mobile')
                        ->state($session->device['platform'].' - '.$session->device['browser']),
                    TextEntry::make('browser')
                        ->hiddenLabel()
                        ->icon('heroicon-o-map-pin')
                        ->state($session->ip_address),
                    TextEntry::make('time')
                        ->hiddenLabel()
                        ->icon('heroicon-o-clock')
                        ->state($session->last_active),
                    TextEntry::make('is_current_device')
                        ->hiddenLabel()
                        ->visible(fn () => $session->is_current_device)
                        ->state('This device')
                        ->color('primary'),
                ])->columns(4);
        }

        return $sections;
    }

    private function getSessions(): array
    {
        if (config(key: 'session.driver') !== 'database') {
            return [];
        }

        return collect(
            value: DB::connection(config(key: 'session.connection'))->table(table: config(key: 'session.table', default: 'sessions'))
                ->where(column: 'user_id', operator: Auth::user()->getAuthIdentifier())
                ->latest(column: 'last_activity')
                ->get()
        )->map(callback: function ($session): object {
            $agent = $this->createAgent($session);

            return (object) [
                'device' => [
                    'browser' => $agent->browser(),
                    'desktop' => $agent->isDesktop(),
                    'mobile' => $agent->isMobile(),
                    'tablet' => $agent->isTablet(),
                    'platform' => $agent->platform(),
                ],
                'ip_address' => $session->ip_address,
                'is_current_device' => $session->id === request()->session()->getId(),
                'last_active' => 'Last seen '.Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
            ];
        })->toArray();
    }

    private function createAgent(mixed $session)
    {
        return tap(
            value: new Agent,
            callback: fn ($agent) => $agent->setUserAgent(userAgent: $session->user_agent)
        );
    }

    private function logoutOtherBrowserSessions($password): void
    {
        if (! Hash::check($password, Auth::user()->password)) {
            Notification::make()
                ->danger()
                ->title('The password you entered was incorrect. Please try again.')
                ->send();

            return;
        }

        Auth::guard()->logoutOtherDevices($password);

        request()->session()->put([
            'password_hash_'.Auth::getDefaultDriver() => Auth::user()->getAuthPassword(),
        ]);

        $this->deleteOtherSessionRecords();

        Notification::make()
            ->success()
            ->title('All other browser sessions have been logged out successfully.')
            ->send();
    }

    private function deleteOtherSessionRecords(): void
    {
        if (config(key: 'session.driver') !== 'database') {
            return;
        }

        DB::connection(config(key: 'session.connection'))->table(table: config(key: 'session.table', default: 'sessions'))
            ->where('user_id', Auth::user()->getAuthIdentifier())
            ->where('id', '!=', request()->session()->getId())
            ->delete();
    }
}
