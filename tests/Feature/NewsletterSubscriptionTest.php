<?php

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a visitor can subscribe to the newsletter with explicit consent', function () {
    $response = $this->post('/newsletter', [
        'email' => 'client@example.com',
        'consent' => true,
    ]);

    $response->assertRedirect();

    $subscriber = NewsletterSubscriber::query()->where('email', 'client@example.com')->firstOrFail();

    expect($subscriber->consent_at)->not->toBeNull();
    expect($subscriber->unsubscribed_at)->toBeNull();
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
    $this->post('/newsletter', ['email' => 'client@example.com', 'consent' => true]);
    $this->post('/newsletter', ['email' => 'client@example.com', 'consent' => true]);

    expect(NewsletterSubscriber::query()->where('email', 'client@example.com')->count())->toBe(1);
});

test('resubscribing clears a previous unsubscription', function () {
    $subscriber = NewsletterSubscriber::factory()->create([
        'email' => 'client@example.com',
        'unsubscribed_at' => now(),
    ]);

    $this->post('/newsletter', ['email' => 'client@example.com', 'consent' => true]);

    expect($subscriber->refresh()->unsubscribed_at)->toBeNull();
});
