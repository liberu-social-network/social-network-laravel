<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->maxLength(5000)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('image_url')
                    ->image()
                    ->directory('posts/images')
                    ->disk('public')
                    ->maxSize(10240),
                Forms\Components\FileUpload::make('video_url')
                    ->directory('posts/videos')
                    ->disk('public')
                    ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/x-msvideo'])
                    ->maxSize(51200),
                Forms\Components\Select::make('media_type')
                    ->options([
                        'text' => 'Text',
                        'image' => 'Image',
                        'video' => 'Video',
                        'mixed' => 'Mixed',
                    ])
                    ->required()
                    ->default('text'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('content')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('media_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'image' => 'success',
                        'video' => 'warning',
                        'mixed' => 'info',
                    }),
                Tables\Columns\TextColumn::make('likes_count')
                    ->counts('likes')
                    ->label('Likes'),
                Tables\Columns\TextColumn::make('comments_count')
                    ->counts('comments')
                    ->label('Comments'),
                Tables\Columns\TextColumn::make('shares_count')
                    ->counts('shares')
                    ->label('Shares'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('media_type')
                    ->options([
                        'text' => 'Text',
                        'image' => 'Image',
                        'video' => 'Video',
                        'mixed' => 'Mixed',
                    ]),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'view' => Pages\ViewPost::route('/{record}'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
