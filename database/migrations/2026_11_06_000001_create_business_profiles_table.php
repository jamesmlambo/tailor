<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('default_tax_rate', 5, 2)->default(0);
            $table->string('default_theme', 20)->default('modern');
            $table->text('payment_terms')->nullable();
            $table->text('bank_details')->nullable();
            $table->text('notes')->nullable();
            $table->string('invoice_prefix', 12)->default('INV');
            $table->string('quotation_prefix', 12)->default('QUO');
            $table->string('receipt_prefix', 12)->default('RCP');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
