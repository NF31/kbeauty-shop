import { Link } from '@inertiajs/react';

export function StorefrontFooter() {
    return (
        <footer className="border-t border-sidebar-border/80">
            <div className="mx-auto grid gap-8 px-4 py-12 text-sm sm:grid-cols-2 md:max-w-7xl lg:grid-cols-4">
                <div>
                    <h3 className="mb-3 font-semibold">K-Beauty</h3>
                    <p className="text-muted-foreground">
                        Soins coréens sélectionnés, livrés chez vous.
                    </p>
                </div>

                <div>
                    <h3 className="mb-3 font-semibold">Aide</h3>
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
                    <h3 className="mb-3 font-semibold">Légal</h3>
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
                    <h3 className="mb-3 font-semibold">Newsletter</h3>
                    <p className="text-muted-foreground">Bientôt disponible.</p>
                </div>
            </div>

            <div className="border-t border-sidebar-border/80 px-4 py-4 text-center text-xs text-muted-foreground">
                &copy; {new Date().getFullYear()} K-Beauty. Tous droits
                réservés.
            </div>
        </footer>
    );
}
