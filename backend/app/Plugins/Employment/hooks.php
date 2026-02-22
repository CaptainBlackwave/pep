<?php

use App\Plugins\Employment\EmploymentModule;
use Illuminate\Support\Facades\Log;

return [
    'OnJobApplied' => function ($data) {
        Log::info('Player applied for job', [
            'user_id' => $data['user']->id,
            'position' => $data['position']->name,
            'company' => $data['company']->name ?? null,
        ]);
        return $data;
    },

    'OnWorkCompleted' => function ($data) {
        Log::info('Player completed work shift', [
            'user_id' => $data['user']->id,
            'earnings' => $data['earnings'],
            'exp' => $data['exp'],
        ]);
        return $data;
    },

    'OnJobQuit' => function ($data) {
        Log::info('Player quit job', [
            'user_id' => $data['user']->id,
            'position' => $data['position'] ?? null,
        ]);
        return $data;
    },

    'alterEmploymentCompanies' => function ($data) {
        return $data;
    },

    'admin.dashboard.widgets' => function ($widgets) {
        $widgets['employment'] = [
            'total_companies' => \DB::getSchemaBuilder()->hasTable('companies')
                ? \DB::table('companies')->count() : 0,
            'employed_users' => \DB::getSchemaBuilder()->hasTable('player_employment')
                ? \DB::table('player_employment')->where('is_active', true)->count() : 0,
        ];
        return $widgets;
    },
];
