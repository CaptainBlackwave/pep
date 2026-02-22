<?php

/**
 * Announcements Module Hooks
 */


// Register announcements service binding so Core controllers can resolve it without importing the class.
if (! app()->bound('announcements.service')) {
    app()->singleton('announcements.service', fn () => app(\App\Plugins\Announcements\Services\AnnouncementService::class));
}

return [
    'OnAnnouncementCreated' => function($data) {
        // Could send emails, push notifications
        return $data;
    },
    
    'OnAnnouncementViewed' => function($data) {
        // Could track view counts, analytics
        return $data;
    },
    
    // Filter announcements (membership-based visibility, etc.)
    'filterAnnouncements' => function($data) {
        return $data;
    },
    
    'moduleLoad' => function() {
        // Initialization
    },
];
