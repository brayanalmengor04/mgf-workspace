<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::view('/offline', 'offline')->name('pwa.offline');

Route::get('/budgets/{budgetPlan}/pdf', \App\Http\Controllers\BudgetPdfDownloadController::class)
    ->middleware('signed')
    ->name('budgets.pdf.signed');

Route::get('/quotes/{quote}/pdf', \App\Http\Controllers\QuotePdfDownloadController::class)
    ->middleware('signed')
    ->name('quotes.pdf.signed');
