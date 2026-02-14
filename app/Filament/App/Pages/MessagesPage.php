<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class MessagesPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static string $view = 'filament.app.pages.messages';

    protected static ?string $title = 'Messages';

    protected static ?string $navigationLabel = 'Messages';

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
