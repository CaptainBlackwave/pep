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
                'id' => 'game-config',
                'label' => 'Game Configuration',
                'icon' => 'Cog6ToothIcon',
                'order' => 2,
                'children' => [
                    [ 'route' => '/settings',       'label' => 'Settings',    'icon' => 'Cog6ToothIcon' ],
                    [ 'route' => '/email-settings', 'label' => 'Email',       'icon' => 'EnvelopeIcon' ],
                    [ 'route' => '/plugin-settings','label' => 'Plugins',     'icon' => 'PuzzlePieceIcon' ],
                    [ 'route' => '/locations',      'label' => 'Locations',   'icon' => 'MapPinIcon' ],
                    [ 'route' => '/ranks',          'label' => 'Ranks',       'icon' => 'StarIcon' ],
                    [ 'route' => '/license',        'label' => 'License',     'icon' => 'KeyIcon' ],
                ],
            ],
            [
                'id' => 'crimes',
                'label' => 'Crimes',
                'icon' => 'BoltIcon',
                'order' => 10,
                'children' => [
                    [ 'route' => '/crimes',            'label' => 'Crimes',            'icon' => 'BoltIcon' ],
                    [ 'route' => '/organized-crimes',  'label' => 'Organized Crimes',  'icon' => 'UserGroupIcon' ],
                    [ 'route' => '/drugs',             'label' => 'Drugs',             'icon' => 'BeakerIcon' ],
                    [ 'route' => '/theft-types',       'label' => 'Theft Types',       'icon' => 'ArchiveBoxArrowDownIcon' ],
                ],
            ],
            [
                'id' => 'combat',
                'label' => 'Combat',
                'icon' => 'FireIcon',
                'order' => 15,
                'children' => [
                    [ 'route' => '/combat-areas',     'label' => 'Combat Areas',     'icon' => 'MapIcon' ],
                    [ 'route' => '/combat-enemies',   'label' => 'Enemies',          'icon' => 'ExclamationTriangleIcon' ],
                    [ 'route' => '/combat-locations', 'label' => 'Combat Locations', 'icon' => 'MapPinIcon' ],
                    [ 'route' => '/combat-logs',      'label' => 'Combat Logs',      'icon' => 'ClipboardDocumentListIcon' ],
                ],
            ],
            [
                'id' => 'economy',
                'label' => 'Economy',
                'icon' => 'BanknotesIcon',
                'order' => 20,
                'children' => [
                    [ 'route' => '/items',        'label' => 'Items',        'icon' => 'CubeIcon' ],
                    [ 'route' => '/item-market',  'label' => 'Item Market',  'icon' => 'ShoppingCartIcon' ],
                    [ 'route' => '/properties',   'label' => 'Properties',   'icon' => 'HomeIcon' ],
                    [ 'route' => '/cars',         'label' => 'Cars',         'icon' => 'TruckIcon' ],
                    [ 'route' => '/stocks',       'label' => 'Stocks',       'icon' => 'ChartBarIcon' ],
                    [ 'route' => '/memberships',  'label' => 'Memberships',  'icon' => 'IdentificationIcon' ],
                ],
            ],
            [
                'id' => 'social',
                'label' => 'Social',
                'icon' => 'UserGroupIcon',
                'order' => 25,
                'children' => [
                    [ 'route' => '/gangs',        'label' => 'Gangs',        'icon' => 'UserGroupIcon' ],
                    [ 'route' => '/bounties',     'label' => 'Bounties',     'icon' => 'CurrencyDollarIcon' ],
                    [ 'route' => '/missions',     'label' => 'Missions',     'icon' => 'FlagIcon' ],
                    [ 'route' => '/achievements', 'label' => 'Achievements', 'icon' => 'TrophyIcon' ],
                ],
            ],
            [
                'id' => 'employment',
                'label' => 'Employment',
                'icon' => 'BriefcaseIcon',
                'order' => 30,
                'children' => [
                    [ 'route' => '/companies',     'label' => 'Companies',      'icon' => 'BuildingOfficeIcon' ],
                    [ 'route' => '/job-positions', 'label' => 'Job Positions',  'icon' => 'BriefcaseIcon' ],
                ],
            ],
            [
                'id' => 'education',
                'label' => 'Education',
                'icon' => 'AcademicCapIcon',
                'order' => 35,
                'children' => [
                    [ 'route' => '/courses', 'label' => 'Courses', 'icon' => 'AcademicCapIcon' ],
                ],
            ],
            [
                'id' => 'entertainment',
                'label' => 'Entertainment',
                'icon' => 'SparklesIcon',
                'order' => 40,
                'children' => [
                    [ 'route' => '/casino-games', 'label' => 'Casino Games', 'icon' => 'SparklesIcon' ],
                    [ 'route' => '/lotteries',    'label' => 'Lotteries',    'icon' => 'TicketIcon' ],
                    [ 'route' => '/races',        'label' => 'Races',        'icon' => 'TrophyIcon' ],
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
                'id' => 'player-tools',
                'label' => 'Player Tools',
                'icon' => 'UserGroupIcon',
                'order' => 55,
                'children' => [
                    [ 'route' => '/daily-rewards', 'label' => 'Daily Rewards',  'icon' => 'GiftIcon' ],
                    [ 'route' => '/leaderboards',  'label' => 'Leaderboards',   'icon' => 'TrophyIcon' ],
                    [ 'route' => '/jail',          'label' => 'Jail',           'icon' => 'NoSymbolIcon' ],
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
