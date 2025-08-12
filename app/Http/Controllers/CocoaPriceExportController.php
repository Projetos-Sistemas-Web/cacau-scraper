<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CocoaPrice;
use Illuminate\Http\Response;

class CocoaPriceExportController extends Controller
{
    /**
     * Exporta os preços do cacau para CSV baseado no período fornecido.
     */
    public function exportCsv(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Busca os preços no período especificado
        $prices = CocoaPrice::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        if ($prices->isEmpty()) {
            return response()->json([
                'error' => 'Nenhum dado encontrado para o período especificado.'
            ], 404);
        }

        // Prepara o conteúdo CSV
        $csvContent = "Data,Preço (R$)\n";

        foreach ($prices as $price) {
            $csvContent .= $price->date->format('d/m/Y') . ',' . number_format($price->price, 2, ',', '.') . "\n";
        }

        // Define o nome do arquivo
        $filename = 'precos_cacau_' . date('Y-m-d_H-i-s') . '.csv';

        // Retorna o arquivo CSV
        return response($csvContent)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
