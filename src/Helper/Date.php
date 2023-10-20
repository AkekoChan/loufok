<?php

namespace App\Helper;

class Date
{
    public static function validate($date, $format = 'Y-m-d'): bool
    {
        // replace a 'Z' at the end by '+00:00'
        $date = preg_replace('/(.*)Z$/', '${1}+00:00', $date);

        $d = \DateTime::createFromFormat($format, $date);


        return $d && $d->format($format) == $date;
    }
}