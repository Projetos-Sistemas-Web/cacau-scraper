<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SeagriCocoaScraper
{
    private HttpClientInterface $client;

    public function __construct(?HttpClientInterface $client = null)
    {
        $this->client = $client ?? HttpClient::create([
            'timeout' => config('scraper.timeout', 30),
            'headers' => [
                'User-Agent' => config('scraper.user_agent'),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control' => 'no-cache',
            ],
        ]);
    }

    /**
     * Monta a URL de consulta da SEAGRI para uma praça específica (ID) ou 'All' (todas as cidades).
     */
    public function buildUrl(int|string $praca, string $fromDate, string $toDate, int $page = 0, ?string $tipo = null): string
    {
        $baseUrl = rtrim((string) config('scraper.base_url'), '/');

        $query = http_build_query([
            'produto' => config('scraper.produto', '37'),
            'praca' => (string) $praca,
            'tipo' => $tipo ?? config('scraper.tipo', '484'),
            'data' => [
                'min' => ['date' => $fromDate],
                'max' => ['date' => $toDate],
            ],
            'page' => $page,
        ]);

        return "{$baseUrl}?{$query}";
    }

    /**
     * Extrai todas as praças disponíveis no <select name="praca"> do HTML da SEAGRI.
     *
     * @return array<int, string> [praca_id => nome]
     */
    public function extractPracasFromHtml(string $html): array
    {
        $crawler = new Crawler($html);
        $pracas = [];

        $crawler->filter('select[name="praca"] option')->each(function (Crawler $option) use (&$pracas) {
            $value = trim((string) $option->attr('value'));
            $label = $this->cleanText($option->text(''));

            if (! ctype_digit($value) || (int) $value <= 2 || $label === '') {
                return;
            }

            $id = (int) $value;
            $pracas[$id] = $this->formatCityName($id, $label);
        });

        return $pracas;
    }

    /**
     * Busca no portal da SEAGRI a lista completa de cidades (praças) disponíveis.
     *
     * @return array<int, string>
     */
    public function fetchAvailablePracas(int $retries = 3): array
    {
        $url = rtrim((string) config('scraper.base_url'), '/');
        $html = $this->fetchPage($url, $retries);

        return $this->extractPracasFromHtml($html);
    }

    /**
     * Faz o parsing do HTML retornado pela SEAGRI e extrai as cotações (qualquer cidade) e paginação.
     *
     * @return array{
     *   records: list<array{date: string, praca_id: int, city: string, product: string, type: string, unit: string, price: float}>,
     *   pracas: array<int, string>,
     *   skipped_no_quote: int,
     *   row_count: int,
     *   has_next: bool,
     *   last_page: int|null
     * }
     */
    public function parsePageHtml(string $html, int|string $praca = 'All', ?string $forcedCityName = null): array
    {
        $crawler = new Crawler($html);
        $pracas = $this->extractPracasFromHtml($html);

        // Índice reverso normalizado (SEM ACENTO / MAIÚSCULAS) => praca_id para resolver linhas de praca=All
        $nameToId = [];
        foreach ($pracas as $id => $label) {
            $nameToId[$this->normalizeKey($label)] = $id;
        }
        foreach ((array) config('scraper.cities', []) as $id => $label) {
            $nameToId[$this->normalizeKey($label)] = (int) $id;
        }
        foreach ((array) config('scraper.city_labels', []) as $id => $label) {
            $nameToId[$this->normalizeKey($label)] = (int) $id;
        }

        $records = [];
        $skippedNoQuote = 0;
        $rowCount = 0;

        $rows = $crawler->filter('table.views-table tbody tr');
        if ($rows->count() === 0) {
            $rows = $crawler->filter('table tbody tr');
        }

        $rows->each(function (Crawler $row) use (
            &$records,
            &$skippedNoQuote,
            &$rowCount,
            $praca,
            $forcedCityName,
            $pracas,
            $nameToId
        ) {
            $tds = $row->filter('td');
            if ($tds->count() < 6) {
                return;
            }

            $rowCount++;

            $dateRaw = $this->cleanText($tds->eq(0)->text(''));
            $product = $this->cleanText($tds->eq(1)->text(''));
            $pracaRaw = $this->cleanText($tds->eq(2)->text(''));
            $type = $this->cleanText($tds->eq(3)->text(''));
            $unit = $this->cleanText($tds->eq(4)->text(''));
            $priceRaw = $this->cleanText($tds->eq(5)->text(''));

            $date = $this->parseDate($dateRaw);
            if ($date === null) {
                return;
            }

            $price = $this->parsePrice($priceRaw);
            if ($price === null) {
                $skippedNoQuote++;

                return;
            }

            $pracaId = $this->resolveRowPracaId($praca, $pracaRaw, $nameToId);
            if ($pracaId === null) {
                return;
            }

            $cityName = $forcedCityName
                ?? $pracas[$pracaId]
                ?? $this->formatCityName($pracaId, $pracaRaw);

            $records[] = [
                'date' => $date,
                'praca_id' => $pracaId,
                'city' => $cityName,
                'product' => $product !== '' ? $product : 'Cacau (até 15:30h)',
                'type' => $type !== '' ? $type : '(comum)',
                'unit' => $unit !== '' ? $unit : 'arroba',
                'price' => $price,
            ];
        });

        $hasNext = $crawler->filter('.pager__item--next a, a[rel="next"]')->count() > 0;
        $lastPage = null;

        $lastLink = $crawler->filter('.pager__item--last a')->first();
        if ($lastLink->count() > 0) {
            $href = (string) $lastLink->attr('href');
            parse_str((string) parse_url($href, PHP_URL_QUERY), $params);
            if (isset($params['page']) && is_numeric($params['page'])) {
                $lastPage = (int) $params['page'];
            }
        }

        return [
            'records' => $records,
            'pracas' => $pracas,
            'skipped_no_quote' => $skippedNoQuote,
            'row_count' => $rowCount,
            'has_next' => $hasNext,
            'last_page' => $lastPage,
        ];
    }

    /**
     * Busca uma página na SEAGRI com tentativas e backoff amigável para evitar bloqueios/DDoS.
     */
    public function fetchPage(string $url, int $retries = 3): string
    {
        $retries = max(1, $retries);
        $lastException = null;

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $response = $this->client->request('GET', $url);
                $status = $response->getStatusCode();

                if ($status === 200) {
                    return $response->getContent();
                }

                if ($status === 429 || $status >= 500) {
                    $headers = $response->getHeaders(false);
                    $retryAfter = isset($headers['retry-after'][0]) && is_numeric($headers['retry-after'][0])
                        ? (int) $headers['retry-after'][0]
                        : (4 * $attempt);

                    Log::warning("SeagriCocoaScraper: HTTP {$status} na tentativa {$attempt} ({$url}). Aguardando {$retryAfter}s.");

                    if ($attempt < $retries) {
                        sleep(min($retryAfter, 30));
                        continue;
                    }
                }

                throw new \RuntimeException("HTTP {$status} ao acessar {$url}");
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning("SeagriCocoaScraper: erro na tentativa {$attempt}/{$retries}: {$e->getMessage()}");

                if ($attempt < $retries) {
                    sleep(min(3 * $attempt, 15));
                }
            }
        }

        throw new \RuntimeException(
            'Falha ao consultar SEAGRI: ' . ($lastException?->getMessage() ?? 'erro desconhecido'),
            0,
            $lastException
        );
    }

    public function parseDate(string $raw): ?string
    {
        if (! preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function parsePrice(string $raw): ?float
    {
        if ($raw === '' || stripos($raw, 'sem cota') !== false) {
            return null;
        }

        $clean = preg_replace('/[^0-9,.\-]/', '', $raw);
        if ($clean === null || $clean === '') {
            return null;
        }

        if (str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }

        if (! is_numeric($clean)) {
            return null;
        }

        $value = round((float) $clean, 2);

        return $value > 0 ? $value : null;
    }

    public function normalizeKey(string $value): string
    {
        return Str::upper(Str::ascii($this->cleanText($value)));
    }

    public function formatCityName(int $pracaId, string $rawName): string
    {
        $labels = (array) config('scraper.city_labels', []);
        if (isset($labels[$pracaId])) {
            return $labels[$pracaId];
        }

        $cities = (array) config('scraper.cities', []);
        if (isset($cities[$pracaId])) {
            return $cities[$pracaId];
        }

        return mb_convert_case(mb_strtolower($this->cleanText($rawName), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @param  array<string, int>  $nameToId
     */
    private function resolveRowPracaId(int|string $requestedPraca, string $pracaRaw, array $nameToId): ?int
    {
        if (is_numeric($requestedPraca)) {
            return (int) $requestedPraca;
        }

        $key = $this->normalizeKey($pracaRaw);
        if ($key !== '' && isset($nameToId[$key])) {
            return $nameToId[$key];
        }

        return $key !== '' ? abs(crc32($key)) : null;
    }

    private function cleanText(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }
}
