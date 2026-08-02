<?php

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Spatie\Honeypot\Honeypot;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * Même principe que postNewsletterSubscription (NewsletterSubscriptionTest) : simule un
 * envoi humain en dépassant le délai minimum du honeypot (13.4), réutilisé ici pour /contact.
 */
function postContactMessage(TestCase $test, array $data = []): TestResponse
{
    $honeypot = app(Honeypot::class);
    $nameFieldName = $honeypot->nameFieldName();
    $validFromFieldName = $honeypot->validFromFieldName();
    $encryptedValidFrom = $honeypot->encryptedValidFrom();

    $test->travel(2)->seconds();

    return $test->post('/contact', [
        ...$data,
        $nameFieldName => '',
        $validFromFieldName => $encryptedValidFrom,
    ]);
}

test('the contact page is publicly accessible', function () {
    $this->get('/contact')->assertOk()->assertInertia(
        fn ($page) => $page->component('storefront/contact'),
    );
});

test('a valid submission notifies every admin and does not notify staff', function () {
    $this->seed(RolePermissionSeeder::class);
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $response = postContactMessage($this, [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'subject' => 'Question produit',
        'message' => 'Bonjour, ce produit convient-il aux peaux sensibles ?',
    ]);

    $response->assertRedirect();

    $message = ContactMessage::query()->where('email', 'alice@example.com')->sole();

    expect($message->name)->toBe('Alice');
    expect($message->subject)->toBe('Question produit');
    expect($message->read_at)->toBeNull();

    Notification::assertSentTo($admin, ContactMessageReceived::class);
    Notification::assertNotSentTo($staff, ContactMessageReceived::class);
});

test('submitting without required fields is rejected', function () {
    $response = postContactMessage($this, [
        'name' => '',
        'email' => 'not-an-email',
        'subject' => '',
        'message' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
});

test('a filled-in honeypot field is treated as spam', function () {
    Notification::fake();

    $honeypot = app(Honeypot::class);
    $this->travel(2)->seconds();

    $this->post('/contact', [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'subject' => 'Spam',
        'message' => 'Spam message',
        $honeypot->nameFieldName() => 'je suis un robot',
        $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom(),
    ]);

    expect(ContactMessage::query()->where('email', 'bot@example.com')->exists())->toBeFalse();
    Notification::assertNothingSent();
});
