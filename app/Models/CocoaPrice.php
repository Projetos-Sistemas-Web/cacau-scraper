<?php

namespace App\Models;

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
        'price',
    ];

    protected $casts = [
        'date'  => 'date',
        'price' => 'decimal:2',
    ];

    public $timestamps = true;
}
