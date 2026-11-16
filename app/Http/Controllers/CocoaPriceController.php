<?php

namespace App\Http\Controllers;

use App\Models\CocoaPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CocoaPriceController extends Controller
{
    /**
     * Lista as praças (cidades) armazenadas no banco e as cidades configuradas em destaque.
     */
    public function cities(): JsonResponse
    {
        $featured = (array) config('scraper.cities', []);

        $stored = CocoaPrice::query()
            ->selectRaw('praca_id, MAX(city) as city, COUNT(*) as records')
            ->groupBy('praca_id')
            ->get()
            ->keyBy('praca_id');

        $allIds = collect(array_keys($featured))
            ->merge($stored->keys())
            ->unique()
            ->values();

        $cities = $allIds->map(function ($id) use ($featured, $stored) {
            $idInt = (int) $id;
            $row = $stored->get($idInt);

            return [
                'praca_id' => $idInt,
                'city' => $row->city ?? ($featured[$idInt] ?? (string) $idInt),
                'records' => $row ? (int) $row->records : 0,
            ];
        })->sortBy('city')->values();

        return response()->json(['cities' => $cities]);
    }

    /**
     * Retorna o valor do cacau em um dia específico (todas as cidades ou filtrada por ?praca= / ?city=).
     */
    public function daily(Request $request, $ano, $mes, $dia): JsonResponse
    {
        $data = sprintf('%04d-%02d-%02d', (int) $ano, (int) $mes, (int) $dia);

        $query = CocoaPrice::whereDate('date', $data);

        if ($request->filled('praca')) {
            $query->forPraca((int) $request->query('praca'));
        } elseif ($request->filled('city')) {
            $query->forCityName((string) $request->query('city'));
        }

        $prices = $query->orderBy('city')->get();

        if ($prices->isEmpty()) {
            return response()->json([
                'error' => "Nenhum dado encontrado para {$data}",
            ], 404);
        }

        if ($request->filled('praca') || $request->filled('city')) {
            $price = $prices->first();

            return response()->json([
                'date' => $price->date->format('Y-m-d'),
                'praca_id' => $price->praca_id,
                'city' => $price->city,
                'product' => $price->product,
                'type' => $price->type,
                'unit' => $price->unit,
                'price' => (float) $price->price,
            ]);
        }

        $first = $prices->first();

        return response()->json([
            'date' => $first->date->format('Y-m-d'),
            'city' => $first->city,
            'price' => (float) $first->price,
            'quotes' => $prices->map(fn (CocoaPrice $p) => [
                'praca_id' => $p->praca_id,
                'city' => $p->city,
                'product' => $p->product,
                'type' => $p->type,
                'unit' => $p->unit,
                'price' => (float) $p->price,
            ])->values(),
        ]);
    }

    /**
     * Retorna a média do cacau para um mês específico (opcionalmente filtrada por ?praca= / ?city=).
     */
    public function monthly(Request $request, $ano, $mes): JsonResponse
    {
        $query = CocoaPrice::whereYear('date', (int) $ano)
            ->whereMonth('date', (int) $mes);

        if ($request->filled('praca')) {
            $query->forPraca((int) $request->query('praca'));
        } elseif ($request->filled('city')) {
            $query->forCityName((string) $request->query('city'));
        }

        $media = (clone $query)->avg('price');

        if ($media === null) {
            return response()->json([
                'error' => "Nenhum dado encontrado para {$ano}-{$mes}",
            ], 404);
        }

        $byCity = (clone $query)
            ->selectRaw('praca_id, city, AVG(price) as average_price, COUNT(*) as records')
            ->groupBy('praca_id', 'city')
            ->orderBy('city')
            ->get()
            ->map(fn ($row) => [
                'praca_id' => (int) $row->praca_id,
                'city' => $row->city,
                'average_price' => round((float) $row->average_price, 2),
                'records' => (int) $row->records,
            ])
            ->values();

        return response()->json([
            'year' => (int) $ano,
            'month' => (int) $mes,
            'average_price' => round((float) $media, 2),
            'by_city' => $byCity,
        ]);
    }
}
