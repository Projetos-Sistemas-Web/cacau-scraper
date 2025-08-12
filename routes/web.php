<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CocoaPriceExportController;
use Illuminate\Support\Facades\Route;
use App\Models\CocoaPrice;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Rota protegida por autenticação
Route::middleware(['auth'])->group(function () {
    Route::get('/admin', function () {
        $prices = CocoaPrice::latest('date')->paginate(15);

        // Pegando últimas 50 linhas de logs do Laravel
        $logFile = storage_path('logs/laravel.log');
        $logs = [];
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logs = array_slice(array_reverse($lines), 0, 50);
        }

        return view('admin.index', compact('prices', 'logs'));
    })->name('admin.dashboard');

    // Rota para download CSV
    Route::post('/admin/export-csv', [CocoaPriceExportController::class, 'exportCsv'])->name('admin.export.csv');
});
