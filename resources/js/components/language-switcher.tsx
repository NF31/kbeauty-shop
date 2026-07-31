import { router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useEffect } from 'react';
import { FlagFr, FlagGb } from '@/components/flag-icons';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

/**
 * Chemins storefront disposant d'un miroir `/en/...` (routes/storefront.php)
 * — catalogue, fiche produit, panier, tunnel de commande (25.1 puis
 * extension au parcours d'achat). Le selecteur reste visible partout ou le
 * header storefront est rendu (y compris login/register/compte/legal) ; sur
 * les pages sans version anglaise, il bascule vers /produits, seule page
 * dont l'anglais existe reellement.
 */
const LOCALIZED_PATH_PATTERN = /^\/(en\/)?(produits|panier|commande)(\/.*)?$/;
const FALLBACK_LOCALIZED_PATH = '/produits';

function alternateLocaleHref(currentUrl: string): string {
    const isEnglish = currentUrl.startsWith('/en/') || currentUrl === '/en';

    if (!LOCALIZED_PATH_PATTERN.test(currentUrl)) {
        return isEnglish
            ? FALLBACK_LOCALIZED_PATH
            : `/en${FALLBACK_LOCALIZED_PATH}`;
    }

    return isEnglish
        ? currentUrl.replace(/^\/en/, '') || '/'
        : `/en${currentUrl}`;
}

export function LanguageSwitcher() {
    const { t } = useLaravelReactI18n();
    const { url, props } = usePage();
    const { locale } = props;
    const alternateHref = alternateLocaleHref(url);

    // Precharge la page dans l'autre langue pour que la bascule du
    // selecteur soit instantanee (servie depuis le cache Inertia) plutot
    // que de declencher un aller-retour serveur visible au clic.
    useEffect(() => {
        router.prefetch(alternateHref, { method: 'get' }, { cacheFor: '30s' });
    }, [alternateHref]);

    return (
        <Select
            value={locale}
            onValueChange={(value) => {
                if (value !== locale) {
                    router.get(alternateHref);
                }
            }}
        >
            <SelectTrigger size="sm" aria-label={t('Choisir la langue')}>
                {/* Contenu fourni explicitement plutot que laisse au
                mapping automatique de Radix (SelectItem -> SelectValue),
                qui ne peut pas se resoudre correctement au rendu serveur
                (portail non monte) et provoque un mismatch d'hydratation. */}
                <SelectValue>
                    {locale === 'en' ? (
                        <>
                            <FlagGb className="size-4 rounded-sm" />
                            English
                        </>
                    ) : (
                        <>
                            <FlagFr className="size-4 rounded-sm" />
                            Français
                        </>
                    )}
                </SelectValue>
            </SelectTrigger>
            <SelectContent>
                {/* Noms de langue non traduits (endonymes) — convention standard des selecteurs de langue */}
                <SelectItem value="fr">
                    <FlagFr className="size-4 rounded-sm" />
                    Français
                </SelectItem>
                <SelectItem value="en">
                    <FlagGb className="size-4 rounded-sm" />
                    English
                </SelectItem>
            </SelectContent>
        </Select>
    );
}
