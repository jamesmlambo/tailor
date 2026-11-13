<?php

namespace App\Support;

class Currency
{
    /**
     * Currencies supported by the invoicing studio.
     *
     * @var array<string, string>
     */
    public const OPTIONS = [
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'KES' => 'Kenyan Shilling',
        'NGN' => 'Nigerian Naira',
        'ZAR' => 'South African Rand',
        'GHS' => 'Ghanaian Cedi',
        'TZS' => 'Tanzanian Shilling',
        'UGX' => 'Ugandan Shilling',
        'INR' => 'Indian Rupee',
        'AED' => 'UAE Dirham',
        'CAD' => 'Canadian Dollar',
        'AUD' => 'Australian Dollar',
    ];

    /**
     * @var array<string, string>
     */
    private const SYMBOLS = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'KES' => 'KSh',
        'NGN' => '₦',
        'ZAR' => 'R',
        'GHS' => 'GH₵',
        'TZS' => 'TSh',
        'UGX' => 'USh',
        'INR' => '₹',
        'AED' => 'د.إ',
        'CAD' => 'C$',
        'AUD' => 'A$',
    ];

    public static function symbol(?string $code): string
    {
        $code = strtoupper((string) $code);

        return self::SYMBOLS[$code] ?? $code.' ';
    }

    public static function format(float|int|string|null $amount, ?string $code): string
    {
        return self::symbol($code).number_format((float) $amount, 2);
    }

    public static function isSupported(?string $code): bool
    {
        return array_key_exists(strtoupper((string) $code), self::OPTIONS);
    }
}
