← [Retour au sommaire](README.md)

# 4. Architecture de sécurité & authentification sans état (Fortify Headless)

Le site principal utilise Laravel Fortify en mode headless (formulaires gérés en React/Inertia). La connexion entre l'application e-commerce et le micro-service Next.js se fait par un **jeton d'accès temporaire signé**, via une route API dédiée à construire côté Laravel (Fortify ne fournit pas nativement l'émission de jetons cross-service — cette route est à développer explicitement, par exemple avec Sanctum ou un JWT signé maison).

## Le flux d'échange et de validation

1.  La cliente, connectée sur la boutique principale, clique sur « Démarrer mon diagnostic de peau ».
2.  Laravel génère un jeton temporaire signé à durée de vie courte (2 minutes) et redirige vers Next.js avec ce jeton en paramètre.
3.  Le **Next.js 16 Server Component** intercepte le jeton via `await searchParams`, effectue une requête serveur-à-serveur vers l'API Laravel pour valider l'identité de l'utilisateur, et initialise sa session locale.
4.  **Rate Limiting (protection des coûts d'IA)** : les Server Actions Next.js appliquent un throttle strict (ex. 3 diagnostics/heure/IP) pour éviter la consommation abusive des crédits d'API LLM/Vision.
5.  **Protection des Données (RGPD)** : les photos transmises à Spring Boot sont traitées en mémoire vive uniquement, puis détruites immédiatement. Seuls des scores textuels anonymisés (`rougeurs: 40%`) sont persistés.
