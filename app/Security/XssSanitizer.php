<?php

namespace App\Security;

class XssSanitizer
{
    /**
     * XSS patterns to filter.
     */
    protected static array $patterns = [
        '/<script\b[^>]*>(.*?)<\/script>/is',
        '/<script\b[^>]*\/>/i',
        '/on\w+\s*=\s*(["\']).*?\1/i',
        '/on\w+\s*=\s*[^"\'\s>]+/i',
        '/javascript\s*:\s*/i',
        '/vbscript\s*:\s*/i',
        '/expression\s*\(/i',
        '/<iframe\b[^>]*>/i',
        '/<embed\b[^>]*>/i',
        '/<object\b[^>]*>/i',
        '/<link\b[^>]*>/i',
        '/<style\b[^>]*>(.*?)<\/style>/is',
        '/<meta\b[^>]*>/i',
        '/<base\b[^>]*>/i',
        '/<\s*form\b[^>]*>/i',
        '/document\s*\.\s*cookie/i',
        '/&\s*#\s*\d+\s*;/i',
        '/data\s*:\s*text\/html/i',
    ];

    /**
     * Sanitize input by removing common XSS vectors.
     *
     * @param  string  $input
     * @return string
     */
    public static function sanitize(string $input): string
    {
        $output = $input;

        foreach (self::$patterns as $pattern) {
            $output = preg_replace($pattern, '', $output);
        }

        // Strip null bytes
        $output = str_replace("\0", '', $output);

        return trim($output);
    }
}
