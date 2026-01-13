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
                    $selected = session('selected_department_code');
                    if ($selected !== 'all') {
                        // Use exact match to distinguish between 404.1 and sub-depts like 404.1.1
                        $builder->where('department_code', $selected);
                    }
                }

                return;
            }

            // 2. Manager: Can see primary + additional departments (Hierarchical)
            //    Also supports session context to drill-down
            if ($user->role === 'manager') {
                $allowedDepts = array_merge(
                    [$user->department_code],
                    $user->additional_department_codes ?? []
                );
                $allowedDepts = array_filter($allowedDepts);

                if (empty($allowedDepts))
                    return;

                // If session context is set, filter to that specific department
                if (session()->has('selected_department_code')) {
                    $selected = session('selected_department_code');
                    if ($selected !== 'all') {
                        // Verify the selected department is within allowed scope
                        $isAllowed = collect($allowedDepts)->contains(function ($code) use ($selected) {
                            return str_starts_with($selected, $code) || str_starts_with($code, $selected);
                        });
                        if ($isAllowed) {
                            // Use exact match to distinguish between 404.1 and sub-depts
                            $builder->where('department_code', $selected);
                            return;
                        }

                    }
                }

                // Default: show all allowed departments
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

            // 4. Operator: Strict exact department isolation (like Instagram)
            if ($user->role === 'operator') {
                $builder->where('department_code', $user->department_code);
                return;
            }

            // 5. Default / Kabag / Read-only: Exact sub-department
            $builder->where('department_code', $user->department_code);
        }
    }
}

