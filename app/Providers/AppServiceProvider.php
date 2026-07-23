<?php

namespace App\Providers;

use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Orders\Contracts\InvoicePdfRendererInterface;
use App\Domain\Orders\Contracts\InvoiceRepositoryInterface;
use App\Domain\Orders\Contracts\OrderRepositoryInterface;
use App\Domain\Orders\Contracts\PaymentRepositoryInterface;
use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Shared\Contracts\UnitOfWorkInterface;
use App\Domain\Stock\Contracts\StockRepositoryInterface;
use App\Infrastructure\Cart\EloquentCartRepository;
use App\Infrastructure\Orders\DompdfInvoiceRenderer;
use App\Infrastructure\Orders\EloquentInvoiceRepository;
use App\Infrastructure\Orders\EloquentOrderRepository;
use App\Infrastructure\Orders\EloquentPaymentRepository;
use App\Infrastructure\Payments\StripePaymentGateway;
use App\Infrastructure\Shared\DatabaseUnitOfWork;
use App\Infrastructure\Stock\EloquentStockRepository;
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
use Spatie\Translatable\Translatable;
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

        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(PaymentGatewayInterface::class, StripePaymentGateway::class);
        $this->app->bind(UnitOfWorkInterface::class, DatabaseUnitOfWork::class);
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->bind(InvoicePdfRendererInterface::class, DompdfInvoiceRenderer::class);
        $this->app->bind(CartRepositoryInterface::class, EloquentCartRepository::class);
        $this->app->bind(StockRepositoryInterface::class, EloquentStockRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthMailNotifications();

        Event::listen(Login::class, MergeGuestCartOnLogin::class);

        // Sans ca, assigner `null` a un champ traduisible (short_description,
        // ingredients_inci, how_to_use — tous nullable) revient en lecture
        // comme une chaine vide plutot que null (comportement par defaut de
        // spatie/laravel-translatable), ce qui casse la distinction "valeur
        // absente" attendue par le reste de l'app (blank(), toBeNull(), etc.).
        app(Translatable::class)->allowNullForTranslation();
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
