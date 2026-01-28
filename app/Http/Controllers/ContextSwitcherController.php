<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContextSwitcherController extends Controller
{
    /**
     * Get available departments for the current user.
     */
    public function getDepartments()
    {
        $user = Auth::user();
        $departments = [];

        // Direktur, MR, HR, Guest (and Special HR Emails): Can see all bubut departments
        $isSpecialHr = in_array($user->email, ['adminhr@peroniks.com', 'managerhr@peroniks.com']);

        if (in_array($user->role, ['direktur', 'mr', 'hr_admin', 'hr_manager', 'guest']) || $isSpecialHr) {
            $departments = \Illuminate\Support\Facades\DB::connection('master')
                ->table('md_departments')
                ->where('code', 'LIKE', '404%')
                ->where('status', 'active')
                ->orderBy('code')
                ->get(['code', 'name']);
        }

        // Manager: Can see allowed departments
        if ($user->role === 'manager') {
            $allowedDepts = array_merge(
                [$user->department_code],
                $user->additional_department_codes ?? []
            );
            $allowedDepts = array_filter($allowedDepts);

            $departments = \Illuminate\Support\Facades\DB::connection('master')
                ->table('md_departments')
                ->where('status', 'active')
                ->where(function ($q) use ($allowedDepts) {
                    foreach ($allowedDepts as $code) {
                        $q->orWhere('code', 'LIKE', $code . '%');
                    }
                })
                ->orderBy('code')
                ->get(['code', 'name']);
        }

        return response()->json([
            'current' => session('selected_department_code', 'all'),
            'departments' => $departments,
        ]);
    }

    /**
     * Set the selected department context.
     */
    public function setDepartment(Request $request)
    {
        $user = Auth::user();
        $code = $request->input('department_code');

        // Validate user can access this department
        $isSpecialHr = in_array($user->email, ['adminhr@peroniks.com', 'managerhr@peroniks.com']);

        if (!in_array($user->role, ['direktur', 'mr', 'manager', 'hr_admin', 'hr_manager', 'guest']) && !$isSpecialHr) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($code === 'all' || $code === null) {
            session()->forget('selected_department_code');
        } else {
            session(['selected_department_code' => $code]);
        }

        return response()->json([
            'success' => true,
            'selected' => $code ?? 'all',
        ]);
    }
}
