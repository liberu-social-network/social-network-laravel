<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class MessagesPage extends Page
{
    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    #[\Override]
    protected string $view = 'filament.app.pages.messages';

    #[\Override]
    protected static ?string $title = 'Messages';

    #[\Override]
    protected static ?string $navigationLabel = 'Messages';

    #[\Override]
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $unreadCount = \App\Models\Message::where('receiver_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return $unreadCount > 0 ? (string) $unreadCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
