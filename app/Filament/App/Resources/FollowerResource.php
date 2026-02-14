<?php

namespace App\Filament\App\Resources;

use App\Models\Follower;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FollowerResource extends Resource
{
    protected static ?string $model = Follower::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Followers';

    protected static ?string $navigationGroup = 'Social';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('follower_id')
                    ->relationship('follower', 'name')
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('following_id')
                    ->relationship('following', 'name')
                    ->required()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $userId = auth()->id();
                return $query->where('follower_id', $userId)
                    ->orWhere('following_id', $userId);
            })
            ->columns([
                Tables\Columns\TextColumn::make('follower.name')
                    ->label('Follower')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('following.name')
                    ->label('Following')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('my_followers')
                    ->label('My Followers')
                    ->query(fn (Builder $query) => $query->where('following_id', auth()->id())),
                Tables\Filters\Filter::make('i_am_following')
                    ->label('I Am Following')
                    ->query(fn (Builder $query) => $query->where('follower_id', auth()->id())),
            ])
            ->actions([
                Tables\Actions\Action::make('unfollow')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->visible(fn (Follower $record) => $record->follower_id === auth()->id())
                    ->requiresConfirmation()
                    ->action(fn (Follower $record) => $record->delete()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\App\Resources\FollowerResource\Pages\ListFollowers::route('/'),
        ];
    }
}
