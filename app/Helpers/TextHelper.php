<?php

namespace App\Helpers;

class TextHelper
{
    /**
     * Strip HTML tags and normalize whitespace.
     */
    public static function stripHtmlToText(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Convert block elements to newlines to prevent words from sticking together
        $html = preg_replace('/<(br|\/p|\/div|\/h[1-6]|\/li|\/tr|\/blockquote|\/pre)[^>]*>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\xC2\xA0", '&nbsp;', '&#160;', '&amp;nbsp;'], ' ', $text);

        // Normalize whitespace
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return trim($text);
    }

    /**
     * Calculate word count of HTML content.
     */
    public static function wordCount(?string $html): int
    {
        $text = self::stripHtmlToText($html);

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text));
    }

    /**
     * Extract a certain number of sentences from HTML content.
     */
    public static function extractSentences(?string $html, int $count = 2): ?string
    {
        $text = self::stripHtmlToText($html);
        if ($text === '') {
            return null;
        }

        if (preg_match_all('/[^.!?\r\n]+[.!?]?/', $text, $matches) && ! empty($matches[0])) {
            $sentences = [];
            foreach ($matches[0] as $match) {
                $cleaned = trim($match);
                if ($cleaned !== '') {
                    $sentences[] = $cleaned;
                }
            }
            if (! empty($sentences)) {
                return implode(' ', array_slice($sentences, 0, $count));
            }
        }

        return $text;
    }
}
