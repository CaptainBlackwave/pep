<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\Setting;

class PublicSettingsController extends Controller
{
    public function index()
    {
        return response()->json([
            'telegram_url' => Setting::where('key', 'telegram_url')->value('value')
                ?? config('app.telegram_url')
                ?? 'https://t.me/peptidevendors',
        ]);
    }
}
