<?php

test('the .env files are excluded from backups so plaintext secrets never leave the server', function () {
    $excluded = config('backup.backup.source.files.exclude');

    expect($excluded)
        ->toContain(base_path('.env'))
        ->toContain(base_path('.env.backup'));
});

test('backups are stored on their own dedicated disk', function () {
    expect(config('backup.backup.destination.disks'))->toBe(['backups']);
    expect(config('filesystems.disks.backups'))->not->toBeNull();
});
