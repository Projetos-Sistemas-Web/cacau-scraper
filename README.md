<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# 🍫 Cacau Scraper

<p align="center">
<img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
<img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/Tailwind_CSS-3.0+-06B6D4?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
<img src="https://img.shields.io/badge/Alpine.js-3.0+-8BC34A?style=for-the-badge&logo=alpine.js&logoColor=white" alt="Alpine.js">
<img src="https://img.shields.io/badge/MySQL-Database-003B57?style=for-the-badge&logo=sqlite&logoColor=white" alt="SQLite">
<img src="https://img.shields.io/badge/Vite-Build_Tool-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite">
</p>

Sistema de monitoramento e análise de preços do cacau desenvolvido em Laravel. O projeto oferece uma interface administrativa completa para visualização, análise e exportação de dados históricos de preços do cacau.

## 📋 Sobre o Projeto

O Cacau Scraper é uma aplicação web desenvolvida com o objetivo de aprendizado em desenvolvimento com Laravel. O projeto permite:

- **Monitoramento de Preços**: Acompanhamento em tempo real dos preços do cacau
- **Análise Histórica**: Visualização de tendências e variações de preços ao longo do tempo
- **Exportação de Dados**: Download de relatórios em formato CSV com filtros por período
- **Dashboard Administrativo**: Interface moderna e responsiva para gestão dos dados
- **Sistema de Logs**: Monitoramento de atividades do sistema

### 🎯 Objetivo Educacional

Este projeto foi desenvolvido como uma oportunidade de aprendizado prático com o framework Laravel, explorando suas funcionalidades principais como:
- Sistema de autenticação com Laravel Breeze
- Eloquent ORM para gerenciamento de dados
- Blade templates para renderização de views
- Migrations e seeders para estrutura do banco
- Controllers e rotas para lógica da aplicação
- Integração com frontend moderno (Tailwind CSS + Alpine.js)

## 🚀 Funcionalidades

### Dashboard Administrativo
- **Estatísticas em Tempo Real**: Total de registros, último preço registrado, última atualização e status do sistema
- **Tabela Interativa**: Listagem paginada com indicadores visuais de variação de preços
- **Logs do Sistema**: Visualização expansível dos últimos logs do Laravel

### Exportação de Dados
- **Filtros por Período**: Seleção de data inicial e final para exportação
- **Formato CSV**: Downloads otimizados com formatação brasileira
- **Nomenclatura Automática**: Arquivos nomeados com timestamp para organização

### Sistema de Autenticação
- **Login Seguro**: Autenticação baseada em sessão
- **Registro de Usuários**: Sistema completo de cadastro
- **Redirecionamento Inteligente**: Acesso direto ao painel após login

## 🛠️ Tecnologias Utilizadas

- **Backend**: Laravel 11.x
- **Frontend**: Blade Templates + Tailwind CSS + Alpine.js
- **Banco de Dados**: SQLite (configurável para MySQL/PostgreSQL)
- **Autenticação**: Laravel Breeze
- **Build Tools**: Vite + NPM

## 📦 Instalação

### Pré-requisitos
- PHP 8.2+
- Composer
- Node.js 18+
- NPM

### Passo a Passo

1. **Clone o repositório**
   ```bash
   git clone https://github.com/Projetos-Sistemas-Web/cacau-scraper.git
   cd cacau-scraper
   ```

2. **Instale as dependências do PHP**
   ```bash
   composer install
   ```

3. **Configure o ambiente**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure o banco de dados**
   ```bash
   php artisan migrate
   ```

5. **Instale as dependências do Node.js**
   ```bash
   npm install
   ```

6. **Compile os assets**
   ```bash
   npm run build
   # ou para desenvolvimento:
   npm run dev
   ```

7. **Inicie o servidor**
   ```bash
   php artisan serve
   ```

## 🎯 Como Usar

### Primeiro Acesso
1. Acesse `http://localhost:8000`
2. Crie uma conta ou faça login
3. Será redirecionado automaticamente para o painel administrativo

### Gerenciamento de Dados
- **Visualizar Preços**: Os dados são exibidos automaticamente na tabela principal
- **Exportar Relatórios**: Use o botão "Exportar CSV" para baixar dados por período
- **Monitorar Sistema**: Expanda a seção "Logs do Sistema" para visualizar atividades

### Estrutura de Dados
```sql
cocoa_prices
├── id (Primary Key)
├── date (Data única do preço)
├── price (Preço em decimal)
├── created_at
└── updated_at
```

## 📁 Estrutura do Projeto

```
cacau-scraper/
├── app/
│   ├── Http/Controllers/
│   │   ├── CocoaPriceExportController.php
│   │   └── Auth/
│   └── Models/
│       └── CocoaPrice.php
├── database/
│   └── migrations/
│       └── create_cocoa_prices_table.php
├── resources/
│   ├── views/
│   │   ├── admin/
│   │   │   └── index.blade.php
│   │   ├── auth/
│   │   └── layouts/
│   └── css/
├── routes/
│   └── web.php
└── public/
```

## 🔧 Configuração Avançada

### Banco de Dados
Para usar MySQL ou PostgreSQL, edite o arquivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cacau_scraper
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### Customização do Tailwind
As cores e estilos podem ser personalizados em `tailwind.config.js`.

## 📄 Licença

Este projeto está licenciado sob a [MIT License](https://opensource.org/licenses/MIT).

---

<p align="center">
Feito com ❤️ usando Laravel
</p>
