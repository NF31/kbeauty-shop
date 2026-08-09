<?php

use App\Infrastructure\Rabbitmq\DiagnosticCreatedEventValidator;
use App\Infrastructure\Rabbitmq\InvalidDiagnosticCreatedEventException;

beforeEach(function () {
    $this->validator = new DiagnosticCreatedEventValidator;
});

function validDiagnosticCreatedPayload(array $overrides = []): string
{
    return json_encode(array_merge([
        'eventType' => 'diagnostic.created',
        'diagnosticId' => 42,
        'status' => 'pending',
        'occurredAt' => '2026-08-10T10:00:00Z',
    ], $overrides));
}

test('accepts an event matching the contract', function () {
    $event = $this->validator->validate(validDiagnosticCreatedPayload());

    expect($event->diagnosticId)->toBe(42)
        ->and($event->status)->toBe('pending');
});

test('rejects a status outside the contract enum', function () {
    $this->validator->validate(validDiagnosticCreatedPayload(['status' => 'archived']));
})->throws(InvalidDiagnosticCreatedEventException::class);

test('rejects a missing diagnosticId', function () {
    $payload = json_encode([
        'eventType' => 'diagnostic.created',
        'status' => 'pending',
        'occurredAt' => '2026-08-10T10:00:00Z',
    ]);

    $this->validator->validate($payload);
})->throws(InvalidDiagnosticCreatedEventException::class);

test('rejects an unexpected eventType', function () {
    $this->validator->validate(validDiagnosticCreatedPayload(['eventType' => 'diagnostic.updated']));
})->throws(InvalidDiagnosticCreatedEventException::class);

test('rejects an unknown extra field', function () {
    $this->validator->validate(validDiagnosticCreatedPayload(['unexpectedField' => 'x']));
})->throws(InvalidDiagnosticCreatedEventException::class);

test('rejects malformed JSON', function () {
    $this->validator->validate('{not json');
})->throws(InvalidDiagnosticCreatedEventException::class);
