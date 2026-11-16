# Cacau Scraper

Sistema de monitoramento e análise de preços da arroba do cacau na Bahia desenvolvido em Laravel, utilizando como fonte oficial o portal da **SEAGRI-BA** (`https://www.ba.gov.br/seagri/cotacao`).

## 📋 Sobre o Projeto

- **Monitoramento Multi-Cidade**: Suporta qualquer praça (cidade) da Bahia disponível no portal da SEAGRI-BA (com destaque inicial para Ilhéus, Itabuna, Uruçuca e Santa Luzia)
- **Fonte Oficial (SEAGRI-BA)**: Coleta data da cotação, produto, praça, tipo, unidade e preço com controle de cadência anti-DDoS
- **Painel Administrativo (`/admin`)**: Cards com última cotação por cidade, filtro dinâmico por cidade, variação percentual por praça e logs do Laravel
- **Exportação CSV**: Exportação por período e por cidade
- **API REST (`/api/cacau/...`)**: Consulta de cotações diárias, médias mensais e cidades monitoradas

## 🚀 Como Rodar o Projeto

1. **Dependências PHP e Node**
   ```bash
   composer install
   nvm use
   npm install
   ```

2. **Ambiente, banco de dados e assets**
   - Copie `.env.example` para `.env`, gere a chave da aplicação (`php artisan key:generate`) e configure suas credenciais locais de banco de dados no `.env`.
   - Execute as migrations e compile os assets:
     ```bash
     php artisan migrate
     npm run build
     ```

3. **Iniciar o servidor web**
   ```bash
   php artisan serve
   # ou ambiente completo (server + queue + logs + vite):
   composer run dev
   ```
   Acesse `http://localhost:8000` (o acesso ao painel `/admin` requer autenticação).

## 🕷️ Como Rodar o Scraper

- **Atualização padrão (últimos 30 dias até hoje, todas as cidades da Bahia com cotação):**
  ```bash
  php artisan scrap:cocoa-price
  ```

- **Carga histórica completa (desde 2022-10-01 até hoje, todas as cidades):**
  ```bash
  php artisan scrap:cocoa-price --full
  ```

- **Listar todas as cidades da Bahia disponíveis na SEAGRI:**
  ```bash
  php artisan scrap:cocoa-price --list-cities
  ```

- **Intervalo específico e/ou cidade específica (por ID ou por nome):**
  ```bash
  php artisan scrap:cocoa-price --from=2025-01-01 --to=2026-11-16 --city=229
  php artisan scrap:cocoa-price --from=2016-01-01 --to=2016-03-01 --city=Itabuna
  php artisan scrap:cocoa-price --from=2025-01-01 --to=2026-11-16 --city=Ilheus --city=Gandu
  ```

> **Agendamento (`routes/console.php`)**: configurado para rodar 1 vez por dia às `18:00` (`America/Sao_Paulo`) com `withoutOverlapping()` e pausa aleatória entre requisições para evitar sobrecarga/bloqueio (anti-DDoS).

## 📡 Endpoints da API

- `GET /api/cacau/cidades`
- `GET /api/cacau/preco-diario/{ano}/{mes}/{dia}` (aceita `?praca=` ou `?city=`)
- `GET /api/cacau/media-mensal/{ano}/{mes}` (aceita `?praca=` ou `?city=`)
