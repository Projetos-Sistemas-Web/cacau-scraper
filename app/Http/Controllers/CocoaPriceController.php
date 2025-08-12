<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CocoaPrice;

class CocoaPriceController extends Controller
{
    /**
     * Retorna o valor do cacau em um dia específico.
     */
    public function daily($ano, $mes, $dia)
    {
        $data = "{$ano}-{$mes}-{$dia}";

        $price = CocoaPrice::whereDate('date', $data)->first();

        if (!$price) {
            return response()->json([
                'error' => "Nenhum dado encontrado para {$data}"
            ], 404);
        }

        return response()->json([
            'date' => $price->date->format('Y-m-d'),
            'price' => (float) $price->price
        ]);
    }

    /**
     * Retorna a média do cacau para um mês específico.
     */
    public function monthly($ano, $mes)
    {
        $media = CocoaPrice::whereYear('date', $ano)
            ->whereMonth('date', $mes)
            ->avg('price');

        if (!$media) {
            return response()->json([
                'error' => "Nenhum dado encontrado para {$ano}-{$mes}"
            ], 404);
        }

        return response()->json([
            'year' => (int) $ano,
            'month' => (int) $mes,
            'average_price' => round($media, 2)
        ]);
    }
}
