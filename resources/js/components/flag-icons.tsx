/**
 * Drapeaux en SVG plutot qu'en emoji — les emoji de drapeau (region
 * indicator symbols) ne s'affichent pas correctement sur Windows (rendus
 * en texte "FR"/"GB" ou en cases vides selon la police), contrairement a
 * macOS/iOS. Utilise par le selecteur de langue (25.1).
 */

type FlagProps = {
    className?: string;
};

export function FlagFr({ className }: FlagProps) {
    return (
        <svg
            viewBox="0 0 3 2"
            className={className}
            role="img"
            aria-label="Français"
        >
            <rect width="1" height="2" x="0" fill="#0055A4" />
            <rect width="1" height="2" x="1" fill="#FFFFFF" />
            <rect width="1" height="2" x="2" fill="#EF4135" />
        </svg>
    );
}

export function FlagGb({ className }: FlagProps) {
    return (
        <svg
            viewBox="0 0 60 36"
            className={className}
            role="img"
            aria-label="English"
        >
            <rect width="60" height="36" fill="#00247D" />
            <path
                d="M0,0 L60,36 M60,0 L0,36"
                stroke="#FFFFFF"
                strokeWidth="6"
            />
            <path
                d="M0,0 L60,36 M60,0 L0,36"
                stroke="#CF142B"
                strokeWidth="2"
            />
            <path d="M30,0 V36 M0,18 H60" stroke="#FFFFFF" strokeWidth="10" />
            <path d="M30,0 V36 M0,18 H60" stroke="#CF142B" strokeWidth="6" />
        </svg>
    );
}
