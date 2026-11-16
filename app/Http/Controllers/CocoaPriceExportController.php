<?php

namespace App\Http\Controllers;

use App\Models\CocoaPrice;
use Illuminate\Http\Request;

class CocoaPriceExportController extends Controller
{
    /**
     * Exporta os preços do cacau para CSV baseado no período e praça fornecidos.
     */
    public function exportCsv(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'praca_id' => 'nullable|integer',
        ]);

        $query = CocoaPrice::whereBetween('date', [$validated['start_date'], $validated['end_date']]);

        if (! empty($validated['praca_id'])) {
            $query->where('praca_id', (int) $validated['praca_id']);
        }

        $prices = $query->orderBy('date', 'desc')
            ->orderBy('city', 'asc')
            ->get();

        if ($prices->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Nenhum dado encontrado para os filtros especificados.',
                ], 404);
            }

            return back()->with('error', 'Nenhum dado encontrado para o período/cidade especificado.');
        }

        $handle = fopen('php://temp', 'r+');
        // BOM UTF-8 para compatibilidade com Excel em português
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Data', 'Cidade', 'Praça ID', 'Produto', 'Tipo', 'Unidade', 'Preço (R$)'], ';');

        foreach ($prices as $price) {
            fputcsv($handle, [
                $price->date->format('d/m/Y'),
                $price->city,
                $price->praca_id,
                $price->product,
                $price->type,
                $price->unit,
                number_format((float) $price->price, 2, ',', '.'),
            ], ';');
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $filename = 'precos_cacau_' . date('Y-m-d_H-i-s') . '.csv';

        return response($csvContent)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
