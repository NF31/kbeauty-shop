<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sauvegarde quotidienne (DB + fichiers), nettoyage puis controle de sante
// dans cet ordre - un backup:clean avant backup:run eviterait de nettoyer la
// sauvegarde qu'on vient de creer, mais laisserait une sauvegarde en trop en
// cas d'echec du run precedent ; l'ordre run -> clean -> monitor est celui
// recommande par la doc spatie/laravel-backup.
Schedule::command('backup:run')->dailyAt('02:00')->onOneServer();
Schedule::command('backup:clean')->dailyAt('02:30')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('03:00')->onOneServer();
