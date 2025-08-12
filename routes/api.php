<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CocoaPriceController;

Route::get('/cacau/preco-diario/{ano}/{mes}/{dia}', [CocoaPriceController::class, 'daily']);
Route::get('/cacau/media-mensal/{ano}/{mes}', [CocoaPriceController::class, 'monthly']);
