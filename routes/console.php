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

// health:queue-check-heartbeat pousse un job sur la queue pour que QueueCheck
// puisse verifier qu'Horizon le traite bien ; health:check execute tous les
// checks (dont ce meme QueueCheck) et notifie en cas d'echec.
Schedule::command('health:queue-check-heartbeat')->everyMinute();
Schedule::command('health:check')->everyMinute();

// Relance panier abandonné (24.1) : seuil d'inactivité de 2h, verifie toutes
// les 30 min pour rester precis sans multiplier les requetes.
Schedule::command('cart:send-abandoned-reminders')->everyThirtyMinutes()->onOneServer();

// Purge Telescope (jamais fait jusqu'ici - telescope_entries a rempli a lui
// seul les 512 Mo du plan Neon gratuit, cf. incident du 2026-08-05). 48h de
// retention suffisent pour investiguer une exception recente en prod ; le
// developpement local pointe sur la meme base Neon partagee, donc chaque
// session de dev local re-remplit vite la table sans cette purge.
Schedule::command('telescope:prune', ['--hours' => 48])->daily()->onOneServer();
