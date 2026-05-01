<?php

declare(strict_types=1);

namespace App\Domain\Vacation\ValueObject;

enum VacationType: string
{
    case PAID_LEAVE = 'conges_payes';
    case COMPENSATORY_REST = 'repos_compensateur';
    case EXCEPTIONAL_ABSENCE = 'absence_exceptionnelle';
    case SICK_LEAVE = 'arret_maladie';
    case TRAINING = 'formation';
    case OTHER = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::PAID_LEAVE => 'Conges payes',
            self::COMPENSATORY_REST => 'Repos compensateur',
            self::EXCEPTIONAL_ABSENCE => 'Absence exceptionnelle',
            self::SICK_LEAVE => 'Arret maladie',
            self::TRAINING => 'Formation',
            self::OTHER => 'Autre',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->value] = $case->label();
        }

        return $choices;
    }
}
