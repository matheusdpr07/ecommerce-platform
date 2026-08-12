<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboardService): Response
    {
        $this->authorize('accessAdminPanel', User::class);

        $period = $request->integer('period');

        if (! in_array($period, [7, 30, 90], true)) {
            $period = 30;
        }

        return Inertia::render('Admin/Dashboard', $dashboardService->getData($period));
    }
}
