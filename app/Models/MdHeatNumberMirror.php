<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdHeatNumberMirror extends Model
{
    protected $table = 'md_heat_numbers_mirror';
    // protected $primaryKey = 'heat_number'; // Default is 'id'
    // public $incrementing = false; // Default is true
    // protected $keyType = 'string'; // Default is int
    public $timestamps = false;

    protected $fillable = [
        'traveler_number',
        'heat_number',
        'kode_produksi',
        'item_code',
        'item_name',
        'size',
        'customer',
        'line',
        'cor_qty',
        'status',
        'source_updated_at',
        'last_sync_at',
    ];

    protected $casts = [
        'source_updated_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'cor_qty' => 'integer',
    ];

    /**
     * Relation to Item (Mirror)
     */
    public function item()
    {
        return $this->belongsTo(MdItemMirror::class, 'item_code', 'code');
    }
}
