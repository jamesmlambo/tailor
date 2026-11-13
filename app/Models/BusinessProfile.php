<?php

namespace App\Models;

use App\Support\Currency;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'email', 'phone', 'website', 'address', 'city', 'country',
    'tax_number', 'logo_path', 'currency', 'default_tax_rate', 'default_theme',
    'payment_terms', 'bank_details', 'notes', 'invoice_prefix',
    'quotation_prefix', 'receipt_prefix',
])]
class BusinessProfile extends Model
{
    public const THEMES = [
        'modern' => [
            'label' => 'Modern',
            'description' => 'Clean layout with an indigo accent.',
            'accent' => '#4f46e5',
            'swatches' => ['#4f46e5', '#eef2ff', '#111827'],
        ],
        'classic' => [
            'label' => 'Classic',
            'description' => 'Timeless serif type with navy rules.',
            'accent' => '#1e3a5f',
            'swatches' => ['#1e3a5f', '#f8f5ef', '#0f172a'],
        ],
        'minimal' => [
            'label' => 'Minimal',
            'description' => 'Lots of whitespace, crisp black type.',
            'accent' => '#111111',
            'swatches' => ['#111111', '#f5f5f4', '#78716c'],
        ],
        'bold' => [
            'label' => 'Bold',
            'description' => 'Dark header with a warm orange accent.',
            'accent' => '#f97316',
            'swatches' => ['#111827', '#f97316', '#fff7ed'],
        ],
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'name' => '',
            'currency' => 'USD',
            'default_tax_rate' => 0,
            'default_theme' => 'modern',
        ]);
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    public function initials(): string
    {
        $name = trim((string) $this->name);

        if ($name === '') {
            return 'B';
        }

        $words = preg_split('/\s+/', $name) ?: [];

        return Str::upper(collect($words)->take(2)->map(fn (string $word) => Str::substr($word, 0, 1))->implode(''));
    }

    /**
     * A stable copy of the business details used on generated documents.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'tax_number' => $this->tax_number,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logoUrl(),
            'currency' => $this->currency,
            'bank_details' => $this->bank_details,
            'payment_terms' => $this->payment_terms,
            'notes' => $this->notes,
            'initials' => $this->initials(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function currencyOptions(): array
    {
        return Currency::OPTIONS;
    }
}
