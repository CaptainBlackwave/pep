<?php

use App\Core\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

// Installer Routes (API for SPA)
Route::prefix('install')->name('installer.')->middleware('installer')->group(function () {
    // Serve the installer UI (handles both /install and /install/)
    Route::match(['get'], '/', function () {
        // Allow preview with ?preview
        if (file_exists(storage_path('installed')) && !request()->has('preview')) {
            // Still show installer but it will display "already installed"
        }
        return response(file_get_contents(public_path('install/index.html')))
            ->header('Content-Type', 'text/html');
    });

    Route::get('/check', [InstallerController::class, 'index'])->name('index');
    Route::get('/requirements', [InstallerController::class, 'requirements'])->name('requirements');
    Route::get('/database/check', [InstallerController::class, 'database'])->name('database');
    Route::post('/database', [InstallerController::class, 'databaseStore'])->name('database.store');
    Route::get('/settings/check', [InstallerController::class, 'settings'])->name('settings');
    Route::post('/settings', [InstallerController::class, 'settingsStore'])->name('settings.store');
    Route::get('/install/check', [InstallerController::class, 'install'])->name('install');
    Route::post('/install/process', [InstallerController::class, 'installProcess'])->name('install.process');

    // Step-by-step installation endpoints
    Route::post('/install/clear-cache', [InstallerController::class, 'stepClearCache']);
    Route::post('/install/migrate', [InstallerController::class, 'stepMigrate']);
    Route::post('/install/seed', [InstallerController::class, 'stepSeed']);
    Route::post('/install/storage-link', [InstallerController::class, 'stepStorageLink']);
    Route::post('/install/finalize', [InstallerController::class, 'stepFinalize']);

    Route::get('/setup-admin/check', [InstallerController::class, 'admin'])->name('admin');
    Route::post('/setup-admin', [InstallerController::class, 'adminStore'])->name('admin.store');
    Route::post('/license', [InstallerController::class, 'licenseStore'])->name('license.store');
    Route::get('/license/check', [InstallerController::class, 'licenseCheck'])->name('license.check');
    Route::post('/complete', [InstallerController::class, 'complete'])->name('complete');
});

// Admin Control Panel SPA (including installer UI)
Route::prefix('admin')->group(function () {
    Route::get('/{any?}', function () {
        return file_get_contents(public_path('admin/index.html'));
    })->where('any', '.*');
});

// Community Frontend SPA — catch all non-API non-admin routes
Route::get('/sitemap.xml', function () {
    $pages = [
        ['loc' => '/home', 'priority' => '1.0', 'changefreq' => 'daily'],
        ['loc' => '/discussions', 'priority' => '0.9', 'changefreq' => 'hourly'],
        ['loc' => '/guides', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => '/research-library', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => '/announcements', 'priority' => '0.7', 'changefreq' => 'daily'],
        ['loc' => '/vendor-reviews', 'priority' => '0.8', 'changefreq' => 'daily'],
        ['loc' => '/pricing', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['loc' => '/terms', 'priority' => '0.3', 'changefreq' => 'monthly'],
        ['loc' => '/privacy', 'priority' => '0.3', 'changefreq' => 'monthly'],
        ['loc' => '/community-rules', 'priority' => '0.3', 'changefreq' => 'monthly'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($pages as $page) {
        $xml .= '<url>';
        $xml .= '<loc>' . htmlspecialchars(config('app.url') . $page['loc'], ENT_QUOTES, 'UTF-8') . '</loc>';
        $xml .= '<priority>' . $page['priority'] . '</priority>';
        $xml .= '<changefreq>' . $page['changefreq'] . '</changefreq>';
        $xml .= '</url>';
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
});

Route::get('/{any?}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '^(?!api|admin|install|storage).*$');
