<?php

namespace App\Plugins\Jail\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\User;
use App\Plugins\Jail\Services\JailService;

class JailAdminController extends Controller
{
    public function index(JailService $jail)
    {
        $jailed = $jail->getJailedPlayers();
        return response()->json(['players' => $jailed, 'total' => count($jailed)]);
    }

    public function release(JailService $jail, int $id)
    {
        $player = User::findOrFail($id);
        $jail->release($player);
        return response()->json(['success' => true, 'message' => $player->username . ' released from jail']);
    }
}
