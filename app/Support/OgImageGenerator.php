<?php

namespace App\Support;

use GdImage;

/**
 * Renders a 1200×630 "yellow pages ad" social share card with GD. The headline
 * is auto-sized and word-wrapped to fit. Fonts are bundled (OFL) so this works
 * the same everywhere, not just on macOS.
 */
class OgImageGenerator
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    private string $display;

    private string $body;

    public function __construct()
    {
        $this->display = resource_path('fonts/Anton-Regular.ttf');
        $this->body = resource_path('fonts/Figtree-Bold.ttf');
    }

    public function render(string $headline, string $eyebrow = 'WORKPLACE RANT'): string
    {
        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $yellow = imagecolorallocate($img, 255, 209, 26);
        $black = imagecolorallocate($img, 17, 17, 17);

        // Yellow field with a thick black border frame.
        imagefilledrectangle($img, 0, 0, self::WIDTH, self::HEIGHT, $black);
        imagefilledrectangle($img, 16, 16, self::WIDTH - 17, self::HEIGHT - 17, $yellow);

        $margin = 80;

        // --- Masthead: black bar with the wordmark ---
        imagefilledrectangle($img, 16, 16, self::WIDTH - 17, 132, $black);
        imagettftext($img, 44, 0, $margin, 100, $yellow, $this->display, 'YELLING PAGES');
        $this->rightText($img, 'yellingpages.com', 26, 96, $yellow, self::WIDTH - $margin);

        // --- Eyebrow ---
        imagettftext($img, 26, 0, $margin, 215, $black, $this->body, strtoupper($eyebrow));

        // --- Auto-fit headline ---
        $maxWidth = self::WIDTH - 2 * $margin;
        $contentTop = 250;
        $contentBottom = 520;
        $available = $contentBottom - $contentTop;

        [$size, $lines] = $this->fitHeadline($headline, $maxWidth, $available);

        $lineHeight = (int) round($size * 1.2);
        $blockHeight = count($lines) * $lineHeight;
        $offsetTop = $contentTop + (int) max(0, ($available - $blockHeight) / 2);

        foreach ($lines as $i => $line) {
            $baseline = $offsetTop + $size + ($i * $lineHeight);
            imagettftext($img, $size, 0, $margin, $baseline, $black, $this->display, $line);
        }

        // --- Footer slogan ---
        imagettftext($img, 30, 0, $margin, self::HEIGHT - 60, $black, $this->body, 'let your finger do the talking');

        ob_start();
        imagepng($img);
        $data = (string) ob_get_clean();
        imagedestroy($img);

        return $data;
    }

    /**
     * Pick the largest font size (down to a floor) that wraps to a reasonable
     * number of lines and fits the available height.
     *
     * @return array{0: int, 1: list<string>}
     */
    private function fitHeadline(string $headline, int $maxWidth, int $available): array
    {
        $lines = [];
        $size = 44;

        for ($candidate = 92; $candidate >= 44; $candidate -= 4) {
            $wrapped = $this->wrap($headline, $candidate, $maxWidth);
            $lineHeight = $candidate * 1.2;

            if (count($wrapped) <= 5 && count($wrapped) * $lineHeight <= $available) {
                return [$candidate, $wrapped];
            }

            $lines = $wrapped;
            $size = $candidate;
        }

        // Headline is very long — keep the smallest size, cap at 6 lines.
        if (count($lines) > 6) {
            $lines = array_slice($lines, 0, 6);
            $lines[5] = rtrim($lines[5]).'…';
        }

        return [$size, $lines];
    }

    /**
     * Greedy word-wrap measured with the display font.
     *
     * @return list<string>
     */
    private function wrap(string $text, int $size, int $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $try = $current === '' ? $word : "{$current} {$word}";
            $box = imagettfbbox($size, 0, $this->display, $try);
            $width = abs($box[2] - $box[0]);

            if ($width <= $maxWidth || $current === '') {
                $current = $try;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function rightText(GdImage $img, string $text, int $size, int $baseline, int $color, int $right): void
    {
        $box = imagettfbbox($size, 0, $this->body, $text);
        $width = abs($box[2] - $box[0]);
        imagettftext($img, $size, 0, $right - $width, $baseline, $color, $this->body, $text);
    }
}
