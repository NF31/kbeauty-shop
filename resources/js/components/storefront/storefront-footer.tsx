import { Link } from '@inertiajs/react';

export function StorefrontFooter() {
    return (
        <footer className="border-t border-sidebar-border/80">
            <div className="mx-auto grid gap-8 px-4 py-12 text-sm sm:grid-cols-2 md:max-w-7xl lg:grid-cols-4">
                <div>
                    <img
                        src="/logo-mark.png"
                        alt="Korea Beauty"
                        loading="lazy"
                        className="mb-3 size-14 rounded-full"
                    />
                    <p className="text-muted-foreground">
                        Soins coréens sélectionnés, livrés chez vous.
                    </p>
                </div>

                <div>
                    <h2 className="mb-3 font-semibold">Aide</h2>
                    <ul className="space-y-2 text-muted-foreground">
                        <li>
                            <Link href="/livraison" className="hover:underline">
                                Livraison
                            </Link>
                        </li>
                        <li>
                            <Link href="/retours" className="hover:underline">
                                Retours
                            </Link>
                        </li>
                        <li>Contact</li>
                    </ul>
                </div>

                <div>
                    <h2 className="mb-3 font-semibold">Légal</h2>
                    <ul className="space-y-2 text-muted-foreground">
                        <li>
                            <Link href="/cgv" className="hover:underline">
                                CGV
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/confidentialite"
                                className="hover:underline"
                            >
                                Confidentialité
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/mentions-legales"
                                className="hover:underline"
                            >
                                Mentions légales
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h2 className="mb-3 font-semibold">Newsletter</h2>
                    <p className="text-muted-foreground">Bientôt disponible.</p>
                </div>
            </div>

            <div className="border-t border-sidebar-border/80 px-4 py-4 text-center text-xs text-muted-foreground">
                &copy; {new Date().getFullYear()} Korea Beauty. Tous droits
                réservés.
            </div>
        </footer>
    );
}
