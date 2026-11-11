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
     * Group digits the Pakistani way: last three, then pairs.
     * 3000000 -> "30,00,000" (NOT the western "3,000,000").
     *
     * Ye zaroori hai. Pehle yahan number_format() tha jo western grouping deta
     * hai, aur tees lakh "3,000,000" ban kar chhapta tha. Thekedaar pehla group
     * "3" dekh kar usay teen lakh parh leta hai, yani app sahi raqam rakh kar
     * bhi ghalat raqam dikhati thi. JS ka toLocaleString('en-PK') bhi western
     * grouping deta hai (test kiya), is liye wahan 'en-IN' use hota hai, jiski
     * grouping Pakistani convention se bilkul milti hai.
     */
    public static function groupDesi(string $digits): string
    {
        if (strlen($digits) <= 3) {
            return $digits;
        }

        $last3 = substr($digits, -3);
        $rest  = substr($digits, 0, -3);

        return preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',' . $last3;
    }

    /**
     * Format paisa as a display string with Pakistani thousands separators.
     * e.g. 220000000 -> "22,00,000.00" ; with symbol -> "₨ 22,00,000.00"
     */
    public static function format(int|null $paisa, bool $symbol = true, bool $decimals = true): string
    {
        $paisa = (int) ($paisa ?? 0);
        $sign  = $paisa < 0 ? '-' : '';
        $abs   = abs($paisa);

        $rupees = intdiv($abs, self::PAISA_PER_RUPEE);
        $formatted = $sign . self::groupDesi((string) $rupees);

        if ($decimals) {
            $formatted .= '.' . str_pad((string) ($abs % self::PAISA_PER_RUPEE), 2, '0', STR_PAD_LEFT);
        }

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
