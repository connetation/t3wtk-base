<?php

declare(strict_types=1);

namespace Connetation\T3wtkBase\Utility;

/**
 * Shared phone-number handling for every `tel:` link across conn/* packages.
 * A single source of truth so `href="tel:..."` is never built from a raw,
 * human-formatted phone string (which can embed spaces, dashes, extensions,
 * or a "(0)" trunk-prefix hint that some dialers mishandle).
 */
final class PhoneUtility
{
    /**
     * Reduces a human-formatted phone number to a dial-safe string for `tel:` links:
     * drops the "(0)" trunk-prefix hint used in AT/DE numbers (must not be dialed
     * alongside a country code), then strips everything but a leading "+" and digits.
     *
     * The original, human-readable string (e.g. "+43 (0) 662 123 456 - 7") should
     * still be used for on-screen display — this is only for the `href` value.
     */
    public static function sanitizeForDialing(string $phone): string
    {
        if (trim($phone) === '') {
            return '';
        }

        $phone = preg_replace('/\(\s*0\s*\)/', '', $phone) ?? $phone;
        $phone = preg_replace('/[^\d+]/', '', $phone) ?? $phone;

        return preg_replace('/(?!^)\+/', '', $phone) ?? $phone;
    }
}
