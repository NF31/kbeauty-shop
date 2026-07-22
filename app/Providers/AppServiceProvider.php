<?php

namespace App\Providers;

use App\Listeners\MergeGuestCartOnLogin;
use App\Models\User;
use App\Support\Salutation;
use Carbon\CarbonImmutable;
use Cloudinary\Cloudinary;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Cloudinary::class, fn () => new Cloudinary(config('services.cloudinary.url')));

        $this->app->singleton(StripeClient::class, fn () => new StripeClient(config('services.stripe.secret')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthMailNotifications();

        Event::listen(Login::class, MergeGuestCartOnLogin::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Reprend le contenu par defaut des notifications Fortify/Laravel pour y appliquer
     * la salutation ("Bonjour"/"Bonsoir" selon l'heure, voir App\Support\Salutation) deja
     * utilisee sur les emails clients (OrderConfirmation, RefundConfirmation).
     */
    protected function configureAuthMailNotifications(): void
    {
        VerifyEmail::toMailUsing(function (User $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Vérification de votre adresse email')
                ->greeting(Salutation::pour($notifiable).',')
                ->line('Merci de confirmer votre adresse email en cliquant sur le bouton ci-dessous.')
                ->action('Vérifier mon adresse email', $url)
                ->line("Si vous n'êtes pas à l'origine de la création de ce compte, vous pouvez ignorer cet email.");
        });

        ResetPassword::toMailUsing(function (User $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe')
                ->greeting(Salutation::pour($notifiable).',')
                ->line('Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.')
                ->action('Réinitialiser le mot de passe', $url)
                ->line('Ce lien de réinitialisation expirera dans '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
                ->line("Si vous n'êtes pas à l'origine de cette demande, aucune action n'est requise.");
        });
    }
}
