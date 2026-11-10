<?php

function numberToWords($number)
{
    $number = round($number, 2);

    $integer = floor($number);
    $fraction = round(($number - $integer) * 100);

    $ones = array(
        "",
        "One",
        "Two",
        "Three",
        "Four",
        "Five",
        "Six",
        "Seven",
        "Eight",
        "Nine",
        "Ten",
        "Eleven",
        "Twelve",
        "Thirteen",
        "Fourteen",
        "Fifteen",
        "Sixteen",
        "Seventeen",
        "Eighteen",
        "Nineteen"
    );

    $tens = array(
        "",
        "",
        "Twenty",
        "Thirty",
        "Forty",
        "Fifty",
        "Sixty",
        "Seventy",
        "Eighty",
        "Ninety"
    );

    $convert = function ($num) use (&$convert, $ones, $tens) {

        if ($num < 20) {
            return $ones[$num];
        }

        if ($num < 100) {

            return $tens[intval($num / 10)] .
                (($num % 10) ? " " . $ones[$num % 10] : "");

        }

        if ($num < 1000) {

            return $ones[intval($num / 100)] .
                " Hundred" .
                (($num % 100) ? " " . $convert($num % 100) : "");

        }

        if ($num < 100000) {

            return $convert(intval($num / 1000)) .
                " Thousand" .
                (($num % 1000) ? " " . $convert($num % 1000) : "");

        }

        if ($num < 10000000) {

            return $convert(intval($num / 100000)) .
                " Lakh" .
                (($num % 100000) ? " " . $convert($num % 100000) : "");

        }

        return $convert(intval($num / 10000000)) .
            " Crore" .
            (($num % 10000000) ? " " . $convert($num % 10000000) : "");

    };

    $words = $convert($integer);

    if ($fraction > 0) {

        $words .= " Taka and " .
            $convert($fraction) .
            " Paisa Only";

    } else {

        $words .= " Taka Only";

    }

    return trim($words);
}