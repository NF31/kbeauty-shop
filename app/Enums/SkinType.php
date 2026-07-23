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
            self::Dry => __('Peau sèche'),
            self::Oily => __('Peau grasse'),
            self::Combination => __('Peau mixte'),
            self::Sensitive => __('Peau sensible'),
            self::Normal => __('Peau normale'),
            self::Dull => __('Peau terne'),
            self::Mature => __('Peau mature'),
            self::Dehydrated => __('Peau déshydratée'),
            self::Acneic => __('Peau à tendance acnéique'),
        };
    }
}
