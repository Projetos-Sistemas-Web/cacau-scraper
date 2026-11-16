<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Limpa os registros antigos do scraper anterior e adapta a tabela
     * cocoa_prices para armazenar múltiplas praças (cidades) e metadados da SEAGRI.
     */
    public function up(): void
    {
        DB::table('cocoa_prices')->truncate();

        Schema::table('cocoa_prices', function (Blueprint $table) {
            $table->dropUnique('cocoa_prices_date_unique');

            $table->unsignedInteger('praca_id')->after('date')->index();
            $table->string('city', 100)->after('praca_id')->index();
            $table->string('product', 120)->default('Cacau (até 15:30h)')->after('city');
            $table->string('type', 60)->default('(comum)')->after('product');
            $table->string('unit', 40)->default('arroba')->after('type');

            $table->unique(['date', 'praca_id'], 'cocoa_prices_date_praca_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cocoa_prices', function (Blueprint $table) {
            $table->dropUnique('cocoa_prices_date_praca_unique');
            $table->dropColumn(['praca_id', 'city', 'product', 'type', 'unit']);
            $table->unique('date', 'cocoa_prices_date_unique');
        });
    }
};
