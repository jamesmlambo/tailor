<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class BusinessProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'remove_logo' => $this->boolean('remove_logo'),
            'default_tax_rate' => $this->input('default_tax_rate') ?: 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:60'],
            'website' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['boolean'],
            'currency' => ['required', Rule::in(array_keys(\App\Support\Currency::OPTIONS))],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_theme' => ['required', Rule::in(array_keys(\App\Models\Document::themeOptions()))],
            'invoice_prefix' => ['required', 'string', 'max:12', 'alpha_dash'],
            'quotation_prefix' => ['required', 'string', 'max:12', 'alpha_dash'],
            'receipt_prefix' => ['required', 'string', 'max:12', 'alpha_dash'],
            'payment_terms' => ['nullable', 'string', 'max:2000'],
            'bank_details' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Keep stored logos tidy when they are replaced or removed.
     */
    public function storeLogo(\App\Models\BusinessProfile $business): void
    {
        if ($this->boolean('remove_logo') && $business->logo_path) {
            Storage::disk('public')->delete($business->logo_path);
            $business->logo_path = null;
        }

        if ($this->hasFile('logo')) {
            if ($business->logo_path) {
                Storage::disk('public')->delete($business->logo_path);
            }

            $business->logo_path = $this->file('logo')->store('business-logos', 'public');
        }
    }
}
