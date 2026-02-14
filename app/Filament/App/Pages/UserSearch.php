<?php

namespace App\Filament\App\Pages;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserSearch extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string $view = 'filament.app.pages.user-search';

    protected static ?string $navigationLabel = 'Search Users';

    protected static ?string $navigationGroup = 'Social';

    public ?string $search = '';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->when($this->search, function (Builder $query) {
                        $query->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%");
                    })
                    ->where('id', '!=', Auth::id())
            )
            ->columns([
                ImageColumn::make('profile_photo_url')
                    ->label('Photo')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('friends_count')
                    ->label('Friends')
                    ->badge()
                    ->color('success'),
                TextColumn::make('followers_count')
                    ->label('Followers')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('following_count')
                    ->label('Following')
                    ->badge()
                    ->color('info'),
            ])
            ->actions([
                Tables\Actions\Action::make('send_friend_request')
                    ->label('Add Friend')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (User $record) => !Auth::user()->isFriendWith($record) && !Auth::user()->hasFriendRequestPending($record))
                    ->action(function (User $record) {
                        Auth::user()->sendFriendRequest($record);
                        $this->notify('success', 'Friend request sent!');
                    }),
                Tables\Actions\Action::make('follow')
                    ->label('Follow')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->visible(fn (User $record) => !Auth::user()->isFollowing($record))
                    ->action(function (User $record) {
                        Auth::user()->follow($record);
                        $this->notify('success', 'Now following user!');
                    }),
                Tables\Actions\Action::make('unfollow')
                    ->label('Unfollow')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->visible(fn (User $record) => Auth::user()->isFollowing($record))
                    ->action(function (User $record) {
                        Auth::user()->unfollow($record);
                        $this->notify('success', 'Unfollowed user!');
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ]);
    }

    protected function notify(string $type, string $message): void
    {
        if ($type === 'success') {
            \Filament\Notifications\Notification::make()
                ->success()
                ->title($message)
                ->send();
        }
    }
}
