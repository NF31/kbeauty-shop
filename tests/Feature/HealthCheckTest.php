<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Health\Checks\Checks\BackupsCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Enums\Status;
use Spatie\Health\Facades\Health;

uses(RefreshDatabase::class);

test('database, redis, queue, horizon, disk and backups checks are registered', function () {
    $checkClasses = Health::registeredChecks()
        ->map(fn ($check) => $check::class)
        ->all();

    expect($checkClasses)->toContain(
        DatabaseCheck::class,
        RedisCheck::class,
        QueueCheck::class,
        HorizonCheck::class,
        UsedDiskSpaceCheck::class,
        BackupsCheck::class,
    );
});

test('a guest is redirected to login', function () {
    $this->get('/admin/health')->assertRedirect('/login');
});

test('the staff role cannot view the health dashboard', function () {
    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)->get('/admin/health')->assertForbidden();
});

test('the admin role can view the health dashboard', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin/health')->assertOk();
});

test('the backups check finds a backup nested in the app-name subfolder', function () {
    Storage::fake('backups');
    Storage::disk('backups')->put(
        config('backup.backup.name').'/2026-08-05-00-23-33.zip',
        'contenu-de-test',
    );

    $result = BackupsCheck::new()
        ->onDisk('backups')
        ->locatedAt(config('backup.backup.name'))
        ->run();

    expect($result->status)->toBe(Status::ok());
});
