<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeamManagementService
{
    /**
     * Assign a user to the default team, or create a personal team if none exists.
     */
    public function assignUserToDefaultTeam(User $user): Team
    {
        $team = Team::first();

        if (! $team) {
            return $this->createPersonalTeamForUser($user);
        }

        if (! $team->hasUser($user)) {
            $team->users()->attach($user, ['role' => 'member']);
        }

        if (! $user->currentTeam) {
            $user->switchTeam($team);
        }

        return $team;
    }

    /**
     * Create a personal team for the given user.
     */
    public function createPersonalTeamForUser(User $user): Team
    {
        $existingPersonalTeam = $user->ownedTeams()->where('personal_team', true)->first();

        if ($existingPersonalTeam) {
            return $existingPersonalTeam;
        }

        $team = DB::transaction(function () use ($user) {
            $team = Team::forceCreate([
                'user_id' => $user->id,
                'name' => explode(' ', $user->name, 2)[0] . "'s Team",
                'personal_team' => true,
            ]);

            $user->teams()->attach($team, ['role' => 'owner']);

            if (! $user->currentTeam) {
                $user->switchTeam($team);
            }

            return $team;
        });

        return $team;
    }
}
