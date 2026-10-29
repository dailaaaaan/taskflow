<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeamResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    /**
     * List the teams the user belongs to, with their role and team size.
     */
    public function __invoke(#[CurrentUser] User $user): Response
    {
        $teams = $user->teams()
            ->withCount(['memberships', 'boards'])
            ->orderBy('name')
            ->get();

        return Inertia::render('dashboard', [
            'teams' => TeamResource::collection($teams),
        ]);
    }
}
