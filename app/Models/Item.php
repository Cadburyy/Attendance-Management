<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_type',
        'tanggal',
        'customer',
        'material',
        'part',
        'no_lot',
        'kode',
        'berat_mentah',
        'gpcs',
        'gkg',
        'scrap',
        'cakalan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'berat_mentah' => 'decimal:2',
        'gkg' => 'decimal:2',
        'scrap' => 'decimal:2',
        'cakalan' => 'decimal:2',
    ];
}