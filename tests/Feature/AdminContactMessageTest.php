<?php

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReply;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('a guest is redirected to login', function () {
    $this->get('/admin/messages')->assertRedirect('/login');
});

test('admin, staff and support can all view the messages list', function () {
    foreach (['admin', 'staff', 'support'] as $role) {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get('/admin/messages')->assertOk();
    }
});

test('the list exposes the unread count and read status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    ContactMessage::factory()->create(['subject' => 'Non lu']);
    ContactMessage::factory()->read()->create(['subject' => 'Déjà lu']);

    $this->actingAs($admin)->get('/admin/messages')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/contact-messages/index')
            ->where('unreadCount', 1));
});

test('viewing a message marks it as read', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $message = ContactMessage::factory()->create();

    $this->actingAs($admin)->get("/admin/messages/{$message->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/contact-messages/show')
            ->where('message.id', $message->id));

    expect($message->refresh()->read_at)->not->toBeNull();
});

test('replying sends an email to the customer and marks the message as replied', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $message = ContactMessage::factory()->create(['email' => 'client@example.com']);

    $this->actingAs($admin)
        ->post("/admin/messages/{$message->id}/reply", [
            'reply' => 'Merci pour ton message, voici la réponse.',
        ])
        ->assertRedirect();

    expect($message->refresh()->replied_at)->not->toBeNull();

    $log = $message->replies()->sole();
    expect($log->message)->toBe('Merci pour ton message, voici la réponse.');
    expect($log->user_id)->toBe($admin->id);

    Notification::assertSentOnDemand(
        ContactMessageReply::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === ['client@example.com' => $message->name],
    );
});

test('the show page exposes the replies thread', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $message = ContactMessage::factory()->create();
    $message->replies()->create(['user_id' => $admin->id, 'message' => 'Première réponse.']);

    $this->actingAs($admin)->get("/admin/messages/{$message->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('replies.0.message', 'Première réponse.')
            ->where('replies.0.authorName', $admin->name));
});

test('replying without a message is rejected', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $message = ContactMessage::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/messages/{$message->id}/reply", ['reply' => ''])
        ->assertSessionHasErrors('reply');

    expect($message->refresh()->replied_at)->toBeNull();
});

test('a guest cannot reply to a message', function () {
    $message = ContactMessage::factory()->create();

    $this->post("/admin/messages/{$message->id}/reply", ['reply' => 'Test'])
        ->assertRedirect('/login');
});
