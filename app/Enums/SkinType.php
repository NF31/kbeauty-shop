<?php

namespace App\Enums;

enum SkinType: string
{
    case Dry = 'seche';
    case Oily = 'grasse';
    case Combination = 'mixte';
    case Sensitive = 'sensible';
    case Normal = 'normale';
    case Dull = 'terne';
    case Mature = 'mature';
    case Dehydrated = 'deshydratee';
    case Acneic = 'acne';

    public function label(): string
    {
        return match ($this) {
            self::Dry => 'Peau sèche',
            self::Oily => 'Peau grasse',
            self::Combination => 'Peau mixte',
            self::Sensitive => 'Peau sensible',
            self::Normal => 'Peau normale',
            self::Dull => 'Peau terne',
            self::Mature => 'Peau mature',
            self::Dehydrated => 'Peau déshydratée',
            self::Acneic => 'Peau à tendance acnéique',
        };
    }
}
