<?php

namespace App\Helpers;

class NumberToWords
{
    private static array $ones = [
        0  => '', 1  => 'One', 2  => 'Two', 3  => 'Three', 4  => 'Four',
        5  => 'Five', 6  => 'Six', 7  => 'Seven', 8  => 'Eight', 9  => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen',
    ];

    private static array $tens = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty',  7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
    ];

    public static function convert(float $number): string
    {
        $number   = round($number, 2);
        $rupees   = (int) floor($number);
        $paise    = (int) round(($number - $rupees) * 100);

        $words = self::toWords($rupees) . ' Rupees';

        if ($paise > 0) {
            $words .= ' and ' . self::toWords($paise) . ' Paise';
        }

        return $words;
    }

    private static function toWords(int $n): string
    {
        if ($n === 0) return 'Zero';

        if ($n < 0) return 'Minus ' . self::toWords(abs($n));

        $parts = [];

        if ($n >= 10_00_00_000) {
            $parts[] = self::toWords((int) ($n / 10_00_00_000)) . ' Arab';
            $n %= 10_00_00_000;
        }
        if ($n >= 1_00_00_000) {
            $parts[] = self::toWords((int) ($n / 1_00_00_000)) . ' Crore';
            $n %= 1_00_00_000;
        }
        if ($n >= 1_00_000) {
            $parts[] = self::toWords((int) ($n / 1_00_000)) . ' Lakh';
            $n %= 1_00_000;
        }
        if ($n >= 1_000) {
            $parts[] = self::toWords((int) ($n / 1_000)) . ' Thousand';
            $n %= 1_000;
        }
        if ($n >= 100) {
            $parts[] = self::$ones[(int) ($n / 100)] . ' Hundred';
            $n %= 100;
        }
        if ($n >= 20) {
            $word = self::$tens[(int) ($n / 10)];
            if ($n % 10 !== 0) {
                $word .= ' ' . self::$ones[$n % 10];
            }
            $parts[] = $word;
        } elseif ($n > 0) {
            $parts[] = self::$ones[$n];
        }

        return implode(' ', $parts);
    }
}
