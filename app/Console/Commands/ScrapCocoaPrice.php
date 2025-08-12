<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;
use App\Models\CocoaPrice;
use Illuminate\Support\Facades\Log;

class ScrapCocoaPrice extends Command
{
     /**
     * The name and signature of the console command.
     *
     * - date (opcional): YYYY-MM-DD (se não informado, usa hoje)
     * - --from / --to: processa um intervalo de datas (ambos obrigatórios)
     * - --retries: tentativas por requisição (padrão 3)
     * - --weekdays: se passado, processa apenas seg-sex
     */
    protected $signature = 'scrap:cocoa-price
                            {date? : Data no formato YYYY-MM-DD (opcional, padrão hoje)}
                            {--from= : Data inicial (YYYY-MM-DD) para range}
                            {--to= : Data final (YYYY-MM-DD) para range}
                            {--retries=3 : Tentativas por requisição}
                            {--weekdays : Processar apenas dias de semana}';

    protected $description = 'Roda o scraping do preço da arroba do cacau na Bahia e salva no banco.';

    public function handle(): int
    {
        $dateArg = $this->argument('date');
        $from = $this->option('from');
        $to = $this->option('to');
        $retries = max(1, (int) $this->option('retries'));
        $onlyWeekdays = $this->option('weekdays');

        // Monta lista de datas a processar
        $dates = [];

        if ($from || $to) {
            if (!$from || !$to) {
                $this->error('Para usar intervalo é necessário passar --from e --to (YYYY-MM-DD).');
                return Command::FAILURE;
            }
            try {
                $start = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
                $end   = Carbon::createFromFormat('Y-m-d', $to)->startOfDay();
            } catch (\Exception $e) {
                $this->error('Formato de data inválido em --from/--to. Use YYYY-MM-DD.');
                return Command::FAILURE;
            }
            if ($start->gt($end)) {
                $this->error('--from não pode ser posterior a --to.');
                return Command::FAILURE;
            }
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                if ($onlyWeekdays && $d->isWeekend()) {
                    continue;
                }
                $dates[] = $d->copy();
            }
        } else {
            try {
                $d = $dateArg ? Carbon::createFromFormat('Y-m-d', $dateArg) : Carbon::today('America/Sao_Paulo');
            } catch (\Exception $e) {
                $this->error('Formato de data inválido no argumento date. Use YYYY-MM-DD.');
                return Command::FAILURE;
            }
            if ($onlyWeekdays && $d->isWeekend()) {
                $this->info("Data {$d->toDateString()} é fim de semana e --weekdays foi passado. Encerrando sem processar.");
                return Command::SUCCESS;
            }
            $dates[] = $d;
        }

        // Http client (com User-Agent para reduzir bloqueios)
        $client = HttpClient::create([
            'timeout' => 20,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; CocoaScraper/1.0; +https://example.com)'
            ],
        ]);

        foreach ($dates as $date) {
            $dateStr = $date->format('Y-m-d');
            $this->info("=== Processando {$dateStr} ===");
            $url = "https://www.noticiasagricolas.com.br/cotacoes/cacau/cacau-mercado-do-cacau/{$dateStr}";

            $succeeded = false;

            for ($attempt = 1; $attempt <= $retries; $attempt++) {
                try {
                    $response = $client->request('GET', $url);
                    $status = $response->getStatusCode();

                    if ($status !== 200) {
                        $this->warn("Tentativa {$attempt}: status HTTP {$status} — {$url}");
                        sleep(2);
                        continue;
                    }

                    $content = $response->getContent();
                    $crawler = new Crawler($content);

                    $found = false;

                    // Percorre linhas da primeira tabela encontrada
                    $crawler->filter('table tr')->each(function (Crawler $row) use (&$found, $dateStr) {
                        $tds = $row->filter('td');

                        if ($tds->count() >= 2) {
                            $estado = trim($tds->eq(0)->text());

                            if (stripos($estado, 'Bahia') !== false) {
                                $raw = trim($tds->eq(1)->text());

                                // Limpeza robusta do número (ex: "R$ 1.234,56" -> "1234.56")
                                $clean = preg_replace('/[^0-9,.\-]/', '', $raw);
                                // remover pontos de milhar e transformar vírgula decimal em ponto
                                $clean = str_replace('.', '', $clean);
                                $clean = str_replace(',', '.', $clean);

                                $price = is_numeric($clean) ? round((float)$clean, 2) : null;

                                if ($price !== null) {
                                    CocoaPrice::updateOrCreate(
                                        ['date' => $dateStr],
                                        ['price' => $price]
                                    );

                                    $this->info("Encontrado e salvo: R$ {$price} — {$dateStr}");
                                    Log::info("scrap:cocoa-price — preço salvo R$ {$price} para {$dateStr}");
                                    $found = true;
                                } else {
                                    $this->warn("Linha Bahia encontrada, mas não foi possível parsear preço: '{$raw}'");
                                }
                            }
                        }
                    });

                    if (!$found) {
                        $this->warn("Não encontrei linha 'Bahia' na tabela para {$dateStr} (pode não existir cotação neste dia).");
                    }

                    $succeeded = true;
                    break; // sai do loop de tentativas
                } catch (\Throwable $e) {
                    $this->error("Erro na tentativa {$attempt} para {$dateStr}: {$e->getMessage()}");
                    Log::error("scrap:cocoa-price erro: {$e->getMessage()} em {$dateStr}");
                    // backoff simples
                    sleep(2 * $attempt);
                }
            } // end attempts

            if (!$succeeded) {
                $this->error("Falha ao processar {$dateStr} após {$retries} tentativas.");
            }

            // espera curta entre requisições para ser gentil com o servidor
            sleep(rand(1, 3));
        } // end foreach dates

        $this->info('Processamento finalizado.');
        return Command::SUCCESS;
    }
}
