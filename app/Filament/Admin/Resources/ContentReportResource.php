<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ContentReportResource\Pages;
use App\Models\ContentReport;
use App\Models\Post;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class ContentReportResource extends Resource
{
    protected static ?string $model = ContentReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Moderation';

    protected static ?string $navigationLabel = 'Content Reports';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Details')
                    ->schema([
                        Forms\Components\TextInput::make('reporter.name')
                            ->label('Reporter')
                            ->disabled(),
                        Forms\Components\TextInput::make('reportable_type')
                            ->label('Content Type')
                            ->disabled(),
                        Forms\Components\TextInput::make('reason')
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Moderation')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'reviewing' => 'Reviewing',
                                'resolved' => 'Resolved',
                                'dismissed' => 'Dismissed',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Reporter')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reportable_type')
                    ->label('Content Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Post::class => 'info',
                        Comment::class => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reviewing' => 'info',
                        'resolved' => 'success',
                        'dismissed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewing' => 'Reviewing',
                        'resolved' => 'Resolved',
                        'dismissed' => 'Dismissed',
                    ]),
                Tables\Filters\SelectFilter::make('reportable_type')
                    ->label('Content Type')
                    ->options([
                        Post::class => 'Post',
                        Comment::class => 'Comment',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_content')
                    ->label('View Content')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Reported Content')
                    ->modalContent(fn (ContentReport $record) => view('filament.moderation.view-content', [
                        'content' => $record->reportable,
                        'type' => class_basename($record->reportable_type),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('approve_content')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ContentReport $record) {
                        $content = $record->reportable;
                        $content->update([
                            'moderation_status' => 'approved',
                            'moderated_by' => auth()->id(),
                            'moderated_at' => now(),
                        ]);
                        
                        $record->update([
                            'status' => 'dismissed',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'admin_notes' => 'Content approved - no violation found.',
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Content approved')
                            ->body('The reported content has been approved.')
                            ->send();
                    }),
                Tables\Actions\Action::make('remove_content')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (ContentReport $record) {
                        $content = $record->reportable;
                        $content->update([
                            'moderation_status' => 'rejected',
                            'moderated_by' => auth()->id(),
                            'moderated_at' => now(),
                        ]);
                        
                        $record->update([
                            'status' => 'resolved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'admin_notes' => 'Content removed due to policy violation.',
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Content removed')
                            ->body('The reported content has been removed.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListContentReports::route('/'),
            'view' => Pages\ViewContentReport::route('/{record}'),
            'edit' => Pages\EditContentReport::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? 'warning' : 'success';
    }
}
