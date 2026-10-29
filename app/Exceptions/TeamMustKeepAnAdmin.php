<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Raised when an operation would leave a team without any admin.
 * It is an expected business rule, not a bug, so it is never reported and
 * renders as a friendly message instead of an error page.
 */
final class TeamMustKeepAnAdmin extends DomainException implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('A team must keep at least one admin. Promote another member first.');
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        Inertia::flash('toast', ['type' => 'error', 'message' => $this->getMessage()]);

        return back();
    }
}
