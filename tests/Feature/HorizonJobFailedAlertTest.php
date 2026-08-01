<?php

use App\Listeners\NotifyAdminsOfFailedHorizonJob;
use App\Models\User;
use App\Notifications\HorizonJobFailedAlert;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\Notification;
use Laravel\Horizon\Events\JobFailed;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('a failed Horizon job notifies admins only', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $job = Mockery::mock(Job::class);
    $job->shouldReceive('resolveName')->andReturn('App\\Notifications\\OrderConfirmation');
    $job->shouldReceive('getConnectionName')->andReturn('redis');
    $job->shouldReceive('getQueue')->andReturn('default');

    // Dispatch direct plutot que via Event::dispatch() : les listeners internes
    // de Horizon abonnes au meme evenement (MarkJobAsFailed, StoreTagsForFailedJob)
    // ecrivent dans Redis a partir d'un JobPayload complet, absent ici puisqu'on
    // simule l'evenement plutot que de faire echouer un vrai job en queue.
    $event = new JobFailed(new Exception('Boom'), $job, json_encode(['id' => 'test-job-id']));

    app(NotifyAdminsOfFailedHorizonJob::class)->handle($event);

    Notification::assertSentTo($admin, HorizonJobFailedAlert::class);
    Notification::assertNotSentTo($staff, HorizonJobFailedAlert::class);
});
