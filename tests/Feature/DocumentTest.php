<?php

use App\Models\Document;

function documentPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'invoice',
        'number' => 'INV-2026-0001',
        'status' => 'draft',
        'theme' => 'modern',
        'currency' => 'USD',
        'issue_date' => '2026-11-06',
        'due_date' => '2026-11-20',
        'client_name' => 'Acme Ltd',
        'client_email' => 'billing@acme.test',
        'client_phone' => '',
        'client_address' => '1 Market Street',
        'items' => [
            [
                'description' => 'Bespoke suit',
                'quantity' => 2,
                'unit_price' => 100,
                'tax_rate' => 10,
            ],
        ],
        'discount_type' => 'fixed',
        'discount_value' => 0,
        'amount_paid' => 0,
        'notes' => '',
        'terms' => '',
    ], $overrides);
}

test('a document can be created with calculated totals', function () {
    $response = $this->post(route('documents.store'), documentPayload());

    $document = Document::query()->firstOrFail();

    $response->assertRedirect(route('documents.show', $document));
    expect((float) $document->subtotal)->toBe(200.0)
        ->and((float) $document->tax_amount)->toBe(20.0)
        ->and((float) $document->total)->toBe(220.0)
        ->and($document->share_token)->not->toBeEmpty()
        ->and($document->items)->toHaveCount(1);
});

test('discounts reduce the tax proportionally', function () {
    $this->post(route('documents.store'), documentPayload([
        'discount_type' => 'percent',
        'discount_value' => 50,
    ]));

    $document = Document::query()->firstOrFail();

    expect((float) $document->discount_amount)->toBe(100.0)
        ->and((float) $document->tax_amount)->toBe(10.0)
        ->and((float) $document->total)->toBe(110.0);
});

test('a document validates required fields', function () {
    $this->post(route('documents.store'), documentPayload([
        'client_name' => '',
        'items' => [],
    ]))->assertSessionHasErrors(['client_name', 'items']);
});

test('a document can be updated and duplicated', function () {
    $this->post(route('documents.store'), documentPayload());
    $document = Document::query()->firstOrFail();

    $this->put(route('documents.update', $document), documentPayload([
        'client_name' => 'Updated Client',
        'number' => 'INV-2026-0002',
    ]))->assertRedirect(route('documents.show', $document));

    expect($document->refresh()->client_name)->toBe('Updated Client');

    $this->post(route('documents.duplicate', $document))
        ->assertRedirect();

    expect(Document::query()->count())->toBe(2);
});

test('document status can be changed', function () {
    $this->post(route('documents.store'), documentPayload());
    $document = Document::query()->firstOrFail();

    $this->patch(route('documents.status', $document), ['status' => 'paid'])
        ->assertRedirect();

    $document->refresh();

    expect($document->status)->toBe('paid')
        ->and((float) $document->amount_paid)->toBe(220.0)
        ->and($document->paid_at)->not->toBeNull();
});

test('a document is available through its private share link', function () {
    $this->post(route('documents.store'), documentPayload());
    $document = Document::query()->firstOrFail();

    $this->get(route('documents.public', ['token' => $document->share_token]))
        ->assertOk()
        ->assertSee('INV-2026-0001');

    $this->get(route('documents.public.pdf', ['token' => $document->share_token]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('a pdf can be generated for every theme', function () {
    $this->post(route('documents.store'), documentPayload());

    $document = Document::query()->firstOrFail();

    foreach (['modern', 'classic', 'minimal', 'bold'] as $theme) {
        $document->update(['theme' => $theme]);

        $response = $this->get(route('documents.pdf', $document));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        expect($response->getContent())->toStartWith('%PDF');
    }
});

test('the whatsapp share link targets the client phone when available', function () {
    $this->post(route('documents.store'), documentPayload([
        'client_phone' => '+254 711 111 111',
    ]));

    $document = Document::query()->firstOrFail();

    expect($document->whatsappUrl())->toContain('https://wa.me/254711111111?text=');
});

test('the business profile can be updated', function () {
    $this->post(route('business.update'), [
        'name' => 'Tailor and Co.',
        'email' => 'hello@tailor.test',
        'currency' => 'KES',
        'default_tax_rate' => 16,
        'default_theme' => 'classic',
        'invoice_prefix' => 'INV',
        'quotation_prefix' => 'QUO',
        'receipt_prefix' => 'RCP',
    ])->assertRedirect(route('business.edit'));

    $this->get(route('business.edit'))
        ->assertOk()
        ->assertSee('Tailor and Co.');
});
