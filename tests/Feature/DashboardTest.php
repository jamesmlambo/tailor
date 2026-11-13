<?php

test('guests can visit the dashboard', function () {
    $this->get(route('dashboard'))->assertOk();
});

test('the dashboard shows the business name', function () {
    $this->post(route('business.update'), [
        'name' => 'Tailor and Co.',
        'currency' => 'KES',
        'default_tax_rate' => 16,
        'default_theme' => 'modern',
        'invoice_prefix' => 'INV',
        'quotation_prefix' => 'QUO',
        'receipt_prefix' => 'RCP',
    ])->assertRedirect(route('business.edit'));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Tailor and Co.');
});
