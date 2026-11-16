<?php

namespace App\Console\Commands;

use App\Models\CocoaPrice;
use App\Services\SeagriCocoaScraper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScrapCocoaPrice extends Command
{
    protected $signature = 'scrap:cocoa-price
                            {date? : Data específica no formato YYYY-MM-DD (opcional)}
                            {--from= : Data inicial (YYYY-MM-DD)}
                            {--to= : Data final (YYYY-MM-DD)}
                            {--full : Buscar histórico completo desde 2022-10-01 até hoje}
                            {--city=* : ID(s) ou nome(s) de cidade da Bahia (omitir para buscar todas as cidades)}
                            {--tipo= : Tipo na SEAGRI (padrão config scraper.tipo, ex: 484 ou All)}
                            {--list-cities : Lista todas as praças da Bahia disponíveis na SEAGRI e encerra}
                            {--retries= : Tentativas por requisição}
                            {--delay= : Atraso mínimo em milissegundos entre requisições (anti-DDoS)}';

    protected $description = 'Busca as cotações da arroba do cacau na SEAGRI-BA para qualquer cidade da Bahia e salva no banco.';

    public function handle(SeagriCocoaScraper $scraper): int
    {
        $retries = max(1, (int) ($this->option('retries') ?: config('scraper.retries', 3)));

        if ($this->option('list-cities')) {
            return $this->handleListCities($scraper, $retries);
        }

        $range = $this->resolveDateRange();
        if ($range === null) {
            return Command::FAILURE;
        }

        [$fromDate, $toDate] = $range;

        $targets = $this->resolveTargets($scraper, $retries);
        if (empty($targets)) {
            $this->error('Nenhuma praça válida encontrada para os parâmetros informados.');

            return Command::FAILURE;
        }

        $tipo = $this->option('tipo') ?: (string) config('scraper.tipo', '484');
        $delayMinMs = $this->option('delay') !== null
            ? max(500, (int) $this->option('delay'))
            : max(500, (int) config('scraper.delay_min_ms', 2000));
        $delayMaxMs = max($delayMinMs, (int) config('scraper.delay_max_ms', 3500));
        $maxPages = max(1, (int) config('scraper.max_pages', 100));

        $this->info("Iniciando scraping SEAGRI-BA ({$fromDate} até {$toDate}, tipo={$tipo})...");

        $totalSaved = 0;
        $totalSkipped = 0;
        $hadFailure = false;
        $requestIndex = 0;

        foreach ($targets as $target) {
            $praca = $target['praca'];
            $label = $target['label'];
            $forcedCity = $praca === 'All' ? null : $label;

            $this->info("=== Consultando: {$label} (praca={$praca}) ===");
            $targetSaved = 0;
            $page = 0;

            while ($page < $maxPages) {
                if ($requestIndex > 0) {
                    $this->politePause($delayMinMs, $delayMaxMs);
                }
                $requestIndex++;

                $url = $scraper->buildUrl($praca, $fromDate, $toDate, $page, $tipo);

                try {
                    $html = $scraper->fetchPage($url, $retries);
                } catch (\Throwable $e) {
                    $hadFailure = true;
                    $this->error("Erro ao consultar {$label} (página {$page}): {$e->getMessage()}");
                    Log::error("scrap:cocoa-price erro em {$label} (praca={$praca}, page={$page}): {$e->getMessage()}");
                    break;
                }

                $parsed = $scraper->parsePageHtml($html, $praca, $forcedCity);
                $count = count($parsed['records']);
                $totalSkipped += $parsed['skipped_no_quote'];

                if ($count > 0) {
                    $now = now();
                    $rows = array_map(static fn (array $r) => [
                        'date' => $r['date'],
                        'praca_id' => $r['praca_id'],
                        'city' => $r['city'],
                        'product' => $r['product'],
                        'type' => $r['type'],
                        'unit' => $r['unit'],
                        'price' => $r['price'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $parsed['records']);

                    CocoaPrice::upsert(
                        $rows,
                        ['date', 'praca_id'],
                        ['city', 'product', 'type', 'unit', 'price', 'updated_at']
                    );

                    $targetSaved += $count;
                    $totalSaved += $count;
                }

                $pageLabel = $parsed['last_page'] !== null
                    ? ($page + 1) . '/' . ($parsed['last_page'] + 1)
                    : (string) ($page + 1);

                $this->line("  Página {$pageLabel}: {$count} cotação(ões) salva(s), {$parsed['skipped_no_quote']} sem cotação.");

                if (! $parsed['has_next'] || $parsed['row_count'] === 0) {
                    break;
                }

                $page++;
            }

            if ($targetSaved === 0) {
                $this->warn("Nenhuma cotação numérica encontrada para {$label} no período {$fromDate}..{$toDate}.");
            } else {
                Log::info("scrap:cocoa-price — {$targetSaved} cotação(ões) salva(s) para {$label} ({$fromDate}..{$toDate}).");
            }
        }

        $this->info("Finalizado. Total salvo/atualizado: {$totalSaved} registro(s). Ignorados (sem cotação): {$totalSkipped}.");

        return $hadFailure && $totalSaved === 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function handleListCities(SeagriCocoaScraper $scraper, int $retries): int
    {
        try {
            $pracas = $scraper->fetchAvailablePracas($retries);
        } catch (\Throwable $e) {
            $this->error("Não foi possível consultar a lista de cidades da SEAGRI: {$e->getMessage()}");

            return Command::FAILURE;
        }

        $this->info('Praças disponíveis na SEAGRI-BA (' . count($pracas) . '):');
        foreach ($pracas as $id => $name) {
            $this->line(sprintf('  %4d => %s', $id, $name));
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<array{praca: int|string, label: string}>
     */
    private function resolveTargets(SeagriCocoaScraper $scraper, int $retries): array
    {
        $requested = array_values(array_filter((array) $this->option('city'), static fn ($v) => trim((string) $v) !== ''));

        // Sem filtro --city: consulta praca=All para capturar qualquer cidade da Bahia em uma única varredura
        if (empty($requested)) {
            return [[
                'praca' => 'All',
                'label' => 'Todas as cidades da Bahia',
            ]];
        }

        foreach ($requested as $item) {
            if (strcasecmp(trim((string) $item), 'All') === 0) {
                return [[
                    'praca' => 'All',
                    'label' => 'Todas as cidades da Bahia',
                ]];
            }
        }

        $knownCities = (array) config('scraper.cities', []) + (array) config('scraper.city_labels', []);
        $remotePracas = null;
        $targets = [];

        foreach ($requested as $item) {
            $raw = trim((string) $item);

            if (ctype_digit($raw)) {
                $id = (int) $raw;
                if (isset($knownCities[$id])) {
                    $targets[$id] = ['praca' => $id, 'label' => $knownCities[$id]];
                    continue;
                }

                $remotePracas ??= $scraper->fetchAvailablePracas($retries);
                if (isset($remotePracas[$id])) {
                    $targets[$id] = ['praca' => $id, 'label' => $remotePracas[$id]];
                } else {
                    $this->warn("Praça ID {$id} não encontrada no formulário da SEAGRI.");
                }
                continue;
            }

            // Busca por nome da cidade (sem acento/case-insensitive)
            $needle = $scraper->normalizeKey($raw);
            $matchedId = null;

            foreach ($knownCities as $id => $name) {
                if ($scraper->normalizeKey($name) === $needle) {
                    $matchedId = (int) $id;
                    $targets[$matchedId] = ['praca' => $matchedId, 'label' => $name];
                    break;
                }
            }

            if ($matchedId !== null) {
                continue;
            }

            $remotePracas ??= $scraper->fetchAvailablePracas($retries);
            foreach ($remotePracas as $id => $name) {
                if ($scraper->normalizeKey($name) === $needle) {
                    $matchedId = (int) $id;
                    $targets[$matchedId] = ['praca' => $matchedId, 'label' => $name];
                    break;
                }
            }

            if ($matchedId === null) {
                $this->warn("Cidade '{$raw}' não encontrada na SEAGRI. Use --list-cities para ver os nomes válidos.");
            }
        }

        return array_values($targets);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function resolveDateRange(): ?array
    {
        $tz = 'America/Sao_Paulo';
        $today = Carbon::today($tz);

        $dateArg = $this->argument('date');
        $from = $this->option('from');
        $to = $this->option('to');
        $full = (bool) $this->option('full');

        if ($full) {
            return ['2022-10-01', $today->format('Y-m-d')];
        }

        if ($from || $to) {
            if (! $from || ! $to) {
                $this->error('Para usar intervalo é necessário informar --from e --to (YYYY-MM-DD).');

                return null;
            }

            try {
                $start = Carbon::createFromFormat('Y-m-d', $from, $tz)->startOfDay();
                $end = Carbon::createFromFormat('Y-m-d', $to, $tz)->startOfDay();
            } catch (\Throwable) {
                $this->error('Formato de data inválido em --from/--to. Use YYYY-MM-DD.');

                return null;
            }

            if ($start->gt($end)) {
                $this->error('--from não pode ser posterior a --to.');

                return null;
            }

            return [$start->format('Y-m-d'), $end->format('Y-m-d')];
        }

        if ($dateArg) {
            try {
                $d = Carbon::createFromFormat('Y-m-d', $dateArg, $tz)->format('Y-m-d');

                return [$d, $d];
            } catch (\Throwable) {
                $this->error('Formato de data inválido no argumento date. Use YYYY-MM-DD.');

                return null;
            }
        }

        $lookback = max(1, (int) config('scraper.default_lookback_days', 30));

        return [
            $today->copy()->subDays($lookback)->format('Y-m-d'),
            $today->format('Y-m-d'),
        ];
    }

    private function politePause(int $minMs, int $maxMs): void
    {
        $delayMs = random_int($minMs, $maxMs);
        usleep($delayMs * 1000);
    }
}
