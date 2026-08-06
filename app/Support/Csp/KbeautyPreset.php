<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * Complète les presets officiels (Stripe, GoogleTagManager, CloudflareTurnstile,
 * Sentry) pour les services tiers propres à kbeauty n'ayant pas de preset dédié
 * dans le package. Klaviyo n'y figure pas : appelé uniquement côté serveur
 * (SendPlacedOrderEventToKlaviyo), jamais depuis le navigateur.
 */
class KbeautyPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        // Images produits, livrées à la volée par Cloudinary (CloudinaryService::url()).
        $policy->add(Directive::IMG, 'https://res.cloudinary.com');

        // Widget de chat support Crisp (resources/js/components/storefront/crisp-chat.tsx) :
        // script chargé dynamiquement + ses appels réseau propres. Le sous-domaine
        // exact du WebSocket (probablement client.relay.crisp.chat) n'est pas visible
        // statiquement dans le code — à confirmer via les violations remontées en
        // report-only avant de passer la policy en mode bloquant.
        $policy->add(Directive::SCRIPT, 'https://client.crisp.chat');
        $policy->add(Directive::CONNECT, 'https://client.crisp.chat');

        // Autocomplétion d'adresse (Base Adresse Nationale, gratuite/sans clé),
        // resources/js/components/storefront/address-autocomplete-input.tsx.
        $policy->add(Directive::CONNECT, 'https://api-adresse.data.gouv.fr');
    }
}
