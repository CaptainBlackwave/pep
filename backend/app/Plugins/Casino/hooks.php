<?php

/**
 * Casino Module Hooks
 */

return [
    'OnBetPlaced' => function($data) {
        return $data;
    },
    
    'OnBetWon' => function($data) {
        // Could trigger achievements, etc.
        return $data;
    },
    
    'OnBetLost' => function($data) {
        return $data;
    },
    
    'OnLotteryTicketPurchased' => function($data) {
        return $data;
    },
    
    // Modify winnings (membership bonuses, etc.)
    'alterWinnings' => function($data) {
        return $data;
    },
    
    'moduleLoad' => function() {
        // Initialization
    },

    'admin.dashboard.widgets' => function ($widgets) {
        $widgets['casino'] = [
            'total_games' => \DB::getSchemaBuilder()->hasTable('casino_games')
                ? \DB::table('casino_games')->count() : 0,
            'bets_today' => \DB::getSchemaBuilder()->hasTable('casino_bets')
                ? \DB::table('casino_bets')->whereDate('played_at', today())->count() : 0,
        ];
        return $widgets;
    },
];
