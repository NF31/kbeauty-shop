<?php

use App\Services\AiCoreDiagnosticClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('a photo is required to run the diagnostic', function () {
    $this->post('/diagnostic-peau', ['skin_type' => 'normale'])
        ->assertInvalid(['photo']);
});

test('a real analysis is displayed when kbeauty-ai-core-service succeeds', function () {
    $this->mock(AiCoreDiagnosticClient::class, function ($mock) {
        $mock->shouldReceive('analyzeSkin')->once()->andReturn([
            'diagnosticId' => 42,
            'analysis' => 'Peau plutot equilibree, quelques rougeurs.',
            'scores' => ['rougeurs' => 30, 'hydratation' => 70],
        ]);
    });

    $response = $this->post('/diagnostic-peau', [
        'skin_type' => 'normale',
        'photo' => UploadedFile::fake()->image('face.jpg'),
    ]);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('result.diagnosticId', 42)
        ->where('result.analysis', 'Peau plutot equilibree, quelques rougeurs.')
        ->where('result.scores.rougeurs', 30));
});

test('the form is redisplayed with an error when kbeauty-ai-core-service fails or is unreachable', function () {
    $this->mock(AiCoreDiagnosticClient::class, function ($mock) {
        $mock->shouldReceive('analyzeSkin')->once()->andReturn(null);
    });

    $this->post('/diagnostic-peau', [
        'skin_type' => 'normale',
        'photo' => UploadedFile::fake()->image('face.jpg'),
    ])->assertInvalid(['photo']);
});
