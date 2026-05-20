<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UserStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        return [
            Stat::make('Friends', $user->friends_count)
                ->description('Total friends')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success'),
            Stat::make('Followers', $user->followers_count)
                ->description('People following you')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Following', $user->following_count)
                ->description('People you follow')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('info'),
        ];
    }
}
