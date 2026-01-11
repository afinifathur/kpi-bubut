<?php

namespace App\Traits;

use App\Models\Scopes\DepartmentScope;

trait HasDepartmentScope
{
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new DepartmentScope);
    }
}
