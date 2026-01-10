<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdHeatNumberMirror extends Model
{
    protected $table = 'md_heat_numbers_mirror';
    protected $primaryKey = 'heat_number';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'heat_number',
        'kode_produksi',
        'item_code',
        'item_name',
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
