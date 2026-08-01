<?php

use App\Models\NewsletterSubscriber;
use App\Notifications\NewsletterSubscriptionConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('subscribing sends a confirmation email without setting consent_at yet', function () {
    Notification::fake();

    $response = $this->post('/newsletter', [
        'email' => 'client@example.com',
        'consent' => true,
    ]);

    $response->assertRedirect();

    $subscriber = NewsletterSubscriber::query()->where('email', 'client@example.com')->firstOrFail();

    expect($subscriber->consent_at)->toBeNull();
    Notification::assertSentTo($subscriber, NewsletterSubscriptionConfirmation::class);
});

test('subscribing without consent is rejected', function () {
    $response = $this->post('/newsletter', [
        'email' => 'client@example.com',
        'consent' => false,
    ]);

    $response->assertSessionHasErrors('consent');
    expect(NewsletterSubscriber::query()->where('email', 'client@example.com')->exists())->toBeFalse();
});

test('subscribing twice with the same email does not duplicate the row', function () {
    Notification::fake();

    $this->post('/newsletter', ['email' => 'client@example.com', 'consent' => true]);
    $this->post('/newsletter', ['email' => 'client@example.com', 'consent' => true]);

    expect(NewsletterSubscriber::query()->where('email', 'client@example.com')->count())->toBe(1);
});

test('an already confirmed subscriber does not receive another confirmation email', function () {
    Notification::fake();

    $subscriber = NewsletterSubscriber::factory()->create(['email' => 'client@example.com']);

    $this->post('/newsletter', ['email' => 'client@example.com', 'consent' => true]);

    Notification::assertNothingSent();
    expect($subscriber->refresh()->consent_at)->not->toBeNull();
});

test('resubscribing after unsubscription requires a new confirmation', function () {
    Notification::fake();

    $subscriber = NewsletterSubscriber::factory()->create([
        'email' => 'client@example.com',
        'unsubscribed_at' => now(),
    ]);

    $this->post('/newsletter', ['email' => 'client@example.com', 'consent' => true]);

    expect($subscriber->refresh())
        ->consent_at->toBeNull()
        ->unsubscribed_at->toBeNull();
    Notification::assertSentTo($subscriber, NewsletterSubscriptionConfirmation::class);
});

test('visiting the signed confirmation link sets consent_at', function () {
    $subscriber = NewsletterSubscriber::factory()->create(['consent_at' => null]);

    $url = URL::temporarySignedRoute(
        'storefront.newsletter.confirm',
        now()->addDays(7),
        ['subscriber' => $subscriber->id],
    );

    $this->get($url)->assertRedirect('/');

    expect($subscriber->refresh()->consent_at)->not->toBeNull();
});

test('an unsigned confirmation link is rejected', function () {
    $subscriber = NewsletterSubscriber::factory()->create(['consent_at' => null]);

    $this->get("/newsletter/confirmer/{$subscriber->id}")->assertForbidden();

    expect($subscriber->refresh()->consent_at)->toBeNull();
});
