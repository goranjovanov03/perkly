<?php

namespace Tests\Unit\Services\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\DeviceInstallation;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\PushSender;
use App\Services\Notifications\EmailSender;
use App\Enums\TargetType;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\App;

class NotificationDispatcherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    protected function assignRoleToUserForOrganization(User $user, string $roleName, Organization $organization): void
    {
        $Role = app(config('permission.models.role'));
        $role = $Role::where('name', $roleName)->where('guard_name', 'sanctum')->first();
        $user->roles()->attach($role, ['team_id' => $organization->id]);
    }

    private function createDispatcher(array &$pushCalls, array &$emailCalls): NotificationDispatcher
    {
        $pushSender = new class($pushCalls) implements PushSender {
            public function __construct(public array &$calls) {}
            public function send(string $token, string $title, string $body, array $data): void
            {
                $this->calls[] = compact('token', 'title', 'body', 'data');
            }
        };

        $emailSender = new class($emailCalls) implements EmailSender {
            public function __construct(public array &$calls) {}
            public function send(string $email, string $subject, string $html, array $data = []): void
            {
                $this->calls[] = compact('email', 'subject', 'html', 'data');
            }
        };

        return new NotificationDispatcher($pushSender, $emailSender);
    }

    public function test_dispatch_creates_in_app_notification_per_recipient(): void
    {
        $pushCalls = [];
        $emailCalls = [];
        $organization = Organization::factory()->create();
        $user1 = User::factory()->create(['locale' => 'mk']);
        $user2 = User::factory()->create(['locale' => 'en']);

        OrganizationMembership::factory()->create([
            'user_id' => $user1->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        OrganizationMembership::factory()->create([
            'user_id' => $user2->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $dispatcher = $this->createDispatcher($pushCalls, $emailCalls);

        $dispatcher->dispatch('test', [$user1->id, $user2->id], $organization->id, TargetType::Test->value, 'target-123', ['name' => 'John']);

        $this->assertDatabaseCount('notifications', 2);
        $notifications = Notification::orderBy('created_at')->get();

        $this->assertEquals($user1->id, $notifications[0]->user_id);
        $this->assertEquals('test', $notifications[0]->type);
        $this->assertEquals('notification.test.title', $notifications[0]->title_key);
        $this->assertEquals(['name' => 'John'], $notifications[0]->title_params);
        $this->assertEquals('notification.test.body', $notifications[0]->body_key);
        $this->assertEquals(['target_type' => 'test', 'target_id' => 'target-123'], $notifications[0]->data);

        $this->assertEquals($user2->id, $notifications[1]->user_id);
    }

    public function test_dispatch_queues_push_for_recipients_with_push_preference_enabled(): void
    {
        $pushCalls = [];
        $emailCalls = [];
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['locale' => 'mk']);

        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        DeviceInstallation::factory()->create([
            'user_id' => $user->id,
            'platform' => 'android',
            'push_token' => 'token-123',
            'locale' => 'mk',
        ]);

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'channel' => 'push',
            'type' => 'test',
            'enabled' => true,
        ]);

        $dispatcher = $this->createDispatcher($pushCalls, $emailCalls);

        $dispatcher->dispatch('test', [$user->id], $organization->id, TargetType::Test->value, 'target-123', ['name' => 'John']);

        $this->assertCount(1, $pushCalls);
        $this->assertEquals('token-123', $pushCalls[0]['token']);
        $this->assertStringContainsString('Тестово известување', $pushCalls[0]['title']);
        $this->assertStringContainsString('тестово известување за John', $pushCalls[0]['body']);
        $this->assertEquals(['target_type' => 'test', 'target_id' => 'target-123', 'notification_type' => 'test'], $pushCalls[0]['data']);
    }

    public function test_dispatch_does_not_queue_push_when_preference_disabled(): void
    {
        $pushCalls = [];
        $emailCalls = [];
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['locale' => 'mk']);

        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        DeviceInstallation::factory()->create([
            'user_id' => $user->id,
            'platform' => 'android',
            'push_token' => 'token-123',
            'locale' => 'mk',
        ]);

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'channel' => 'push',
            'type' => 'test',
            'enabled' => false,
        ]);

        $dispatcher = $this->createDispatcher($pushCalls, $emailCalls);

        $dispatcher->dispatch('test', [$user->id], $organization->id, TargetType::Test->value, 'target-123', ['name' => 'John']);

        $this->assertCount(0, $pushCalls);
    }

    public function test_dispatch_queues_email_for_recipients_with_email_preference_enabled(): void
    {
        $pushCalls = [];
        $emailCalls = [];
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['locale' => 'mk', 'email' => 'user@example.com']);

        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'channel' => 'email',
            'type' => 'test',
            'enabled' => true,
        ]);

        $dispatcher = $this->createDispatcher($pushCalls, $emailCalls);

        $dispatcher->dispatch('test', [$user->id], $organization->id, TargetType::Test->value, 'target-123', ['name' => 'John']);

        $this->assertCount(1, $emailCalls);
        $this->assertEquals('user@example.com', $emailCalls[0]['email']);
        $this->assertStringContainsString('Тестово известување', $emailCalls[0]['subject']);
        $this->assertStringContainsString('тестово известување за John', $emailCalls[0]['html']);
        $this->assertEquals(['target_type' => 'test', 'target_id' => 'target-123', 'notification_type' => 'test'], $emailCalls[0]['data']);
    }

    public function test_dispatch_does_not_queue_email_when_preference_disabled(): void
    {
        $pushCalls = [];
        $emailCalls = [];
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['locale' => 'mk', 'email' => 'user@example.com']);

        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'channel' => 'email',
            'type' => 'test',
            'enabled' => false,
        ]);

        $dispatcher = $this->createDispatcher($pushCalls, $emailCalls);

        $dispatcher->dispatch('test', [$user->id], $organization->id, TargetType::Test->value, 'target-123', ['name' => 'John']);

        $this->assertCount(0, $emailCalls);
    }

    public function test_dispatch_respects_locale_for_push_rendering(): void
    {
        $pushCalls = [];
        $emailCalls = [];
        $organization = Organization::factory()->create();
        $userMk = User::factory()->create(['locale' => 'mk']);
        $userEn = User::factory()->create(['locale' => 'en']);

        OrganizationMembership::factory()->create([
            'user_id' => $userMk->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        OrganizationMembership::factory()->create([
            'user_id' => $userEn->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        DeviceInstallation::factory()->create([
            'user_id' => $userMk->id,
            'platform' => 'android',
            'push_token' => 'token-mk',
            'locale' => 'mk',
        ]);
        DeviceInstallation::factory()->create([
            'user_id' => $userEn->id,
            'platform' => 'ios',
            'push_token' => 'token-en',
            'locale' => 'en',
        ]);

        NotificationPreference::factory()->create([
            'user_id' => $userMk->id,
            'organization_id' => $organization->id,
            'channel' => 'push',
            'type' => 'test',
            'enabled' => true,
        ]);
        NotificationPreference::factory()->create([
            'user_id' => $userEn->id,
            'organization_id' => $organization->id,
            'channel' => 'push',
            'type' => 'test',
            'enabled' => true,
        ]);

        $dispatcher = $this->createDispatcher($pushCalls, $emailCalls);

        $dispatcher->dispatch('test', [$userMk->id, $userEn->id], $organization->id, TargetType::Test->value, 'target-123', ['name' => 'John']);

        $this->assertCount(2, $pushCalls);

        $mkCall = collect($pushCalls)->firstWhere('token', 'token-mk');
        $enCall = collect($pushCalls)->firstWhere('token', 'token-en');

        $this->assertStringContainsString('Тестово известување', $mkCall['title']);
        $this->assertStringContainsString('Test Notification', $enCall['title']);
    }

    public function test_dispatch_respects_locale_for_email_rendering(): void
    {
        $pushCalls = [];
        $emailCalls = [];
        $organization = Organization::factory()->create();
        $userMk = User::factory()->create(['locale' => 'mk', 'email' => 'mk@example.com']);
        $userEn = User::factory()->create(['locale' => 'en', 'email' => 'en@example.com']);

        OrganizationMembership::factory()->create([
            'user_id' => $userMk->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        OrganizationMembership::factory()->create([
            'user_id' => $userEn->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        NotificationPreference::factory()->create([
            'user_id' => $userMk->id,
            'organization_id' => $organization->id,
            'channel' => 'email',
            'type' => 'test',
            'enabled' => true,
        ]);
        NotificationPreference::factory()->create([
            'user_id' => $userEn->id,
            'organization_id' => $organization->id,
            'channel' => 'email',
            'type' => 'test',
            'enabled' => true,
        ]);

        $dispatcher = $this->createDispatcher($pushCalls, $emailCalls);

        $dispatcher->dispatch('test', [$userMk->id, $userEn->id], $organization->id, TargetType::Test->value, 'target-123', ['name' => 'John']);

        $this->assertCount(2, $emailCalls);

        $mkCall = collect($emailCalls)->firstWhere('email', 'mk@example.com');
        $enCall = collect($emailCalls)->firstWhere('email', 'en@example.com');

        $this->assertStringContainsString('Тестово известување', $mkCall['subject']);
        $this->assertStringContainsString('Test Notification', $enCall['subject']);
    }

    public function test_dispatch_throws_on_invalid_target_type(): void
    {
        $pushCalls = [];
        $emailCalls = [];
        $dispatcher = $this->createDispatcher($pushCalls, $emailCalls);
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid target_type: invalid_type');

        $dispatcher->dispatch('test', [$user->id], $organization->id, 'invalid_type', 'target-123');
    }

    public function test_dispatch_with_null_organization_and_null_target(): void
    {
        $pushCalls = [];
        $emailCalls = [];
        $user = User::factory()->create(['locale' => 'mk']);

        DeviceInstallation::factory()->create([
            'user_id' => $user->id,
            'platform' => 'android',
            'push_token' => 'token-123',
            'locale' => 'mk',
        ]);

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'organization_id' => null,
            'channel' => 'push',
            'type' => 'test',
            'enabled' => true,
        ]);

        $dispatcher = $this->createDispatcher($pushCalls, $emailCalls);

        $dispatcher->dispatch('test', [$user->id], null, null, null, ['name' => 'John']);

        $this->assertDatabaseCount('notifications', 1);
        $notification = Notification::first();
        $this->assertNull($notification->organization_id);
        $this->assertNull($notification->data['target_type'] ?? null);
        $this->assertNull($notification->data['target_id'] ?? null);
    }

    public function test_dispatch_does_not_send_push_to_revoked_installations(): void
    {
        $pushCalls = [];
        $emailCalls = [];
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['locale' => 'mk']);

        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        DeviceInstallation::factory()->create([
            'user_id' => $user->id,
            'platform' => 'android',
            'push_token' => 'active-token',
            'locale' => 'mk',
            'revoked_at' => null,
        ]);
        DeviceInstallation::factory()->create([
            'user_id' => $user->id,
            'platform' => 'ios',
            'push_token' => 'revoked-token',
            'locale' => 'mk',
            'revoked_at' => now(),
        ]);

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'channel' => 'push',
            'type' => 'test',
            'enabled' => true,
        ]);

        $dispatcher = $this->createDispatcher($pushCalls, $emailCalls);

        $dispatcher->dispatch('test', [$user->id], $organization->id, TargetType::Test->value, 'target-123', ['name' => 'John']);

        $this->assertCount(1, $pushCalls);
        $this->assertEquals('active-token', $pushCalls[0]['token']);
    }
}