<?php

use App\Facades\Hook;

// Property purchase event
Hook::register('OnPropertyBought', function ($data) {
    // Log property purchase
    activity()
        ->causedBy($data['player'])
        ->withProperties(['property' => $data['property']->name, 'price' => $data['price']])
        ->log('property_bought');
});

// Property sale event
Hook::register('OnPropertySold', function ($data) {
    // Log property sale
    activity()
        ->causedBy($data['player'])
        ->withProperties(['property' => $data['property']->name, 'sale_price' => $data['sale_price']])
        ->log('property_sold');
});

// Income collection event
Hook::register('OnIncomeCollected', function ($data) {
    // Log income collection
    activity()
        ->causedBy($data['player'])
        ->withProperties(['amount' => $data['amount']])
        ->log('property_income_collected');
});

// Register the properties relation on User from this plugin rather than embedding
// the plugin class name in the Core User model.
\App\Core\Models\User::resolveRelationUsing('properties', function ($user) {
    return $user->belongsToMany(\App\Plugins\Properties\Models\Property::class, 'user_properties')
        ->withPivot('purchased_at', 'income_collected_at')
        ->withTimestamps();
});
