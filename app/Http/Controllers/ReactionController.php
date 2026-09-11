<?php

namespace App\Http\Controllers;

use App\Enums\ReactionType;
use App\Models\Rant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReactionController extends Controller
{
    public function store(Request $request, Rant $rant): RedirectResponse
    {
        abort_unless($rant->isApproved(), 404);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(ReactionType::class)],
        ]);

        $type = ReactionType::from($validated['type']);
        $existing = $rant->reactions()->where('user_id', $request->user()->id)->first();

        if ($existing && $existing->type === $type) {
            // Clicking your current reaction again clears it.
            $existing->delete();
        } elseif ($existing) {
            // Switch to the newly chosen emoji.
            $existing->update(['type' => $type]);
        } else {
            $rant->reactions()->create([
                'user_id' => $request->user()->id,
                'type' => $type,
            ]);
        }

        return back();
    }
}
