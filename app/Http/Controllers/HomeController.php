<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Document;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $business = BusinessProfile::current();

        $recent = Document::query()
            ->latest('issue_date')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'type' => $document->type,
                'type_label' => Document::TYPES[$document->type],
                'number' => $document->number,
                'status' => $document->status,
                'status_label' => Document::statusOptions($document->type)[$document->status] ?? ucfirst($document->status),
                'client_name' => $document->client_name,
                'issue_date' => $document->issue_date?->format('Y-m-d'),
                'total' => (float) $document->total,
                'currency' => $document->currency,
            ]);

        return Inertia::render('Home', [
            'business' => $business->snapshot(),
            'needsProfile' => trim((string) $business->name) === '',
            'recent' => $recent,
        ]);
    }
}
