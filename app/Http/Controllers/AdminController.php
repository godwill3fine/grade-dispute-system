<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;

class AdminController extends Controller
{

    public function recentActivity()
        {
            return ActivityLog::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();
        }
}
