<?php

namespace App\Helpers;

class TextHelper
{
    public static function getDefaultNames(): array
    {
        return [
            'Untitled Section',
            'Untitled Project',
            'Untitled Chapter',
            'Untitled Notes',
            'Unnamed Character',
            'New Character', // legacy support
            'Personal Identity',
            'Physical Appearance',
            'Gender',
            'Age',
            'Place of Birth',
            'Date of Birth',
            'Height',
            'Weight',
            'Blood Type',
            'Hair Color',
            'Eye Color',
            'Skin Color',
            'Parent',
            'Sibling',
            'Friend',
            'Enemy',
            'Neighbor',
            'Lover',
        ];
    }

    public static function localizeDefaultName(?string $name): string
    {
        if (! $name) {
            return '';
        }

        if (preg_match('/^Draft\s+(\d+)$/i', $name, $matches)) {
            return __('Draft').' '.$matches[1];
        }

        foreach (self::getDefaultNames() as $default) {
            if ($name === $default) {
                return __($default);
            }
            if (preg_match('/^('.preg_quote($default, '/').')\s*\((\d+)\)$/', $name, $matches)) {
                return __($default).' ('.$matches[2].')';
            }
        }

        return $name;
    }

    public static function normalizeDefaultName(?string $name): ?string
    {
        if (! $name) {
            return $name;
        }

        $localizedDraft = __('Draft');
        if (preg_match('/^'.preg_quote($localizedDraft, '/').'\s+(\d+)$/i', $name, $matches)) {
            return 'Draft '.$matches[1];
        }

        foreach (self::getDefaultNames() as $default) {
            $localized = __($default);
            if ($name === $localized) {
                return $default;
            }
            if (preg_match('/^('.preg_quote($localized, '/').')\s*\((\d+)\)$/', $name, $matches)) {
                return $default.' ('.$matches[2].')';
            }
        }

        return $name;
    }

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
     * Generate a unique name for a new item, following file-explorer convention:
     *   "Untitled Project", "Untitled Project (1)", "Untitled Project (2)", …
     *
     * @param  string  $base  The base default name, e.g. "Untitled Project".
     * @param  callable  $existing  A callable that returns an iterable of existing
     *                              title strings to check against. Receives no args.
     */
    public static function uniqueName(string $base, callable $existing): string
    {
        $titles = collect($existing())->map(function ($t) {
            $normalized = self::normalizeDefaultName((string) $t);

            return strtolower($normalized);
        });

        if (! $titles->contains(strtolower($base))) {
            return $base;
        }

        $counter = 1;
        while ($titles->contains(strtolower("{$base} ({$counter})"))) {
            $counter++;
        }

        return "{$base} ({$counter})";
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
