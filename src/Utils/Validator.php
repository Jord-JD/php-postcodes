<?php

namespace JordJD\Postcodes\Utils;

abstract class Validator
{
    public static $invalidPostcodes = [
        'KT185DN',
        'AB154YR',
        'B628RS',
    ];

    public static function validatePostcode($postcode)
    {
        $postcode = strtoupper($postcode);

        if (in_array(str_replace(' ', '', $postcode), self::$invalidPostcodes)) {
            return false;
        }

        $regex = '#^(GIR ?0AA|[A-PR-UWYZ]([0-9]{1,2}|([A-HK-Y][0-9]([0-9ABEHMNPRV-Y])?)|[0-9][A-HJKPS-UW]) ?[0-9][ABD-HJLNP-UW-Z]{2})$#';

        $result = preg_match($regex, $postcode);

        return $result ? true : false;
    }
}
