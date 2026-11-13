<?php

test('the home page shows the create menu and recent documents', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('New document')
        ->assertSee('Recent documents');
});

test('the business setup prompt is shown until details are saved', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Set up your business details');

    $this->post(route('business.update'), [
        'name' => 'Tailor and Co.',
        'currency' => 'KES',
        'default_tax_rate' => 16,
        'default_theme' => 'modern',
        'invoice_prefix' => 'INV',
        'quotation_prefix' => 'QUO',
        'receipt_prefix' => 'RCP',
    ])->assertRedirect(route('business.edit'));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Tailor and Co.')
        ->assertDontSee('Set up your business details');
});
