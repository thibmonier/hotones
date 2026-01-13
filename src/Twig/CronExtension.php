<?php

namespace App\Twig;

use Cron\CronExpression;
use DateTimeImmutable;
use DateTimeZone;
use Override;
use Throwable;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CronExtension extends AbstractExtension
{
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('human_cron', $this->humanizeCron(...)),
            new TwigFilter('cron_next_run', $this->nextRun(...)),
        ];
    }

    /**
     * Humanize a 5-part cron expression (min hour dom mon dow) in FR or EN.
     */
    public function humanizeCron(string $expr, string $locale = 'fr'): string
    {
        $expr  = trim($expr);
        $parts = preg_split('/\s+/', $expr);
        if (!$parts || count($parts) !== 5) {
            return $this->t('Invalid CRON expression', $locale).': '.$expr;
        }
        [$min, $hour, $dom, $mon, $dow] = $parts;

        // Helpers
        $isStar  = fn (string $s): bool => $s === '*';
        $fmtTime = function (string $h, string $m, string $loc): string {
            if ($h === '*' && $m === '*') {
                return $this->t('any time', $loc);
            }
            if ($h === '*' && $m !== '*') {
                return $this->t('at minute', $loc).' '.sprintf('%02d', (int) $m);
            }
            if ($h !== '*' && $m === '*') {
                return $this->t('at each minute of', $loc).' '.sprintf('%02d:xx', (int) $h);
            }

            return $this->t('at', $loc).' '.sprintf('%02d:%02d', (int) $h, (int) $m);
        };
        $names = $this->names($locale);

        // Minute parsing
        $minInfo = $this->parseStepListRange($min, 0, 59);
        // Hour parsing
        $hourInfo = $this->parseStepListRange($hour, 0, 23);

        // 1) Every minute
        if ($min === '*' && $hour === '*' && $dom === '*' && $mon === '*' && $dow === '*') {
            return $this->t('every minute', $locale);
        }
        // 2) Every N minutes (only minutes stepped)
        if ($minInfo['type'] === 'step' && $hour === '*' && $dom === '*' && $mon === '*' && $dow === '*') {
            return sprintf($this->t('every %d minutes', $locale), $minInfo['step']);
        }
        // 3) Hourly patterns
        if ($min === '0' && $hour === '*' && $dom === '*' && $mon === '*' && $dow === '*') {
            return $this->t('hourly at minute 00', $locale);
        }
        if ($min !== '*' && $hour === '*' && $dom === '*' && $mon === '*' && $dow === '*') {
            return sprintf($this->t('every hour at minute %02d', $locale), (int) $min);
        }
        // 4) Every N hours at minute M
        if ($hourInfo['type'] === 'step' && $dom === '*' && $mon === '*' && $dow === '*') {
            $minuteTxt = $min === '*' ? $this->t('any minute', $locale) : sprintf('%02d', (int) $min);

            return sprintf($this->t('every %d hours at minute %s', $locale), $hourInfo['step'], $minuteTxt);
        }
        // 4b) Every N hours between H1-H2 at minute M
        if ($hourInfo['type'] === 'step_range' && $dom === '*' && $mon === '*' && $dow === '*') {
            $minuteTxt = $min === '*' ? $this->t('any minute', $locale) : sprintf('%02d', (int) $min);

            return sprintf(
                $this->t('every %d hours between %s and %s at minute %s', $locale),
                $hourInfo['step'],
                sprintf('%02d:00', $hourInfo['start']),
                sprintf('%02d:00', $hourInfo['end']),
                $minuteTxt,
            );
        }
        // 4c) At specific hours list
        if ($hourInfo['type'] === 'list' && $dom === '*' && $mon === '*' && $dow === '*') {
            $times = array_map(fn ($h): string => sprintf('%02d:%02d', $h, (int) ($min === '*' ? 0 : $min)), $hourInfo['list']);
            $glue  = $locale === 'en' ? ', ' : ', ';

            return sprintf($this->t('at times %s', $locale), implode($glue, $times));
        }
        // 5) Daily at H:M
        if ($dom === '*' && $mon === '*' && $dow === '*' && $min !== '*' && $hour !== '*') {
            return sprintf($this->t('every day at %s', $locale), sprintf('%02d:%02d', (int) $hour, (int) $min));
        }
        // 6) Weekly: given day(s) of week at patterns for hours
        if ($dom === '*' && $mon === '*' && $dow !== '*') {
            $days = $this->listToLabels($dow, $names['days']);
            // Reuse hour/min description
            if ($hourInfo['type'] === 'step_range') {
                $minuteTxt = $min === '*' ? $this->t('any minute', $locale) : sprintf('%02d', (int) $min);
                $timeTxt   = sprintf(
                    $this->t('every %d hours between %s and %s at minute %s', $locale),
                    $hourInfo['step'],
                    sprintf('%02d:00', $hourInfo['start']),
                    sprintf('%02d:00', $hourInfo['end']),
                    $minuteTxt,
                );
            } elseif ($hourInfo['type'] === 'step') {
                $minuteTxt = $min === '*' ? $this->t('any minute', $locale) : sprintf('%02d', (int) $min);
                $timeTxt   = sprintf($this->t('every %d hours at minute %s', $locale), $hourInfo['step'], $minuteTxt);
            } elseif ($hourInfo['type'] === 'list') {
                $times   = array_map(fn ($h): string => sprintf('%02d:%02d', $h, (int) ($min === '*' ? 0 : $min)), $hourInfo['list']);
                $timeTxt = sprintf($this->t('at times %s', $locale), implode(', ', $times));
            } else {
                $timeTxt = $fmtTime($hour, $min, $locale);
            }

            return sprintf($this->t('every %s %s', $locale), $days, $timeTxt);
        }
        // 7) Monthly: given day(s) of month at H:M
        if ($dom !== '*' && $mon === '*' && $dow === '*') {
            $days = $this->listToText($dom, $locale, 'day');

            return sprintf($this->t('every month on %s %s', $locale), $days, $fmtTime($hour, $min, $locale));
        }
        // 8) Specific months (with optional dom)
        if ($mon !== '*' && $dow === '*') {
            $months = $this->listToLabels($mon, $names['months']);
            $onDom  = $dom !== '*' ? sprintf($this->t('on day %s', $locale), $this->listToText($dom, $locale, 'day')) : $this->t('every day', $locale);

            return sprintf($this->t('%s in %s %s', $locale), $fmtTime($hour, $min, $locale), $months, $onDom);
        }

        // Fallback
        return $this->t('CRON', $locale).': '.$expr;
    }

    /**
     * Parse expressions like "*", "N", "N1,N2", "N1-N2", "*∕N", "N1-N2∕N".
     */
    private function parseStepListRange(string $expr, int $min, int $max): array
    {
        $expr = trim($expr);
        if ($expr === '*') {
            return ['type' => 'any'];
        }
        if (preg_match('#^(\d+)-(\d+)/(\d+)$#', $expr, $m)) {
            return ['type' => 'step_range', 'start' => (int) $m[1], 'end' => (int) $m[2], 'step' => (int) $m[3]];
        }
        if (preg_match('#^\*/(\d+)$#', $expr, $m)) {
            return ['type' => 'step', 'step' => (int) $m[1]];
        }
        if (preg_match('#^(\d+)-(\d+)$#', $expr, $m)) {
            return ['type' => 'range', 'start' => (int) $m[1], 'end' => (int) $m[2]];
        }
        if (preg_match('#^(\d+)(?:,(\d+))+?#', $expr)) {
            $list = array_map(intval(...), explode(',', $expr));

            return ['type' => 'list', 'list' => $list];
        }
        if (ctype_digit($expr)) {
            return ['type' => 'fixed', 'value' => (int) $expr];
        }

        return ['type' => 'unknown', 'raw' => $expr];
    }

    private function names(string $locale): array
    {
        $days_fr   = [0 => 'dimanche', 1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi', 7 => 'dimanche'];
        $days_en   = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
        $months_fr = [1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'];
        $months_en = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];

        return [
            'days'   => $locale === 'en' ? $days_en : $days_fr,
            'months' => $locale === 'en' ? $months_en : $months_fr,
        ];
    }

    private function listToLabels(string $expr, array $labels): string
    {
        $parts = explode(',', $expr);
        $out   = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '*') {
                return implode(', ', array_values($labels));
            }
            if (ctype_digit($p)) {
                $k     = (int) $p;
                $out[] = $labels[$k] ?? $p;
            } elseif (preg_match('#^(\d+)-(\d+)$#', $p, $m)) {
                $start = (int) $m[1];
                $end   = (int) $m[2];
                $out[] = ($labels[$start] ?? $start).' - '.($labels[$end] ?? $end);
            }
        }

        return implode(', ', $out);
    }

    private function listToText(string $expr, string $locale, string $type): string
    {
        // For day-of-month numbers
        $parts = array_map(trim(...), explode(',', $expr));
        $fmt   = function (string $n) use ($locale) {
            $i = (int) $n;
            if ($locale === 'en') {
                return $i.(in_array($i % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'][$i % 10] ?? 'th'));
            }

            return (string) $i; // FR no ordinal for DOM
        };
        $values = [];
        foreach ($parts as $p) {
            if (ctype_digit($p)) {
                $values[] = $fmt($p);
            } elseif (preg_match('#^(\d+)-(\d+)$#', $p, $m)) {
                $values[] = $fmt($m[1]).'–'.$fmt($m[2]);
            } elseif (preg_match('#^\*/(\d+)$#', $p, $m)) {
                $values[] = ($locale === 'en' ? 'every ' : 'tous les ').(int) $m[1];
            }
        }

        return implode(', ', $values);
    }

    private function t(string $text, string $locale): string
    {
        $map = [
            'fr' => [
                'Invalid CRON expression'                       => 'Expression CRON invalide',
                'every minute'                                  => 'chaque minute',
                'every %d minutes'                              => 'toutes les %d minutes',
                'hourly at minute 00'                           => 'au début de chaque heure',
                'every hour at minute %02d'                     => 'chaque heure à la minute %02d',
                'any minute'                                    => 'chaque minute',
                'any time'                                      => 'à toute heure',
                'at each minute of'                             => 'à chaque minute de',
                'at'                                            => 'à',
                'every day at %s'                               => 'tous les jours à %s',
                'every %s %s'                                   => 'chaque %s %s',
                'every month on %s %s'                          => 'chaque mois le %s %s',
                'on day %s'                                     => 'le jour %s',
                'every day'                                     => 'tous les jours',
                '%s in %s %s'                                   => '%s en %s %s',
                'CRON'                                          => 'CRON',
                'at minute'                                     => 'à la minute',
                'every %d hours at minute %s'                   => 'toutes les %d heures à la minute %s',
                'every %d hours between %s and %s at minute %s' => 'toutes les %d heures entre %s et %s à la minute %s',
                'at times %s'                                   => 'aux heures %s',
            ],
            'en' => [
                'Invalid CRON expression'                       => 'Invalid CRON expression',
                'every minute'                                  => 'every minute',
                'every %d minutes'                              => 'every %d minutes',
                'hourly at minute 00'                           => 'hourly at minute 00',
                'every hour at minute %02d'                     => 'every hour at minute %02d',
                'any minute'                                    => 'any minute',
                'any time'                                      => 'any time',
                'at each minute of'                             => 'at each minute of',
                'at'                                            => 'at',
                'every day at %s'                               => 'every day at %s',
                'every %s %s'                                   => 'every %s %s',
                'every month on %s %s'                          => 'every month on %s %s',
                'on day %s'                                     => 'on day %s',
                'every day'                                     => 'every day',
                '%s in %s %s'                                   => '%s in %s %s',
                'CRON'                                          => 'CRON',
                'at minute'                                     => 'at minute',
                'every %d hours at minute %s'                   => 'every %d hours at minute %s',
                'every %d hours between %s and %s at minute %s' => 'every %d hours between %s and %s at minute %s',
                'at times %s'                                   => 'at %s',
            ],
        ];

        return $map[$locale][$text] ?? $text;
    }

    public function nextRun(string $expr, string $tz = 'UTC'): ?DateTimeImmutable
    {
        try {
            $cron = CronExpression::factory($expr);
            $now  = new DateTimeImmutable('now', new DateTimeZone($tz));
            $next = $cron->getNextRunDate($now, 0, false);

            return DateTimeImmutable::createFromMutable($next);
        } catch (Throwable) {
            return null;
        }
    }
}
