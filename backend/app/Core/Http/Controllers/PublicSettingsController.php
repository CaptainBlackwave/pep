<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\Setting;

class PublicSettingsController extends Controller
{
    public function index()
    {
        return response()->json([
            'telegram_url' => Setting::where('key', 'telegram_url')->value('value')
                ?? config('app.telegram_url')
                ?? 'https://t.me/peptidevendors',
            'legal_pages' => $this->legalPages(),
        ]);
    }

    private function legalPages(): array
    {
        return [
            'terms' => [
                'title' => Setting::where('key', 'legal_terms_title')->value('value') ?: 'Terms of Service',
                'paragraphs' => $this->paragraphs(
                    Setting::where('key', 'legal_terms')->value('value'),
                    [
                        'By using Peptide Vendors, you agree to use the community for lawful, educational discussion and to follow all community rules and moderation decisions.',
                        'Content is provided by community members and administrators for informational purposes. You are responsible for verifying information before relying on it.',
                        'Accounts may be restricted or removed for abuse, unsafe conduct, spam, evasion, or attempts to use the site as a marketplace.',
                    ]
                ),
            ],
            'privacy' => [
                'title' => Setting::where('key', 'legal_privacy_title')->value('value') ?: 'Privacy Policy',
                'paragraphs' => $this->paragraphs(
                    Setting::where('key', 'legal_privacy')->value('value'),
                    [
                        'Peptide Vendors keeps account data, profile preferences, messages, submissions, and moderation records only for operating the private community.',
                        'We use your information to authenticate access, show community content, support account settings, prevent abuse, and improve reliability.',
                        'Do not post private medical, payment, or identifying information in public areas. You can manage profile visibility, notifications, privacy, sessions, and API tokens from account settings.',
                    ]
                ),
            ],
            'rules' => [
                'title' => Setting::where('key', 'legal_rules_title')->value('value') ?: 'Community Rules',
                'paragraphs' => $this->paragraphs(
                    Setting::where('key', 'legal_rules')->value('value'),
                    [
                        'Keep discussion educational, evidence-led, and respectful. Personal attacks, harassment, spam, scams, and transaction coordination are not allowed.',
                        'Vendor reviews and lab-result submissions should be specific, honest, and batch-aware where possible. Moderators may hide incomplete, unsafe, promotional, or unverifiable submissions.',
                        'This community is for information sharing and harm-reduction context only. It is not medical advice, legal advice, or a marketplace.',
                    ]
                ),
            ],
        ];
    }

    private function paragraphs(?string $value, array $fallback): array
    {
        if (!$value) {
            return $fallback;
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return collect($decoded)->map(fn ($line) => trim((string) $line))->filter()->values()->all();
        }

        return collect(preg_split('/\R{2,}/', $value) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all() ?: $fallback;
    }
}
