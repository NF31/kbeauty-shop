<?php

test('resend transport is registered as a mailer', function () {
    expect(config('mail.mailers.resend.transport'))->toBe('resend');
});

test('resend service key reads from RESEND_API_KEY', function () {
    config(['services.resend.key' => 're_test_key']);

    expect(config('services.resend.key'))->toBe('re_test_key');
});
