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

        static::creating(function ($model) {
            if (auth()->check()) {
                $user = auth()->user();
                if (!$model->department_code && $user->department_code) {
                    $model->department_code = $user->department_code;
                }

                // If model has 'tim' column and user has 'tim', auto-fill it
                if (method_exists($model, 'getTable') && \Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), 'tim')) {
                    if (!$model->tim && $user->tim) {
                        $model->tim = $user->tim;
                    }
                }
            }
        });
    }
}
