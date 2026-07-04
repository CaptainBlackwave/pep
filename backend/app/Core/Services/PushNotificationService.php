<?php

namespace App\Core\Services;

use App\Core\Models\PushSubscription;
use App\Core\Models\User;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationService
{
    private ?WebPush $webPush = null;

    public function __construct()
    {
        $publicKey = config('services.webpush.vapid_public_key');
        $privateKey = config('services.webpush.vapid_private_key');

        if ($publicKey && $privateKey) {
            $auth = [
                'VAPID' => [
                    'subject' => config('app.url'),
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ];
            $this->webPush = new WebPush($auth);
            $this->webPush->setReuseVAPIDHeaders(true);
        }
    }

    public function send(User $user, string $title, string $body, string $url = '/'): void
    {
        if (!$this->webPush) {
            return;
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);

        foreach ($subscriptions as $sub) {
            $this->webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key ?? '',
                    'authToken' => $sub->auth_token ?? '',
                ]),
                $payload,
            );
        }

        foreach ($this->webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            if ($report->isSubscriptionExpired()) {
                $endpoint = $report->getEndpoint();
                PushSubscription::where('endpoint', $endpoint)->delete();
            }
        }
    }
}
