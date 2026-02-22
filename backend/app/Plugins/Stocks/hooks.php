<?php

/**
 * Stocks Module Hooks
 */

return [
    'OnStockBuy' => function($data) {
        // Could trigger achievements, notifications
        return $data;
    },
    
    'OnStockSell' => function($data) {
        return $data;
    },
    
    'OnPriceUpdate' => function($data) {
        // Could trigger notifications for big price swings
        return $data;
    },
    
    // Modify transaction fees (membership discounts, etc.)
    'alterTransactionFees' => function($data) {
        return $data;
    },
    
    'moduleLoad' => function() {
        // Initialization
    },

    'admin.dashboard.widgets' => function ($widgets) {
        $widgets['stocks'] = [
            'total_stocks' => \DB::getSchemaBuilder()->hasTable('stocks')
                ? \DB::table('stocks')->count() : 0,
            'investors' => \DB::getSchemaBuilder()->hasTable('user_stocks')
                ? \DB::table('user_stocks')->distinct('user_id')->count() : 0,
        ];
        return $widgets;
    },
];
