<?php

namespace App\Service;

class TimeConversionService
{
    public const HOURS_PER_DAY = 8;

    /**
     * Convertit des heures en jours (1j = 8h)
     */
    public static function hoursToDays(string $hours): string
    {
        return bcdiv($hours, (string)self::HOURS_PER_DAY, 2);
    }

    /**
     * Convertit des jours en heures (1j = 8h)
     */
    public static function daysToHours(string $days): string
    {
        return bcmul($days, (string)self::HOURS_PER_DAY, 2);
    }

    /**
     * Formate les heures pour affichage (avec conversion en jours si >= 8h)
     */
    public static function formatHoursForDisplay(string $hours): string
    {
        $hoursFloat = floatval($hours);
        
        if ($hoursFloat >= self::HOURS_PER_DAY) {
            $days = self::hoursToDays($hours);
            $remainingHours = bcmod($hours, (string)self::HOURS_PER_DAY, 2);
            
            if ($remainingHours === '0.00') {
                return number_format(floatval($days), 1, ',', ' ') . 'j';
            } else {
                return number_format(floatval($days), 1, ',', ' ') . 'j ' . 
                       number_format(floatval($remainingHours), 1, ',', ' ') . 'h';
            }
        }
        
        return number_format($hoursFloat, 1, ',', ' ') . 'h';
    }

    /**
     * Formate les jours pour affichage
     */
    public static function formatDaysForDisplay(string $days): string
    {
        return number_format(floatval($days), 1, ',', ' ') . 'j';
    }

    /**
     * Parse une entrée utilisateur qui peut être en heures ou jours
     * Exemples: "8h", "1j", "1.5j", "7.5h"
     */
    public static function parseUserInput(string $input): array
    {
        $input = trim(strtolower($input));
        
        if (str_ends_with($input, 'j')) {
            $days = rtrim($input, 'j');
            return [
                'days' => $days,
                'hours' => self::daysToHours($days)
            ];
        } elseif (str_ends_with($input, 'h')) {
            $hours = rtrim($input, 'h');
            return [
                'hours' => $hours,
                'days' => self::hoursToDays($hours)
            ];
        } else {
            // Par défaut, considérer comme des heures
            return [
                'hours' => $input,
                'days' => self::hoursToDays($input)
            ];
        }
    }

    /**
     * Calcule les jours ouvrés entre deux dates
     */
    public static function getWorkingDaysBetween(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $start = clone $start;
        $end = clone $end;
        $days = 0;

        while ($start <= $end) {
            // Exclure les weekends (samedi=6, dimanche=0)
            if (!in_array($start->format('w'), ['0', '6'])) {
                $days++;
            }
            $start->modify('+1 day');
        }

        return $days;
    }

    /**
     * Calcule les heures théoriques d'une période
     */
    public static function getTheoreticalHours(\DateTimeInterface $start, \DateTimeInterface $end, float $dailyHours = self::HOURS_PER_DAY): string
    {
        $workingDays = self::getWorkingDaysBetween($start, $end);
        return bcmul((string)$workingDays, (string)$dailyHours, 2);
    }
}