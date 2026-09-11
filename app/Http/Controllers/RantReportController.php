<?php

namespace App\Http\Controllers;

use App\Models\Rant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RantReportController extends Controller
{
    public function store(Request $request, Rant $rant): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:100'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $rant->reports()->create([
            'user_id' => $request->user()?->id,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
        ]);

        return back()->with('success', 'Thanks — a moderator will take a look.');
    }
}
