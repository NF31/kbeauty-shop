/**
 * Prefixe un chemin storefront avec /en quand la locale active est
 * l'anglais — toutes les pages publiques ont desormais un miroir /en/...
 * (memes segments francais, seul le prefixe change, voir routes/storefront.php
 * et routes/web.php).
 */
export function localizedPath(path: string, locale: string): string {
    if (locale !== 'en') {
        return path;
    }

    return path === '/' ? '/en' : `/en${path}`;
}
