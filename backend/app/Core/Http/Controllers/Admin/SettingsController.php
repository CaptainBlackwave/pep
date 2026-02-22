<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\Setting;
use App\Core\Models\User;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Get all settings as a flat key-value object
     */
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // Convert boolean strings to actual booleans for frontend
        foreach ($settings as $key => $value) {
            if ($value === '1' || $value === 'true') {
                $settings[$key] = true;
            } elseif ($value === '0' || $value === 'false') {
                $settings[$key] = false;
            } elseif (is_numeric($value) && strpos($value, '.') !== false) {
                $settings[$key] = (float) $value;
            } elseif (is_numeric($value)) {
                $settings[$key] = (int) $value;
            }
        }

        return response()->json($settings);
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        // Check if settings are sent as flat object (from frontend) or array format
        $data = $request->all();

        // If data has a 'settings' array with key/value pairs, use that format
        if (isset($data['settings']) && is_array($data['settings'])) {
            // Check if it's the old format: [{key: 'x', value: 'y'}]
            if (isset($data['settings'][0]['key'])) {
                $validated = $request->validate([
                    'settings' => 'required|array',
                    'settings.*.key' => 'required|string',
                    'settings.*.value' => 'required',
                ]);

                foreach ($validated['settings'] as $setting) {
                    Setting::updateOrCreate(
                        ['key' => $setting['key']],
                        ['value' => $setting['value']]
                    );
                }
            }
        } else {
            // Flat object format from frontend: {game_name: 'x', starting_cash: 1000}
            // Exclude any non-setting fields
            $excludeFields = ['_token', '_method'];

            foreach ($data as $key => $value) {
                if (in_array($key, $excludeFields)) {
                    continue;
                }

                // Convert boolean to string for storage
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                }

                // Convert arrays/objects to JSON
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }

                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => (string) $value]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Get specific setting
     */
    public function show($key)
    {
        $setting = Setting::where('key', $key)->firstOrFail();
        return response()->json($setting);
    }

    /**
     * Create or update settings (handles both single setting and bulk update)
     */
    public function store(Request $request)
    {
        $data = $request->all();

        // Check if this is a single setting with 'key' field
        if (isset($data['key'])) {
            $validated = $request->validate([
                'key' => 'required|string',
                'value' => 'required',
                'category' => 'nullable|string',
                'type' => 'nullable|string|in:text,number,boolean,json',
                'description' => 'nullable|string',
            ]);

            $setting = Setting::updateOrCreate(
                ['key' => $validated['key']],
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Setting saved successfully',
                'setting' => $setting
            ]);
        }

        // Otherwise, treat as bulk update with flat object format
        // {game_name: 'x', starting_cash: 1000, ...}
        $excludeFields = ['_token', '_method'];

        foreach ($data as $key => $value) {
            if (in_array($key, $excludeFields)) {
                continue;
            }

            // Convert boolean to string for storage
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            // Convert arrays/objects to JSON
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Get security-specific settings (2FA, OAuth, policies)
     */
    public function securityIndex()
    {
        $flat = Setting::all()->pluck('value', 'key')->toArray();

        $securityKeys = [
            'require_2fa_admin', 'allow_2fa_users', 'recovery_codes_count',
            'totp_window', 'min_password_length', 'require_special_chars',
            'require_mixed_case', 'session_timeout', 'max_login_attempts',
            'lockout_duration',
        ];

        $defaults = [
            'require_2fa_admin'    => false,
            'allow_2fa_users'      => true,
            'recovery_codes_count' => 8,
            'totp_window'          => 30,
            'min_password_length'  => 8,
            'require_special_chars'=> false,
            'require_mixed_case'   => false,
            'session_timeout'      => 120,
            'max_login_attempts'   => 5,
            'lockout_duration'     => 15,
        ];

        $security = [];
        foreach ($securityKeys as $key) {
            $raw = $flat[$key] ?? null;
            if ($raw === null) {
                $security[$key] = $defaults[$key];
            } elseif ($raw === '1' || $raw === 'true') {
                $security[$key] = true;
            } elseif ($raw === '0' || $raw === 'false') {
                $security[$key] = false;
            } elseif (is_numeric($raw)) {
                $security[$key] = (int) $raw;
            } else {
                $security[$key] = $raw;
            }
        }

        // 2FA stats
        $totalUsers   = User::count();
        $with2fa      = User::whereNotNull('two_factor_confirmed_at')->count();
        $adminsWith2fa = User::whereNotNull('two_factor_confirmed_at')
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->count();

        $twoFactorStats = [
            'enabled'        => $with2fa,
            'admins_enabled' => $adminsWith2fa,
            'percentage'     => $totalUsers > 0 ? round(($with2fa / $totalUsers) * 100) : 0,
        ];

        // OAuth providers
        $oauthCounts = \App\Core\Models\OAuthProvider::selectRaw('provider, count(*) as cnt')
            ->groupBy('provider')
            ->pluck('cnt', 'provider');

        $providerNames = ['discord', 'google', 'github', 'twitter', 'facebook'];
        $oauthProviders = [];
        foreach ($providerNames as $name) {
            $clientId     = $flat["oauth_{$name}_client_id"]     ?? '';
            $clientSecret = $flat["oauth_{$name}_client_secret"] ?? '';
            $enabled      = ($flat["oauth_{$name}_enabled"] ?? '0') === '1';
            $oauthProviders[] = [
                'name'          => $name,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'enabled'       => $enabled,
                'configured'    => !empty($clientId) && !empty($clientSecret),
                'users_count'   => (int) ($oauthCounts[$name] ?? 0),
            ];
        }

        return response()->json([
            'security'         => $security,
            'two_factor_stats' => $twoFactorStats,
            'oauth_providers'  => $oauthProviders,
        ]);
    }

    /**
     * Save OAuth provider credentials
     */
    public function saveOAuthProvider(Request $request, string $provider)
    {
        $validated = $request->validate([
            'client_id'     => 'nullable|string|max:500',
            'client_secret' => 'nullable|string|max:500',
            'enabled'       => 'boolean',
        ]);

        Setting::updateOrCreate(['key' => "oauth_{$provider}_client_id"],     ['value' => $validated['client_id'] ?? '']);
        Setting::updateOrCreate(['key' => "oauth_{$provider}_client_secret"], ['value' => $validated['client_secret'] ?? '']);
        Setting::updateOrCreate(['key' => "oauth_{$provider}_enabled"],       ['value' => ($validated['enabled'] ?? false) ? '1' : '0']);

        return response()->json(['success' => true, 'message' => ucfirst($provider) . ' settings saved']);
    }

    /**
     * Delete setting
     */
    public function destroy($key)
    {
        Setting::where('key', $key)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted successfully'
        ]);
    }
}
