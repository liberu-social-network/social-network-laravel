<?php

namespace App\Http\Livewire;

use App\Services\ActivityService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ActivityFeed extends Component
{
    public $activities;
    public $limit = 20;

    protected ActivityService $activityService;

    public function boot(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function mount()
    {
        $this->loadActivities();
    }

    public function loadActivities()
    {
        if (Auth::check()) {
            $this->activities = $this->activityService->getActivitiesForUser(
                Auth::id(),
                $this->limit
            );
        } else {
            $this->activities = collect();
        }
    }

    public function loadMore()
    {
        $this->limit += 10;
        $this->loadActivities();
    }

    public function render()
    {
        return view('livewire.activity-feed');
    }
}
