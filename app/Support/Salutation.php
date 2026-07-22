<?php

namespace App\Support;

class Salutation
{
    /**
     * "Bonsoir" a partir de 18h (et jusqu'au lendemain matin) : evite d'envoyer un
     * "Bonjour" a un client qui recoit l'email en soiree, meme si l'envoi est
     * differe par la queue (ShouldQueue) par rapport a l'action qui l'a declenche.
     */
    public static function selonHeure(): string
    {
        return now()->hour < 18 ? 'Bonjour' : 'Bonsoir';
    }
}
