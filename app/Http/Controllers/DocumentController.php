<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Mail\DocumentMail;
use App\Models\BusinessProfile;
use App\Models\Document;
use App\Services\DocumentPdfService;
use App\Support\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentPdfService $pdf) {}

    public function index(Request $request, ?string $type = null): Response
    {
        $type = array_key_exists((string) $type, Document::TYPES) ? $type : null;
        $search = trim((string) $request->query('search'));
        $status = (string) $request->query('status');

        $query = Document::query()->with('items')->latest('issue_date')->latest('id');

        if ($type) {
            $query->where('type', $type);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('number', 'like', '%'.$search.'%')
                    ->orWhere('client_name', 'like', '%'.$search.'%')
                    ->orWhere('client_email', 'like', '%'.$search.'%');
            });
        }

        $documents = $query->get()->map(fn (Document $document) => $this->serialize($document));

        $statuses = $type
            ? Document::statusOptions($type)
            : collect(Document::STATUSES)
                ->flatMap(fn (array $statuses) => $statuses)
                ->unique()
                ->all();

        $summaryQuery = Document::query();
        if ($type) {
            $summaryQuery->where('type', $type);
        }

        return Inertia::render('documents/Index', [
            'type' => $type,
            'title' => $type ? Document::TYPE_PLURALS[$type] : 'All documents',
            'documents' => $documents,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => collect($statuses)->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
            'summary' => [
                'count' => (clone $summaryQuery)->count(),
                'total' => (float) (clone $summaryQuery)->sum('total'),
                'paid' => (float) (clone $summaryQuery)->whereIn('status', ['paid', 'accepted', 'issued'])->sum('total'),
            ],
            'business' => BusinessProfile::current()->snapshot(),
            'currency' => BusinessProfile::current()->currency,
        ]);
    }

    public function create(Request $request): Response
    {
        $type = (string) $request->query('type', 'invoice');
        $type = array_key_exists($type, Document::TYPES) ? $type : 'invoice';
        $business = BusinessProfile::current();
        $issueDate = now()->format('Y-m-d');

        return Inertia::render('documents/Form', [
            'mode' => 'create',
            'document' => null,
            'defaults' => [
                'type' => $type,
                'number' => Document::nextNumber($type, $issueDate, $this->prefixFor($type, $business)),
                'status' => Document::defaultStatus($type),
                'theme' => $business->default_theme,
                'currency' => $business->currency,
                'issue_date' => $issueDate,
                'due_date' => now()->addDays($type === 'quotation' ? 30 : 14)->format('Y-m-d'),
                'tax_rate' => (float) $business->default_tax_rate,
            ],
            'business' => [
                ...$business->snapshot(),
                'default_tax_rate' => (float) $business->default_tax_rate,
            ],
            'themes' => $this->themes(),
            'currencies' => collect(Currency::OPTIONS)->map(fn (string $label, string $code) => [
                'value' => $code,
                'label' => $code.' — '.$label,
            ])->values(),
            'statusGroups' => collect(Document::STATUSES)->map(fn (array $statuses) => collect($statuses)->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])->values())->all(),
            'typeLabels' => Document::TYPES,
            'typePlurals' => Document::TYPE_PLURALS,
            'nextNumbers' => collect(array_keys(Document::TYPES))
                ->mapWithKeys(fn (string $type) => [
                    $type => Document::nextNumber($type, $issueDate, $this->prefixFor($type, $business)),
                ])
                ->all(),
        ]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $document = $request->saveDocument();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => Document::TYPES[$document->type].' '.$document->number.' generated.',
        ]);

        return to_route('documents.show', $document);
    }

    public function show(Document $document): Response
    {
        return Inertia::render('documents/Show', [
            'document' => $this->serialize($document),
            'statuses' => collect(Document::statusOptions($document->type))->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
            'typeLabels' => Document::TYPES,
        ]);
    }

    public function edit(Document $document): Response
    {
        $document->load('items');
        $business = BusinessProfile::current();

        return Inertia::render('documents/Form', [
            'mode' => 'edit',
            'document' => $this->serialize($document),
            'defaults' => [
                'type' => $document->type,
                'number' => $document->number,
                'status' => $document->status,
                'theme' => $document->theme,
                'currency' => $document->currency,
                'issue_date' => $document->issue_date?->format('Y-m-d'),
                'due_date' => $document->due_date?->format('Y-m-d'),
                'tax_rate' => (float) $business->default_tax_rate,
            ],
            'business' => [
                ...$business->snapshot(),
                'default_tax_rate' => (float) $business->default_tax_rate,
            ],
            'themes' => $this->themes(),
            'currencies' => collect(Currency::OPTIONS)->map(fn (string $label, string $code) => [
                'value' => $code,
                'label' => $code.' — '.$label,
            ])->values(),
            'statusGroups' => collect(Document::STATUSES)->map(fn (array $statuses) => collect($statuses)->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])->values())->all(),
            'typeLabels' => Document::TYPES,
            'typePlurals' => Document::TYPE_PLURALS,
            'nextNumbers' => collect(array_keys(Document::TYPES))
                ->mapWithKeys(fn (string $type) => [
                    $type => Document::nextNumber($type, now()->format('Y-m-d'), $this->prefixFor($type, $business)),
                ])
                ->all(),
        ]);
    }

    public function update(DocumentRequest $request, Document $document): RedirectResponse
    {
        $request->saveDocument($document);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Document updated.']);

        return to_route('documents.show', $document);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $type = $document->type;
        $document->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Document deleted.']);

        return to_route($type.'s.index');
    }

    public function duplicate(Document $document): RedirectResponse
    {
        $copy = $document->replicate(['share_token']);
        $issueDate = now()->format('Y-m-d');
        $business = BusinessProfile::current();

        $copy->fill([
            'number' => Document::nextNumber($document->type, $issueDate, $this->prefixFor($document->type, $business)),
            'status' => Document::defaultStatus($document->type),
            'issue_date' => $issueDate,
            'due_date' => now()->addDays($document->type === 'quotation' ? 30 : 14)->format('Y-m-d'),
            'amount_paid' => 0,
            'sent_at' => null,
            'paid_at' => null,
        ]);
        $copy->share_token = null;
        $copy->save();

        foreach ($document->items as $item) {
            $copy->items()->create($item->only([
                'description', 'quantity', 'unit_price', 'tax_rate', 'line_total', 'position',
            ]));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Document duplicated as '.$copy->number.'.']);

        return to_route('documents.edit', $copy);
    }

    public function status(Request $request, Document $document): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(Document::statusOptions($document->type)))],
        ]);

        $document->status = $data['status'];

        if ($document->status === 'paid') {
            $document->paid_at = now();
            $document->amount_paid = $document->total;
        }

        if (in_array($document->status, ['sent', 'accepted', 'declined', 'issued', 'void'], true)) {
            $document->sent_at ??= now();
        }

        $document->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status updated to '.$document->status.'.']);

        return back();
    }

    public function pdf(Document $document): SymfonyResponse
    {
        return $this->pdf->make($document)->download($this->pdf->filename($document));
    }

    public function email(Request $request, Document $document): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:160'],
        ]);

        try {
            Mail::to($data['email'])->send(new DocumentMail(
                $document,
                $this->pdf->output($document),
                $document->shareUrl(),
            ));

            $document->forceFill(['sent_at' => $document->sent_at ?? now()])->save();

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Email sent to '.$data['email'].'.']);
        } catch (Throwable $e) {
            report($e);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Could not send the email. Check your mail configuration.',
            ]);
        }

        return back();
    }

    public function publicShow(string $token): Response
    {
        $document = Document::query()->with('items')->where('share_token', $token)->firstOrFail();

        return Inertia::render('documents/Public', [
            'document' => $this->serialize($document),
        ]);
    }

    public function publicPdf(string $token): SymfonyResponse
    {
        $document = Document::query()->with('items')->where('share_token', $token)->firstOrFail();

        return $this->pdf->make($document)->download($this->pdf->filename($document));
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Document $document): array
    {
        $document->loadMissing('items');
        $business = $document->businessDetails();

        return [
            ...$document->toArray(),
            'items' => $document->items->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_rate' => (float) $item->tax_rate,
                'line_total' => (float) $item->line_total,
            ])->values()->all(),
            'business' => $business,
            'type_label' => Document::TYPES[$document->type],
            'type_plural' => Document::TYPE_PLURALS[$document->type],
            'status_label' => Document::statusOptions($document->type)[$document->status] ?? ucfirst($document->status),
            'due_label' => $document->dueLabel(),
            'share_url' => $document->shareUrl(),
            'pdf_url' => $document->pdfUrl(),
            'public_pdf_url' => $document->publicPdfUrl(),
            'whatsapp_url' => $document->whatsappUrl(),
            'mailto_url' => $document->mailtoUrl(),
            'balance' => max(0, (float) $document->total - (float) $document->amount_paid),
            'formatted_total' => Currency::format($document->total, $document->currency),
            'is_overdue' => in_array($document->status, ['sent', 'overdue'], true)
                && $document->due_date
                && $document->due_date->isPast(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function themes(): array
    {
        return collect(BusinessProfile::THEMES)
            ->map(fn (array $theme, string $id) => ['id' => $id, ...$theme])
            ->values()
            ->all();
    }

    private function prefixFor(string $type, BusinessProfile $business): string
    {
        return match ($type) {
            'quotation' => $business->quotation_prefix,
            'receipt' => $business->receipt_prefix,
            default => $business->invoice_prefix,
        };
    }
}
