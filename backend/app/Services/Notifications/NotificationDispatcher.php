<?php

namespace App\Services\Notifications;

use App\Enums\DevicePlatform;
use App\Enums\NotificationChannel;
use App\Enums\TargetType;
use App\Models\DeviceInstallation;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class NotificationDispatcher
{
    public function __construct(
        private PushSender $pushSender,
        private EmailSender $emailSender,
    ) {}

    public function dispatch(
        string $type,
        array $recipientUserIds,
        ?string $organizationId,
        ?string $targetType,
        ?string $targetId,
        array $params = [],
    ): void {
        if ($targetType !== null && ! in_array($targetType, TargetType::values(), true)) {
            throw new \InvalidArgumentException("Invalid target_type: {$targetType}");
        }

        $recipients = User::whereIn('id', $recipientUserIds)->get();

        foreach ($recipients as $recipient) {
            $this->createInAppNotification($recipient, $organizationId, $type, $targetType, $targetId, $params);
        }

        foreach ($recipients as $recipient) {
            $this->queuePushAndEmail($recipient, $organizationId, $type, $targetType, $targetId, $params);
        }
    }

    private function createInAppNotification(
        User $recipient,
        ?string $organizationId,
        string $type,
        ?string $targetType,
        ?string $targetId,
        array $params,
    ): void {
        Notification::create([
            'id' => Str::ulid(),
            'organization_id' => $organizationId,
            'user_id' => $recipient->id,
            'type' => $type,
            'title_key' => "notification.{$type}.title",
            'title_params' => $params,
            'body_key' => "notification.{$type}.body",
            'body_params' => $params,
            'data' => array_filter([
                'target_type' => $targetType,
                'target_id' => $targetId,
            ]),
        ]);
    }

    private function queuePushAndEmail(
        User $recipient,
        ?string $organizationId,
        string $type,
        ?string $targetType,
        ?string $targetId,
        array $params,
    ): void {
        $preferences = $this->getPreferences($recipient, $organizationId, $type);

        if ($preferences[NotificationChannel::Push->value] ?? true) {
            $this->queuePush($recipient, $type, $targetType, $targetId, $params);
        }

        if ($preferences[NotificationChannel::Email->value] ?? true) {
            $this->queueEmail($recipient, $type, $targetType, $targetId, $params);
        }
    }

    private function getPreferences(User $recipient, ?string $organizationId, string $type): array
    {
        $query = NotificationPreference::where('user_id', $recipient->id)
            ->where('type', $type)
            ->where(function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId)
                  ->orWhereNull('organization_id');
            });

        return $query->pluck('enabled', 'channel')->toArray();
    }

    private function queuePush(
        User $recipient,
        string $type,
        ?string $targetType,
        ?string $targetId,
        array $params,
    ): void {
        $installations = DeviceInstallation::where('user_id', $recipient->id)
            ->whereNull('revoked_at')
            ->get();

        foreach ($installations as $installation) {
            $locale = $installation->locale ?: $recipient->locale ?: config('app.locale', 'mk');
            App::setLocale($locale);

            $title = Lang::get("notification.{$type}.title", $params);
            $body = Lang::get("notification.{$type}.body", $params);

            $data = array_filter([
                'target_type' => $targetType,
                'target_id' => $targetId,
                'notification_type' => $type,
            ]);

            $this->pushSender->send($installation->push_token, $title, $body, $data);
        }
    }

    private function queueEmail(
        User $recipient,
        string $type,
        ?string $targetType,
        ?string $targetId,
        array $params,
    ): void {
        $locale = $recipient->locale ?: config('app.locale', 'mk');
        App::setLocale($locale);

        $subject = Lang::get("notification.{$type}.email_subject", $params);
        $html = Lang::get("notification.{$type}.email_html", $params);

        $data = array_filter([
            'target_type' => $targetType,
            'target_id' => $targetId,
            'notification_type' => $type,
        ]);

        $this->emailSender->send($recipient->email, $subject, $html, $data);
    }
}