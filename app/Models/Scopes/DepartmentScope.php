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
            
            // If user has a department_code, lock them to it
            if ($user->department_code) {
                $builder->where('department_code', $user->department_code);
            } 
            // If manager/director has selected a context in session
            elseif (session()->has('selected_department_code')) {
                $builder->where('department_code', session('selected_department_code'));
            }
        }
    }
}
