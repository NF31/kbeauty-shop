<?php

use App\Models\NewsletterSubscriber;
use App\Notifications\NewsletterSubscriptionConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Spatie\Honeypot\Honeypot;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * Simule un envoi humain : recupere des champs honeypot fraichement generes puis avance
 * l'horloge de test pour depasser leur `valid_from` (13.4), comme le ferait un vrai
 * visiteur qui met plus d'une seconde a remplir le formulaire.
 */
function postNewsletterSubscription(TestCase $test, array $data = []): TestResponse
{
    $honeypot = app(Honeypot::class);
    $nameFieldName = $honeypot->nameFieldName();
    $validFromFieldName = $honeypot->validFromFieldName();
    $encryptedValidFrom = $honeypot->encryptedValidFrom();

    $test->travel(2)->seconds();

    return $test->post('/newsletter', [
        ...$data,
        $nameFieldName => '',
        $validFromFieldName => $encryptedValidFrom,
    ]);
}

test('subscribing sends a confirmation email without setting consent_at yet', function () {
    Notification::fake();

    $response = postNewsletterSubscription($this, ['email' => 'client@example.com', 'consent' => true]);

    $response->assertRedirect();

    $subscriber = NewsletterSubscriber::query()->where('email', 'client@example.com')->firstOrFail();

    expect($subscriber->consent_at)->toBeNull();
    Notification::assertSentTo($subscriber, NewsletterSubscriptionConfirmation::class);
});

test('subscribing without consent is rejected', function () {
    $response = postNewsletterSubscription($this, ['email' => 'client@example.com', 'consent' => false]);

    $response->assertSessionHasErrors('consent');
    expect(NewsletterSubscriber::query()->where('email', 'client@example.com')->exists())->toBeFalse();
});

test('subscribing twice with the same email does not duplicate the row', function () {
    Notification::fake();

    postNewsletterSubscription($this, ['email' => 'client@example.com', 'consent' => true]);
    postNewsletterSubscription($this, ['email' => 'client@example.com', 'consent' => true]);

    expect(NewsletterSubscriber::query()->where('email', 'client@example.com')->count())->toBe(1);
});

test('an already confirmed subscriber does not receive another confirmation email', function () {
    Notification::fake();

    $subscriber = NewsletterSubscriber::factory()->create(['email' => 'client@example.com']);

    postNewsletterSubscription($this, ['email' => 'client@example.com', 'consent' => true]);

    Notification::assertNothingSent();
    expect($subscriber->refresh()->consent_at)->not->toBeNull();
});

test('resubscribing after unsubscription requires a new confirmation', function () {
    Notification::fake();

    $subscriber = NewsletterSubscriber::factory()->create([
        'email' => 'client@example.com',
        'unsubscribed_at' => now(),
    ]);

    postNewsletterSubscription($this, ['email' => 'client@example.com', 'consent' => true]);

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

test('a filled-in honeypot field is treated as spam', function () {
    Notification::fake();

    $honeypot = app(Honeypot::class);
    $this->travel(2)->seconds();

    $this->post('/newsletter', [
        'email' => 'bot@example.com',
        'consent' => true,
        $honeypot->nameFieldName() => 'je suis un robot',
        $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom(),
    ]);

    expect(NewsletterSubscriber::query()->where('email', 'bot@example.com')->exists())->toBeFalse();
    Notification::assertNothingSent();
});

test('a submission faster than the honeypot delay is treated as spam', function () {
    Notification::fake();

    $honeypot = app(Honeypot::class);

    // Pas de saut dans le temps ici : simule un bot qui soumet instantanement,
    // avant le `valid_from` genere par le honeypot.
    $this->post('/newsletter', [
        'email' => 'bot@example.com',
        'consent' => true,
        $honeypot->nameFieldName() => '',
        $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom(),
    ]);

    expect(NewsletterSubscriber::query()->where('email', 'bot@example.com')->exists())->toBeFalse();
    Notification::assertNothingSent();
});

test('a submission missing the honeypot fields entirely is treated as spam', function () {
    Notification::fake();

    $this->post('/newsletter', [
        'email' => 'bot@example.com',
        'consent' => true,
    ]);

    expect(NewsletterSubscriber::query()->where('email', 'bot@example.com')->exists())->toBeFalse();
    Notification::assertNothingSent();
});
