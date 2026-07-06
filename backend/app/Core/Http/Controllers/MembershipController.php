<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\MembershipPlan;
use App\Core\Models\MembershipSubscription;
use App\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MembershipController extends Controller
{
    public function plans()
    {
        return response()->json([
            'data' => MembershipPlan::where('active', true)->orderBy('sort_order')->get()->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => (float) $plan->price_monthly,
                'price_yearly' => (float) $plan->price_yearly,
                'features' => $plan->features ?? [],
            ]),
        ]);
    }

    public function status(Request $request)
    {
        $user = $request->user();
        $sub = MembershipSubscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'past_due'])
            ->with('plan')
            ->latest()
            ->first();

        return response()->json([
            'tier' => $user->tier,
            'subscription' => $sub ? [
                'id' => $sub->id,
                'plan' => $sub->plan?->name ?? 'Unknown',
                'plan_id' => $sub->plan_id,
                'provider' => $sub->provider,
                'status' => $sub->status,
                'current_period_end' => $sub->current_period_end?->toIso8601String(),
                'cancelled' => $sub->cancelled_at !== null,
            ] : null,
        ]);
    }

    public function createStripeCheckout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:membership_plans,id',
            'interval' => 'required|in:month,year',
        ]);

        $plan = MembershipPlan::findOrFail($request->plan_id);
        $user = $request->user();
        $price = $request->interval === 'year' ? $plan->price_yearly : $plan->price_monthly;

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $customerId = $user->stripe_customer_id;
        if (!$customerId) {
            $customer = \Stripe\Customer::create([
                'email' => $user->email,
                'metadata' => ['user_id' => $user->id],
            ]);
            $customerId = $customer->id;
            $user->update(['stripe_customer_id' => $customerId]);
        }

        $session = \Stripe\Checkout\Session::create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => $plan->name . ' (' . $request->interval . 'ly)'],
                    'unit_amount' => (int) ($price * 100),
                    'recurring' => ['interval' => $request->interval],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => config('app.frontend_url') . '/settings/billing?success=1',
            'cancel_url' => config('app.frontend_url') . '/pricing?canceled=1',
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan_id' => (string) $plan->id,
                'interval' => $request->interval,
            ],
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function handleStripeWebhook(Request $request)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $endpointSecret = config('services.stripe.webhook_secret');

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $userId = $session->metadata->user_id;
                $planId = $session->metadata->plan_id;
                $subscriptionId = $session->subscription;

                if ($userId && $planId && $subscriptionId) {
                    try {
                        $stripeSub = \Stripe\Subscription::retrieve($subscriptionId);
                        MembershipSubscription::create([
                            'user_id' => $userId,
                            'plan_id' => $planId,
                            'provider' => 'stripe',
                            'provider_subscription_id' => $subscriptionId,
                            'status' => 'active',
                            'current_period_start' => now()->timestamp,
                            'current_period_end' => $stripeSub->current_period_end
                                ? now()->createFromTimestamp($stripeSub->current_period_end)
                                : now()->addMonth(),
                        ]);
                        User::where('id', $userId)->update([
                            'tier' => 'paid',
                            'subscription_ends_at' => $stripeSub->current_period_end
                                ? now()->createFromTimestamp($stripeSub->current_period_end)
                                : now()->addMonth(),
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Stripe webhook: failed to process checkout', ['error' => $e->getMessage()]);
                    }
                }
                break;

            case 'invoice.payment_succeeded':
                $subscriptionId = $event->data->object->subscription;
                if ($subscriptionId) {
                    $sub = MembershipSubscription::where('provider_subscription_id', $subscriptionId)->latest()->first();
                    if ($sub) {
                        try {
                            $stripeSub = \Stripe\Subscription::retrieve($subscriptionId);
                            $end = $stripeSub->current_period_end
                                ? now()->createFromTimestamp($stripeSub->current_period_end)
                                : now()->addMonth();
                            $sub->update(['current_period_end' => $end, 'status' => 'active']);
                            User::where('id', $sub->user_id)->update(['tier' => 'paid', 'subscription_ends_at' => $end]);
                        } catch (\Exception $e) {
                            Log::error('Stripe webhook: failed to update subscription', ['id' => $subscriptionId]);
                        }
                    }
                }
                break;

            case 'customer.subscription.deleted':
                $stripeSubId = $event->data->object->id;
                $sub = MembershipSubscription::where('provider_subscription_id', $stripeSubId)->latest()->first();
                if ($sub) {
                    $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                    User::where('id', $sub->user_id)->update(['tier' => 'free', 'subscription_ends_at' => null]);
                }
                break;
        }

        return response()->json(['received' => true]);
    }

    public function createPayPalOrder(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:membership_plans,id',
            'interval' => 'required|in:month,year',
        ]);

        $plan = MembershipPlan::findOrFail($request->plan_id);
        $price = (float) ($request->interval === 'year' ? $plan->price_yearly : $plan->price_monthly);
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.client_secret');
        $base = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $token = $this->payPalAuth($clientId, $secret, $base);
        if (!$token) return response()->json(['error' => 'PayPal auth failed'], 500);

        $productId = $this->payPalGetOrCreateProduct($token, $base);
        if (!$productId) return response()->json(['error' => 'Failed to create PayPal product'], 500);

        $planId = $this->payPalCreatePlan($token, $base, $productId, $plan, $request->interval, (float) $price);
        if (!$planId) return response()->json(['error' => 'Failed to create PayPal billing plan'], 500);

        return $this->payPalCreateSubscription($token, $base, $planId, $request);
    }

    private function payPalAuth($clientId, $secret, $base): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$base/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) return null;
        return json_decode($result)->access_token;
    }

    private function payPalGetOrCreateProduct(string $token, string $base): ?string
    {
        // Check if already exists
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$base/v1/catalogs/products");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $token"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200) {
            $products = json_decode($result)->products ?? [];
            foreach ($products as $p) {
                if (($p->name ?? '') === 'PepVGuides Premium') return $p->id;
            }
        }
        // Create new
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$base/v1/catalogs/products");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $token"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'name' => 'PepVGuides Premium',
            'description' => 'Premium membership for pepvguides.com',
            'type' => 'SERVICE',
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 201) return null;
        return json_decode($result)->id;
    }

    private function payPalCreatePlan(string $token, string $base, string $productId, $plan, string $interval, float $price): ?string
    {
        $planData = [
            'product_id' => $productId,
            'name' => $plan->name . ' (' . $interval . 'ly)',
            'description' => $plan->description ?? $plan->name . ' subscription',
            'billing_cycles' => [[
                'frequency' => ['interval_unit' => strtoupper($interval), 'interval_count' => 1],
                'tenure_type' => 'REGULAR',
                'sequence' => 1,
                'total_cycles' => 0,
                'pricing_scheme' => [
                    'fixed_price' => ['value' => number_format($price, 2, '.', ''), 'currency_code' => 'USD'],
                ],
            ]],
            'payment_preferences' => [
                'auto_bill_outstanding' => true,
                'setup_fee_failure_action' => 'CANCEL',
                'payment_failure_threshold' => 3,
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$base/v1/billing/plans");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $token"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($planData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 201) return null;
        return json_decode($result)->id;
    }

    private function payPalCreateSubscription(string $token, string $base, string $planId, Request $request): \Illuminate\Http\JsonResponse
    {
        $subscriptionData = [
            'plan_id' => $planId,
            'start_time' => now()->addMinute()->toIso8601String(),
            'subscriber' => [
                'name' => ['given_name' => $request->user()->name ?? 'Member'],
                'email_address' => $request->user()->email,
            ],
            'application_context' => [
                'brand_name' => 'PepVGuides',
                'locale' => 'en-US',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => config('app.frontend_url') . '/settings/billing?success=1',
                'cancel_url' => config('app.frontend_url') . '/pricing?canceled=1',
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$base/v1/billing/subscriptions");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $token"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($subscriptionData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 201 || $httpCode === 200) {
            $data = json_decode($response);
            return response()->json([
                'id' => $data->id,
                'approval_url' => $data->links[0]->href ?? null,
            ]);
        }

        return response()->json(['error' => 'Failed to create PayPal subscription'], 500);
    }

    public function handlePayPalWebhook(Request $request)
    {
        $payload = $request->getContent();
        $event = json_decode($payload);

        if (!$event || !isset($event->event_type)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        switch ($event->event_type) {
            case 'BILLING.SUBSCRIPTION.CREATED':
            case 'PAYMENT.SALE.COMPLETED':
                $resource = $event->resource ?? (object) [];
                $subscriberEmail = isset($resource->subscriber) ? ($resource->subscriber->email_address ?? null) : null;
                if ($subscriberEmail) {
                    $user = User::where('email', $subscriberEmail)->first();
                    if ($user) {
                        $plan = MembershipPlan::where('active', true)->orderBy('sort_order')->first();
                        if ($plan) {
                            MembershipSubscription::updateOrCreate(
                                ['user_id' => $user->id, 'provider' => 'paypal'],
                                [
                                    'plan_id' => $plan->id,
                                    'provider_subscription_id' => $resource->id ?? 'paypal_' . uniqid(),
                                    'status' => 'active',
                                    'current_period_start' => now(),
                                    'current_period_end' => now()->addMonth(),
                                ]
                            );
                            $user->update(['tier' => 'paid', 'subscription_ends_at' => now()->addMonth()]);
                        }
                    }
                }
                break;

            case 'BILLING.SUBSCRIPTION.CANCELLED':
                $resource = $event->resource ?? (object) [];
                $subId = $resource->id ?? null;
                if ($subId) {
                    $sub = MembershipSubscription::where('provider_subscription_id', $subId)->latest()->first();
                    if ($sub) {
                        $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                        User::where('id', $sub->user_id)->update(['tier' => 'free', 'subscription_ends_at' => null]);
                    }
                }
                break;
        }

        return response()->json(['received' => true]);
    }

    public function cancel(Request $request)
    {
        $user = $request->user();
        $sub = MembershipSubscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'past_due'])
            ->latest()
            ->first();

        if (!$sub) {
            return response()->json(['error' => 'No active subscription found'], 404);
        }

        if ($sub->provider === 'stripe' && $sub->provider_subscription_id) {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            try {
                \Stripe\Subscription::update($sub->provider_subscription_id, [
                    'cancel_at_period_end' => true,
                ]);
                $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                return response()->json(['message' => 'Subscription cancelled. You have access until the end of the billing period.']);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to cancel subscription'], 500);
            }
        }

        $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        return response()->json(['message' => 'Subscription cancelled.']);
    }
}
