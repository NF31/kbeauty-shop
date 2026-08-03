<?php

use App\Rules\ValidTurnstileToken;
use Illuminate\Support\Facades\Http;

test('passes when the secret key is not configured', function () {
    config(['services.turnstile.secret_key' => null]);

    $failed = false;
    (new ValidTurnstileToken)->validate('cf-turnstile-response', '', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('fails when configured but the token is empty', function () {
    config(['services.turnstile.secret_key' => 'test-secret']);

    $failed = false;
    (new ValidTurnstileToken)->validate('cf-turnstile-response', '', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('passes when cloudflare confirms the token', function () {
    config(['services.turnstile.secret_key' => 'test-secret']);
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
    ]);

    $failed = false;
    (new ValidTurnstileToken)->validate('cf-turnstile-response', 'valid-token', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('fails when cloudflare rejects the token', function () {
    config(['services.turnstile.secret_key' => 'test-secret']);
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false], 200),
    ]);

    $failed = false;
    (new ValidTurnstileToken)->validate('cf-turnstile-response', 'bad-token', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('fails when cloudflare is unreachable', function () {
    config(['services.turnstile.secret_key' => 'test-secret']);
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([], 500),
    ]);

    $failed = false;
    (new ValidTurnstileToken)->validate('cf-turnstile-response', 'some-token', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});
