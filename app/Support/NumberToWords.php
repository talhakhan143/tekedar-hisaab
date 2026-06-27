<?php

namespace App\Support;

/**
 * Converts integer paisa into an English "amount in words" string for printed
 * vouchers / invoices, e.g. 220050 paisa -> "Rupees Two Thousand Two Hundred
 * and Fifty Paisa Only". Uses the Pakistani/Indian numbering system (Lakh, Crore).
 */
class NumberToWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    /** Full label, e.g. "Rupees Twelve Thousand Only". */
    public static function rupees(int|null $paisa): string
    {
        $paisa = (int) ($paisa ?? 0);
        $negative = $paisa < 0;
        $paisa = abs($paisa);

        $rupees = intdiv($paisa, 100);
        $paise  = $paisa % 100;

        $parts = [];
        $parts[] = 'Rupees ' . self::words($rupees);
        if ($paise > 0) {
            $parts[] = 'and ' . self::words($paise) . ' Paisa';
        }

        $label = implode(' ', $parts) . ' Only';

        return ($negative ? 'Minus ' : '') . $label;
    }

    /** Convert a whole number to words using the Lakh/Crore system. */
    public static function words(int $n): string
    {
        if ($n === 0) {
            return 'Zero';
        }

        $out = '';

        $crore = intdiv($n, 10000000);
        $n %= 10000000;
        $lakh = intdiv($n, 100000);
        $n %= 100000;
        $thousand = intdiv($n, 1000);
        $n %= 1000;
        $hundred = intdiv($n, 100);
        $rest = $n % 100;

        if ($crore)    { $out .= self::words($crore) . ' Crore '; }
        if ($lakh)     { $out .= self::twoDigits($lakh) . ' Lakh '; }
        if ($thousand) { $out .= self::twoDigits($thousand) . ' Thousand '; }
        if ($hundred)  { $out .= self::ONES[$hundred] . ' Hundred '; }
        if ($rest) {
            if ($out !== '') {
                $out .= 'and ';
            }
            $out .= self::twoDigits($rest) . ' ';
        }

        return trim($out);
    }

    private static function twoDigits(int $n): string
    {
        if ($n < 20) {
            return self::ONES[$n];
        }
        $tens = self::TENS[intdiv($n, 10)];
        $ones = self::ONES[$n % 10];

        return $ones ? "{$tens} {$ones}" : $tens;
    }
}
