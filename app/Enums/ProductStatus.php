<?php

namespace App\Enums;

use App\Observers\ProductObserver;

/** Passer à `Published` sans `ingredients_inci` renseigné est bloqué par {@see ProductObserver}. */
enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
