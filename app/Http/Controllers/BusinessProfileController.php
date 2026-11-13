<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessProfileRequest;
use App\Models\BusinessProfile;
use App\Models\Document;
use App\Support\Currency;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BusinessProfileController extends Controller
{
    public function edit(): Response
    {
        $business = BusinessProfile::current();

        return Inertia::render('business/Edit', [
            'business' => [
                ...$business->toArray(),
                'logo_url' => $business->logoUrl(),
                'initials' => $business->initials(),
            ],
            'themes' => collect(BusinessProfile::THEMES)
                ->map(fn (array $theme, string $id) => ['id' => $id, ...$theme])
                ->values()
                ->all(),
            'currencies' => collect(Currency::OPTIONS)->map(fn (string $label, string $code) => [
                'value' => $code,
                'label' => $code.' — '.$label,
            ])->values(),
            'typeLabels' => Document::TYPES,
        ]);
    }

    public function update(BusinessProfileRequest $request): RedirectResponse
    {
        $business = BusinessProfile::current();

        $business->fill($request->safe()->except(['logo', 'remove_logo']));
        $request->storeLogo($business);
        $business->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Business details saved.']);

        return to_route('business.edit');
    }
}
