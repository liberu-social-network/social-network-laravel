<?php

namespace App\Filament\App\Resources;

use App\Models\Friendship;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FriendshipResource extends Resource
{
    protected static ?string $model = Friendship::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Friend Requests';

    protected static string|\UnitEnum|null $navigationGroup = 'Social';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('requester_id')
                    ->relationship('requester', 'name')
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('addressee_id')
                    ->relationship('addressee', 'name')
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $userId = auth()->id();
                return $query->where('requester_id', $userId)
                    ->orWhere('addressee_id', $userId);
            })
            ->columns([
                Tables\Columns\TextColumn::make('requester.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('addressee.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'declined' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('accept')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Friendship $record) => $record->status === 'pending' && $record->addressee_id === auth()->id())
                    ->action(fn (Friendship $record) => $record->update(['status' => 'accepted'])),
                Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Friendship $record) => $record->status === 'pending' && $record->addressee_id === auth()->id())
                    ->action(fn (Friendship $record) => $record->update(['status' => 'declined'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => \App\Filament\App\Resources\FriendshipResource\Pages\ListFriendships::route('/'),
            'edit' => \App\Filament\App\Resources\FriendshipResource\Pages\EditFriendship::route('/{record}/edit'),
        ];
    }
}
