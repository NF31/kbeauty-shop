<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\BackupsCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class HealthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Health::checks([
            DatabaseCheck::new(),
            RedisCheck::new(),
            QueueCheck::new(),
            HorizonCheck::new(),
            UsedDiskSpaceCheck::new(),
            // ->locatedAt() est indispensable : BackupsCheck liste uniquement la
            // racine du disque (pas de recursion), or spatie/laravel-backup range
            // chaque sauvegarde dans un sous-dossier nomme d'apres config('backup.backup.name')
            // (ex. "kbeauty-shop/2026-08-05-...zip") - sans ce glob, le check
            // ne trouve jamais aucune sauvegarde et echoue toujours en "No backups found".
            BackupsCheck::new()
                ->onDisk('backups')
                ->locatedAt(config('backup.backup.name'))
                ->youngestBackShouldHaveBeenMadeBefore(now()->subDay()),
        ]);
    }
}
