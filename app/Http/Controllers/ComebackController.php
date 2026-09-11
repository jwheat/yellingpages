<?php

namespace App\Http\Controllers;

use App\Comebacks\ComebackGenerator;
use App\Enums\ComebackTone;
use App\Models\Rant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComebackController extends Controller
{
    public function store(Request $request, Rant $rant, ComebackGenerator $generator): JsonResponse
    {
        abort_unless($rant->isApproved(), 404);

        $validated = $request->validate([
            'tone' => ['required', Rule::enum(ComebackTone::class)],
        ]);

        $tone = ComebackTone::from($validated['tone']);
        $result = $generator->generate((string) $rant->scrubbed_body, $tone);

        return response()->json([
            'comeback' => $result['text'],
            'tone' => $tone->value,
            'source' => $result['source'],
        ]);
    }
}
