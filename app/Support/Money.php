<?php

namespace App\Support;

/**
 * Money helper. All monetary values are stored as integer paisa (1 PKR = 100 paisa)
 * to avoid floating-point rounding errors. This class is the single place that
 * converts between paisa, rupees, and human-readable strings.
 */
class Money
{
    public const PAISA_PER_RUPEE = 100;

    /** Convert a rupee value (int|float|string) to integer paisa. */
    public static function toPaisa(int|float|string|null $rupees): int
    {
        if ($rupees === null || $rupees === '') {
            return 0;
        }

        // Strip currency symbols, spaces and thousands separators.
        if (is_string($rupees)) {
            $rupees = preg_replace('/[^\d.\-]/', '', $rupees);
            if ($rupees === '' || $rupees === '-') {
                return 0;
            }
        }

        // Round to nearest paisa to avoid 0.1 + 0.2 style drift.
        return (int) round(((float) $rupees) * self::PAISA_PER_RUPEE);
    }

    /** Convert integer paisa to a float rupee value (use only for display/forms). */
    public static function toRupees(int|null $paisa): float
    {
        return (int) ($paisa ?? 0) / self::PAISA_PER_RUPEE;
    }

    /**
     * Format paisa as a display string with thousands separators.
     * e.g. 220000000 -> "2,200,000.00" ; with symbol -> "₨ 2,200,000.00"
     */
    public static function format(int|null $paisa, bool $symbol = true, bool $decimals = true): string
    {
        $value = self::toRupees($paisa);
        $formatted = number_format($value, $decimals ? 2 : 0);

        return $symbol ? '₨ ' . $formatted : $formatted;
    }

    /** Short format for cards: 1,250,000 paisa-rupees -> "₨ 12.5K", millions -> "₨ 1.2M". */
    public static function short(int|null $paisa, bool $symbol = true): string
    {
        $value = self::toRupees($paisa);
        $abs = abs($value);
        $sign = $value < 0 ? '-' : '';

        if ($abs >= 10_000_000) {
            $out = $sign . number_format($abs / 10_000_000, 2) . ' Cr';
        } elseif ($abs >= 100_000) {
            $out = $sign . number_format($abs / 100_000, 2) . ' Lac';
        } elseif ($abs >= 1_000) {
            $out = $sign . number_format($abs / 1_000, 1) . 'K';
        } else {
            $out = $sign . number_format($abs, 0);
        }

        return $symbol ? '₨ ' . $out : $out;
    }
}
