<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Executa uma vez ao dia às 18:00 (horário de Brasília) para evitar sobrecarga/DDoS no portal da SEAGRI-BA.
Schedule::command('scrap:cocoa-price')
    ->dailyAt('18:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping();
