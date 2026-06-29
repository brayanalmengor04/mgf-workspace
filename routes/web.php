<?php

use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
