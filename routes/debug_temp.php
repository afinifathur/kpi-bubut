<?php

use App\Models\MdItemMirror;
use App\Models\MdOperatorMirror;
use Illuminate\Support\Facades\Route;

Route::get('/debug-data', function () {
    return [
        'items_count' => MdItemMirror::count(),
        'items_sample' => MdItemMirror::limit(5)->get(),
        'operators_count' => MdOperatorMirror::count(),
        'operators_sample' => MdOperatorMirror::limit(5)->get(),
        'items_active_count' => MdItemMirror::where('status', 'active')->count(),
        'operators_active_count' => MdOperatorMirror::where('status', 'active')->count(),
    ];
});
