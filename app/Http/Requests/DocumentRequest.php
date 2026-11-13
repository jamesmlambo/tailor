<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['description'] ?? '')) !== '')
            ->values()
            ->all();

        $this->merge([
            'items' => $items,
            'discount_type' => $this->input('discount_type') ?: 'fixed',
            'discount_value' => $this->input('discount_value') ?: 0,
            'amount_paid' => $this->input('amount_paid') ?: 0,
            'tax_number' => $this->input('tax_number'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Document|null $document */
        $document = $this->route('document');
        $type = (string) $this->input('type', $document?->type ?? 'invoice');

        return [
            'type' => ['required', Rule::in(array_keys(Document::TYPES))],
            'number' => [
                'required', 'string', 'max:60',
                Rule::unique('documents', 'number')->ignore($document?->id),
            ],
            'status' => ['required', Rule::in(array_keys(Document::statusOptions($type)))],
            'theme' => ['required', Rule::in(array_keys(Document::themeOptions()))],
            'currency' => ['required', 'string', 'size:3', Rule::in(array_keys(\App\Support\Currency::OPTIONS))],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'client_name' => ['required', 'string', 'max:160'],
            'client_email' => ['nullable', 'email', 'max:160'],
            'client_phone' => ['nullable', 'string', 'max:60'],
            'client_address' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * Persist the document and its line items in one transaction.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function saveDocument(?Document $document = null): Document
    {
        $data = $this->validated();
        $totals = Document::calculateTotals(
            $data['items'],
            $data['discount_type'],
            $data['discount_value'],
        );

        $business = \App\Models\BusinessProfile::current();

        $amountPaid = (float) ($data['amount_paid'] ?? 0);

        if ($data['type'] === 'receipt' && $amountPaid <= 0) {
            $amountPaid = $totals['total'];
        }

        $document ??= new Document;
        $document->fill([
            'type' => $data['type'],
            'number' => $data['number'],
            'status' => $data['status'],
            'theme' => $data['theme'],
            'currency' => strtoupper($data['currency']),
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'] ?? null,
            'client_name' => $data['client_name'],
            'client_email' => $data['client_email'] ?? null,
            'client_phone' => $data['client_phone'] ?? null,
            'client_address' => $data['client_address'] ?? null,
            'subtotal' => $totals['subtotal'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'discount_amount' => $totals['discount_amount'],
            'tax_amount' => $totals['tax_amount'],
            'total' => $totals['total'],
            'amount_paid' => min($amountPaid, $totals['total']),
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? null,
            'business_snapshot' => $business->snapshot(),
        ]);

        if ($data['status'] === 'paid') {
            $document->paid_at ??= now();
            $document->amount_paid = $totals['total'];
        }

        if (in_array($data['status'], ['sent', 'accepted', 'declined', 'issued', 'void'], true)) {
            $document->sent_at ??= now();
        }

        $document->save();

        $document->items()->delete();

        foreach ($totals['lines'] as $index => $line) {
            $document->items()->create([
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'tax_rate' => $line['tax_rate'],
                'line_total' => $line['line_total'],
                'position' => $index,
            ]);
        }

        return $document->refresh();
    }
}
