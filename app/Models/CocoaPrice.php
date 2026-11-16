<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CocoaPrice extends Model
{
    protected $table = 'cocoa_prices';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'praca_id',
        'city',
        'product',
        'type',
        'unit',
        'price',
    ];

    protected $casts = [
        'date' => 'date',
        'praca_id' => 'integer',
        'price' => 'decimal:2',
    ];

    public $timestamps = true;

    public function scopeForPraca(Builder $query, int $pracaId): Builder
    {
        return $query->where('praca_id', $pracaId);
    }

    public function scopeForCityName(Builder $query, string $city): Builder
    {
        return $query->whereRaw('LOWER(city) = ?', [mb_strtolower(trim($city))]);
    }
}
