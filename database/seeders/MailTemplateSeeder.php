<?php

namespace Database\Seeders;

use App\Models\MailTemplate;
use App\Services\MailService;
use Illuminate\Database\Seeder;

/**
 * Seeds the default English email templates for each MailService template type.
 *
 * Templates use {$var} placeholders that MailService substitutes at send time:
 *   {$username}, {$code}, {$expired} (minutes), {$link}.
 *
 * Idempotent: existing templates of a given type are updated in place.
 */
class MailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'type' => MailService::TYPE_LOGIN_VERIFY,
                'name' => 'Login Verification',
                'subject' => 'Your login verification code',
                'contents' => $this->wrap(
                    '<p>Hello {$username},</p>'
                    .'<p>Your login verification code is:</p>'
                    .'<p style="font-size:22px;font-weight:bold;letter-spacing:3px;">{$code}</p>'
                    .'<p>This code expires in {$expired} minutes. If you did not request it, please ignore this email.</p>'
                ),
            ],
            [
                'type' => MailService::TYPE_INVITATION,
                'name' => 'Invitation',
                'subject' => 'You have been invited',
                'contents' => $this->wrap(
                    '<p>Hello {$username},</p>'
                    .'<p>You have been invited to join. Click the link below to accept your invitation:</p>'
                    .'<p><a href="{$link}">{$link}</a></p>'
                    .'<p>This invitation link expires in {$expired} minutes.</p>'
                ),
            ],
            [
                'type' => MailService::TYPE_ALARM,
                'name' => 'Alarm Notification',
                'subject' => 'Security alarm notification',
                'contents' => $this->wrap(
                    '<p>Hello {$username},</p>'
                    .'<p>A security event has been detected on device <strong>{$device}</strong>:</p>'
                    .'<p>{$message}</p>'
                    .'<p>If this was not expected, please review your account activity immediately.</p>'
                ),
            ],
            [
                'type' => MailService::TYPE_PASSWORD_RESET,
                'name' => 'Password Reset',
                'subject' => 'Reset your password',
                'contents' => $this->wrap(
                    '<p>Hello {$username},</p>'
                    .'<p>We received a request to reset your password. Click the link below to choose a new one:</p>'
                    .'<p><a href="{$link}">{$link}</a></p>'
                    .'<p>This link expires in {$expired} minutes. If you did not request a reset, you can safely ignore this email.</p>'
                ),
            ],
        ];

        foreach ($templates as $template) {
            MailTemplate::updateOrCreate(
                ['type' => $template['type']],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'contents' => $template['contents'],
                ],
            );
        }
    }

    /**
     * Wrap a fragment in a minimal HTML document shell.
     */
    private function wrap(string $body): string
    {
        return '<!DOCTYPE html><html><body style="font-family:Arial,Helvetica,sans-serif;color:#333;">'
            .$body
            .'</body></html>';
    }
}
