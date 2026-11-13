<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Document;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $business = BusinessProfile::current();

        $paidInvoices = Document::query()
            ->where('type', 'invoice')
            ->where('status', 'paid');

        $outstandingInvoices = Document::query()
            ->where('type', 'invoice')
            ->whereIn('status', ['sent', 'overdue']);

        $quotes = Document::query()->where('type', 'quotation');

        $recent = Document::query()
            ->with('items')
            ->latest('issue_date')
            ->latest('id')
            ->limit(6)
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

        $typeBreakdown = collect(Document::TYPES)->map(fn (string $label, string $type) => [
            'type' => $type,
            'label' => Document::TYPE_PLURALS[$type],
            'count' => Document::query()->where('type', $type)->count(),
            'total' => (float) Document::query()->where('type', $type)->sum('total'),
        ])->values();

        return Inertia::render('Dashboard', [
            'business' => [
                ...$business->snapshot(),
                'default_currency' => $business->currency,
            ],
            'needsProfile' => trim((string) $business->name) === '',
            'stats' => [
                'paid' => (float) (clone $paidInvoices)->sum('total'),
                'paid_count' => (clone $paidInvoices)->count(),
                'outstanding' => (float) (clone $outstandingInvoices)->sum('total'),
                'outstanding_count' => (clone $outstandingInvoices)->count(),
                'quotations' => (float) (clone $quotes)->whereIn('status', ['draft', 'sent'])->sum('total'),
                'quotations_count' => (clone $quotes)->count(),
                'this_month' => (float) Document::query()
                    ->where('type', 'invoice')
                    ->where('status', 'paid')
                    ->whereYear('issue_date', now()->year)
                    ->whereMonth('issue_date', now()->month)
                    ->sum('total'),
            ],
            'recent' => $recent,
            'typeBreakdown' => $typeBreakdown,
            'currency' => $business->currency,
            'defaultTaxRate' => (float) $business->default_tax_rate,
        ]);
    }
}
