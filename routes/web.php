<?php

use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('business', [BusinessProfileController::class, 'edit'])->name('business.edit');
Route::post('business', [BusinessProfileController::class, 'update'])->name('business.update');

Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');

Route::get('invoices', [DocumentController::class, 'index'])->defaults('type', 'invoice')->name('invoices.index');
Route::get('quotations', [DocumentController::class, 'index'])->defaults('type', 'quotation')->name('quotations.index');
Route::get('receipts', [DocumentController::class, 'index'])->defaults('type', 'receipt')->name('receipts.index');

Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
Route::get('documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
Route::post('documents/{document}/duplicate', [DocumentController::class, 'duplicate'])->name('documents.duplicate');
Route::patch('documents/{document}/status', [DocumentController::class, 'status'])->name('documents.status');
Route::get('documents/{document}/pdf', [DocumentController::class, 'pdf'])->name('documents.pdf');
Route::post('documents/{document}/email', [DocumentController::class, 'email'])->name('documents.email');

Route::get('s/{token}', [DocumentController::class, 'publicShow'])->name('documents.public');
Route::get('s/{token}/pdf', [DocumentController::class, 'publicPdf'])->name('documents.public.pdf');

require __DIR__.'/settings.php';
