<?php

namespace App\Filament\App\Resources;

use App\Models\Friendship;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FriendshipResource extends Resource
{
    protected static ?string $model = Friendship::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Friend Requests';

    protected static ?string $navigationGroup = 'Social';

    public static function form(Form $form): Form
    {
        return $form
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
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'accepted',
                        'danger' => 'declined',
                    ]),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('accept')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Friendship $record) => $record->status === 'pending' && $record->addressee_id === auth()->id())
                    ->action(fn (Friendship $record) => $record->update(['status' => 'accepted'])),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Friendship $record) => $record->status === 'pending' && $record->addressee_id === auth()->id())
                    ->action(fn (Friendship $record) => $record->update(['status' => 'declined'])),
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
            'index' => \App\Filament\App\Resources\FriendshipResource\Pages\ListFriendships::route('/'),
            'edit' => \App\Filament\App\Resources\FriendshipResource\Pages\EditFriendship::route('/{record}/edit'),
        ];
    }
}
