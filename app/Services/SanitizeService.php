<?php

namespace App\Services;

use Stevebauman\Purify\Facades\Purify;

class SanitizeService
{
    public function purify($html)
    {
        $cleaned = Purify::clean($html);

        return $cleaned;
    }

    public function html($html)
    {
        return $this->cleanHtmlWithSpacing($html);
    }

    public function cleanHtmlWithSpacing($html)
    {
        $blockTags = ['a', 'p', 'img', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'blockquote', 'br'];

        foreach ($blockTags as $tag) {
            $html = preg_replace("/<\/{$tag}>/i", "</{$tag}> ", $html);
        }

        $html = preg_replace("/<br\s*\/?>/i", '<br /> ', $html);

        $cleaned = Purify::clean($html);

        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);

        return $cleaned;
    }
}
