<?php

namespace App\Http\Controllers;

use App\Models\Rant;
use App\Support\OgImageGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class OgImageController extends Controller
{
    public function __construct(private readonly OgImageGenerator $generator) {}

    public function rant(Rant $rant): Response
    {
        abort_unless($rant->isApproved(), 404);

        // Cache key includes a title hash so edits regenerate the card.
        $path = "og/rants/{$rant->id}-".substr(sha1($rant->title), 0, 8).'.png';

        return $this->serve($path, fn () => $this->generator->render($rant->title));
    }

    public function default(): Response
    {
        return $this->serve(
            'og/default.png',
            fn () => $this->generator->render(
                'Workplace rants, names swapped to protect the guilty.',
                'let your finger do the talking',
            ),
        );
    }

    /**
     * @param  callable(): string  $make
     */
    private function serve(string $path, callable $make): Response
    {
        if (! Storage::exists($path)) {
            Storage::put($path, $make());
        }

        return response(Storage::get($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
