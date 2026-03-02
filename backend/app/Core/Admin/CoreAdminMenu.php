<?php

namespace App\Core\Admin;

class CoreAdminMenu
{
    /**
     * Get the default admin sidebar items.
     */
    public static function items(): array
    {
        return [
            [
                'id' => 'user-management',
                'label' => 'User Management',
                'icon' => 'UsersIcon',
                'order' => 1,
                'children' => [
                    [ 'route' => '/users',      'label' => 'Users',               'icon' => 'UsersIcon' ],
                    [ 'route' => '/user-tools', 'label' => 'User Tools',          'icon' => 'WrenchScrewdriverIcon' ],
                    [ 'route' => '/roles',      'label' => 'Roles & Permissions', 'icon' => 'ShieldCheckIcon' ],
                    [ 'route' => '/ip-bans',    'label' => 'IP Bans',             'icon' => 'NoSymbolIcon' ],
                ],
            ],
            [
                'id' => 'configuration',
                'label' => 'Configuration',
                'icon' => 'Cog6ToothIcon',
                'order' => 2,
                'children' => [
                    [ 'route' => '/settings',       'label' => 'Settings',    'icon' => 'Cog6ToothIcon' ],
                    [ 'route' => '/email-settings', 'label' => 'Email',       'icon' => 'EnvelopeIcon' ],
                    [ 'route' => '/plugin-settings','label' => 'Plugins',     'icon' => 'PuzzlePieceIcon' ],
                    [ 'route' => '/license',        'label' => 'License',     'icon' => 'KeyIcon' ],
                ],
            ],
            [
                'id' => 'communication',
                'label' => 'Communication',
                'icon' => 'ChatBubbleLeftRightIcon',
                'order' => 45,
                'children' => [
                    [ 'route' => '/announcements',    'label' => 'Announcements',    'icon' => 'MegaphoneIcon' ],
                    [ 'route' => '/wiki',             'label' => 'Wiki',             'icon' => 'BookOpenIcon' ],
                    [ 'route' => '/faq',              'label' => 'FAQ',              'icon' => 'QuestionMarkCircleIcon' ],
                    [ 'route' => '/forum-categories', 'label' => 'Forum Categories', 'icon' => 'ChatBubbleLeftRightIcon' ],
                    [ 'route' => '/events',           'label' => 'Events',           'icon' => 'CalendarIcon' ],
                    [ 'route' => '/chat-channels',    'label' => 'Chat Channels',    'icon' => 'ChatBubbleLeftRightIcon' ],
                ],
            ],
            [
                'id' => 'support',
                'label' => 'Support',
                'icon' => 'LifebuoyIcon',
                'order' => 50,
                'children' => [
                    [ 'route' => '/tickets',           'label' => 'Tickets',           'icon' => 'TicketIcon' ],
                    [ 'route' => '/ticket-categories', 'label' => 'Ticket Categories', 'icon' => 'TagIcon' ],
                ],
            ],
            [
                'id' => 'system',
                'label' => 'System',
                'icon' => 'ServerIcon',
                'order' => 90,
                'children' => [
                    [ 'route' => '/system-health',  'label' => 'System Health',   'icon' => 'ServerIcon' ],
                    [ 'route' => '/error-logs',     'label' => 'Error Logs',      'icon' => 'ExclamationTriangleIcon' ],
                    [ 'route' => '/activity-logs',  'label' => 'Activity Logs',   'icon' => 'ClipboardDocumentListIcon' ],
                    [ 'route' => '/security',       'label' => 'Security',        'icon' => 'ShieldCheckIcon' ],
                    [ 'route' => '/backups',        'label' => 'Backups',         'icon' => 'CircleStackIcon' ],
                    [ 'route' => '/webhooks',       'label' => 'Webhooks',        'icon' => 'ArrowTopRightOnSquareIcon' ],
                    [ 'route' => '/api-keys',       'label' => 'API Keys',        'icon' => 'KeyIcon' ],
                    [ 'route' => '/notifications',  'label' => 'Notifications',   'icon' => 'BellIcon' ],
                    [ 'route' => '/calendar',       'label' => 'Calendar',        'icon' => 'CalendarIcon' ],
                    [ 'route' => '/tasks',          'label' => 'Tasks',           'icon' => 'ClipboardDocumentCheckIcon' ],
                ],
            ],
        ];
    }
}
