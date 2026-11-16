<?php

use App\Http\Controllers\CocoaPriceExportController;
use App\Http\Controllers\ProfileController;
use App\Models\CocoaPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::get('/admin', function (Request $request) {
        $featuredCities = (array) config('scraper.cities', []);

        // Mescla as cidades em destaque com quaisquer outras cidades da Bahia já armazenadas no banco
        $dbCities = CocoaPrice::query()
            ->select('praca_id', 'city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city', 'praca_id')
            ->toArray();

        $cities = $featuredCities + $dbCities;
        asort($cities, SORT_LOCALE_STRING | SORT_FLAG_CASE);

        $selectedPracaId = $request->filled('praca_id') ? (int) $request->query('praca_id') : null;

        $query = CocoaPrice::query();
        if ($selectedPracaId !== null && $selectedPracaId > 0) {
            $query->where('praca_id', $selectedPracaId);
        }

        $prices = $query->orderByDesc('date')
            ->orderBy('city')
            ->paginate(15)
            ->withQueryString();

        // Calcula a variação em relação ao registro imediatamente anterior da mesma praça
        $variations = [];
        foreach ($prices as $item) {
            $previous = CocoaPrice::where('praca_id', $item->praca_id)
                ->whereDate('date', '<', $item->date->format('Y-m-d'))
                ->orderByDesc('date')
                ->first();

            $variations[$item->id] = ($previous && (float) $previous->price > 0)
                ? (((float) $item->price - (float) $previous->price) / (float) $previous->price) * 100
                : null;
        }

        // Última cotação registrada para os cards principais (cidades em destaque + eventuais novas com dados)
        $cardCities = $featuredCities + $dbCities;
        $latestByCity = [];
        foreach ($cardCities as $pracaId => $cityName) {
            $latestByCity[$pracaId] = [
                'name' => $cityName,
                'praca_id' => $pracaId,
                'latest' => CocoaPrice::where('praca_id', $pracaId)->orderByDesc('date')->first(),
                'count' => CocoaPrice::where('praca_id', $pracaId)->count(),
            ];
        }

        // Pegando últimas 50 linhas de logs do Laravel
        $logFile = storage_path('logs/laravel.log');
        $logs = [];
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logs = array_slice(array_reverse($lines), 0, 50);
        }

        return view('admin.index', compact(
            'prices',
            'variations',
            'cities',
            'selectedPracaId',
            'latestByCity',
            'logs'
        ));
    })->name('admin.dashboard');

    // Rota para download CSV
    Route::post('/admin/export-csv', [CocoaPriceExportController::class, 'exportCsv'])->name('admin.export.csv');
});
