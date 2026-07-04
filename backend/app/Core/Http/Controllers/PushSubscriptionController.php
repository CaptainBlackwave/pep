<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushSubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'public_key' => ['nullable', 'string', 'max:255'],
            'auth_token' => ['nullable', 'string', 'max:255'],
        ]);

        $existing = PushSubscription::where('endpoint', $validated['endpoint'])->first();

        if ($existing) {
            $existing->update([
                'public_key' => $validated['public_key'] ?? $existing->public_key,
                'auth_token' => $validated['auth_token'] ?? $existing->auth_token,
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['success' => true, 'message' => 'Subscription updated.']);
        }

        PushSubscription::create([
            'user_id' => $request->user()->id,
            'endpoint' => $validated['endpoint'],
            'public_key' => $validated['public_key'] ?? null,
            'auth_token' => $validated['auth_token'] ?? null,
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true, 'message' => 'Subscribed to push notifications.']);
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        PushSubscription::where('endpoint', $validated['endpoint'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Unsubscribed.']);
    }

    public function vapidPublicKey()
    {
        $key = config('services.webpush.vapid_public_key');

        return response()->json([
            'public_key' => $key,
        ]);
    }
}
