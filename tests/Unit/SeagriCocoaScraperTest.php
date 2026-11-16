<?php

namespace Tests\Unit;

use App\Services\SeagriCocoaScraper;
use Tests\TestCase;

class SeagriCocoaScraperTest extends TestCase
{
    public function test_builds_expected_seagri_url_for_city_and_all(): void
    {
        $scraper = new SeagriCocoaScraper();
        $url = $scraper->buildUrl(229, '2022-10-01', '2026-11-16', 12);

        $this->assertStringStartsWith('https://www.ba.gov.br/seagri/cotacao?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);

        $this->assertSame('37', $params['produto']);
        $this->assertSame('229', $params['praca']);
        $this->assertSame('484', $params['tipo']);
        $this->assertSame('2022-10-01', $params['data']['min']['date']);
        $this->assertSame('2026-11-16', $params['data']['max']['date']);
        $this->assertSame('12', $params['page']);

        $allUrl = $scraper->buildUrl('All', '2025-01-01', '2026-11-16', 0);
        parse_str((string) parse_url($allUrl, PHP_URL_QUERY), $allParams);
        $this->assertSame('All', $allParams['praca']);
    }

    public function test_parses_seagri_html_for_any_city_using_select_options(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
<select name="praca">
    <option value="All">- Qualquer -</option>
    <option value="201">GANDU</option>
    <option value="229">ILHEUS</option>
    <option value="242">ITABUNA</option>
    <option value="407">SANTA LUZIA</option>
    <option value="467">URUCUCA</option>
</select>
<table class="table table-striped views-table views-view-table cols-6">
    <thead>
        <tr>
            <th>Data da Cotação</th>
            <th>Produto</th>
            <th>Praça</th>
            <th>Tipo</th>
            <th>Unidade</th>
            <th>Preço</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>22/10/2026</td>
            <td>Cacau (até 15:30h)</td>
            <td>ILHEUS</td>
            <td>(comum)</td>
            <td>arroba</td>
            <td>230,00</td>
        </tr>
        <tr>
            <td>22/10/2026</td>
            <td>Cacau (até 15:30h)</td>
            <td>GANDU</td>
            <td>(comum)</td>
            <td>arroba</td>
            <td>228,50</td>
        </tr>
        <tr>
            <td>15/01/2024</td>
            <td>Cacau (até 15:30h)</td>
            <td>ITABUNA</td>
            <td>(comum)</td>
            <td>arroba</td>
            <td>Sem cotação</td>
        </tr>
        <tr>
            <td>31/08/2023</td>
            <td>Cacau (até 15:30h)</td>
            <td>URUCUCA</td>
            <td>(comum)</td>
            <td>arroba</td>
            <td>R$ 1.234,56</td>
        </tr>
    </tbody>
</table>
<nav class="pager">
    <ul class="pagination">
        <li class="page-item pager__item--next"><a href="?page=1">Próxima</a></li>
        <li class="page-item pager__item--last"><a href="?page=18">Última</a></li>
    </ul>
</nav>
</body>
</html>
HTML;

        $scraper = new SeagriCocoaScraper();
        $result = $scraper->parsePageHtml($html, 'All');

        $this->assertSame(4, $result['row_count']);
        $this->assertSame(1, $result['skipped_no_quote']);
        $this->assertTrue($result['has_next']);
        $this->assertSame(18, $result['last_page']);
        $this->assertCount(3, $result['records']);
        $this->assertCount(5, $result['pracas']);

        $this->assertSame([
            'date' => '2026-10-22',
            'praca_id' => 229,
            'city' => 'Ilhéus',
            'product' => 'Cacau (até 15:30h)',
            'type' => '(comum)',
            'unit' => 'arroba',
            'price' => 230.0,
        ], $result['records'][0]);

        $this->assertSame([
            'date' => '2026-10-22',
            'praca_id' => 201,
            'city' => 'Gandu',
            'product' => 'Cacau (até 15:30h)',
            'type' => '(comum)',
            'unit' => 'arroba',
            'price' => 228.5,
        ], $result['records'][1]);

        $this->assertSame([
            'date' => '2023-08-31',
            'praca_id' => 467,
            'city' => 'Uruçuca',
            'product' => 'Cacau (até 15:30h)',
            'type' => '(comum)',
            'unit' => 'arroba',
            'price' => 1234.56,
        ], $result['records'][2]);
    }

    public function test_parses_empty_table_without_pagination(): void
    {
        $html = '<html><body><div class="view-empty">Sem resultados</div></body></html>';

        $scraper = new SeagriCocoaScraper();
        $result = $scraper->parsePageHtml($html, 407, 'Santa Luzia');

        $this->assertSame(0, $result['row_count']);
        $this->assertSame(0, $result['skipped_no_quote']);
        $this->assertFalse($result['has_next']);
        $this->assertNull($result['last_page']);
        $this->assertSame([], $result['records']);
    }
}
