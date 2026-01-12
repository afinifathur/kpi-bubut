<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class DepartmentScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();

            // 1. Direktur & MR: Full access by default, or session context
            if (in_array($user->role, ['direktur', 'mr'])) {
                if (session()->has('selected_department_code')) {
                    $builder->where('department_code', 'LIKE', session('selected_department_code') . '%');
                }
                return;
            }

            // 2. Manager: Can see primary + additional departments (Hierarchical)
            if ($user->role === 'manager') {
                $allowedDepts = array_merge(
                    [$user->department_code],
                    $user->additional_department_codes ?? []
                );
                $allowedDepts = array_filter($allowedDepts);

                if (empty($allowedDepts))
                    return;

                $builder->where(function ($q) use ($allowedDepts) {
                    foreach ($allowedDepts as $code) {
                        $q->orWhere('department_code', 'LIKE', $code . '%');
                    }
                });
                return;
            }

            // 3. SPV: Exact sub-department + Team isolation
            if ($user->role === 'spv') {
                if ($user->department_code) {
                    $builder->where('department_code', $user->department_code);
                }

                // Only filter by TIM if the model has the column
                // Note: We use in_array check for specific transactional models for performance
                $teamAwareModels = [
                    \App\Models\ProductionLog::class,
                    \App\Models\RejectLog::class,
                    \App\Models\DowntimeLog::class
                ];

                if ($user->tim && in_array(get_class($model), $teamAwareModels)) {
                    $builder->where('tim', $user->tim);
                }
                return;
            }

            // 4. Default / Kabag / Read-only: Exact sub-department
            if ($user->department_code) {
                $builder->where('department_code', $user->department_code);
            }
        }
    }
}
