<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\User;
use Illuminate\Support\Facades\DB;

class AccountDuplicationController extends Controller
{
    public function index()
    {
        $groups = User::select(
            'last_ip',
            DB::raw('GROUP_CONCAT(id) as user_ids'),
            DB::raw('GROUP_CONCAT(username) as usernames'),
            DB::raw('COUNT(*) as count')
        )
            ->whereNotNull('last_ip')
            ->groupBy('last_ip')
            ->having('count', '>', 1)
            ->orderByDesc('count')
            ->get();

        return response()->json($groups);
    }
}
