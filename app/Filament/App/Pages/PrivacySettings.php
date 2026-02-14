<?php

namespace App\Filament\App\Pages;

use App\Models\UserPrivacySetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PrivacySettings extends Page
{
    protected static string $view = 'filament.pages.privacy-settings';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Privacy Settings';

    protected static ?string $title = 'Privacy Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $privacySettings = $user->privacySettings ?? UserPrivacySetting::create(['user_id' => $user->id]);

        $this->form->fill([
            'profile_visibility' => $privacySettings->profile_visibility,
            'show_email' => $privacySettings->show_email,
            'show_birth_date' => $privacySettings->show_birth_date,
            'show_location' => $privacySettings->show_location,
            'allow_friend_requests' => $privacySettings->allow_friend_requests,
            'allow_messages_from_non_friends' => $privacySettings->allow_messages_from_non_friends,
            'show_online_status' => $privacySettings->show_online_status,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Profile Visibility')
                    ->description('Control who can view your profile and personal information')
                    ->schema([
                        Select::make('profile_visibility')
                            ->label('Who can view your profile?')
                            ->options([
                                'public' => 'Everyone (Public)',
                                'friends_only' => 'Friends Only',
                                'private' => 'Only Me (Private)',
                            ])
                            ->required()
                            ->helperText('Determines who can see your profile information'),
                    ]),

                Section::make('Personal Information')
                    ->description('Choose what personal information to display on your profile')
                    ->schema([
                        Toggle::make('show_email')
                            ->label('Show email address')
                            ->helperText('Allow others to see your email address'),

                        Toggle::make('show_birth_date')
                            ->label('Show birth date')
                            ->helperText('Display your birth date on your profile'),

                        Toggle::make('show_location')
                            ->label('Show location')
                            ->helperText('Display your location on your profile'),
                    ]),

                Section::make('Communication Preferences')
                    ->description('Manage how others can interact with you')
                    ->schema([
                        Toggle::make('allow_friend_requests')
                            ->label('Allow friend requests')
                            ->helperText('Let others send you friend requests'),

                        Toggle::make('allow_messages_from_non_friends')
                            ->label('Allow messages from non-friends')
                            ->helperText('Receive messages from people who are not your friends'),

                        Toggle::make('show_online_status')
                            ->label('Show online status')
                            ->helperText('Display when you are online'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = Auth::user();
        $privacySettings = $user->privacySettings ?? UserPrivacySetting::create(['user_id' => $user->id]);

        $privacySettings->update($data);

        Notification::make()
            ->title('Privacy settings updated')
            ->success()
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
