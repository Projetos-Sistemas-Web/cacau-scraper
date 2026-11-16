<?php

use App\Http\Controllers\CocoaPriceController;
use Illuminate\Support\Facades\Route;

Route::get('/cacau/cidades', [CocoaPriceController::class, 'cities']);
Route::get('/cacau/preco-diario/{ano}/{mes}/{dia}', [CocoaPriceController::class, 'daily']);
Route::get('/cacau/media-mensal/{ano}/{mes}', [CocoaPriceController::class, 'monthly']);
