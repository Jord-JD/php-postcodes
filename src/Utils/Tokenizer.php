<?php

namespace JordJD\Postcodes\Utils;

use JordJD\Postcodes\Exceptions\InvalidPostcodeException;

abstract class Tokenizer
{
    public static function outward($postcode)
    {
        self::sanityCheck($postcode);

        return strtoupper(trim(substr($postcode, 0, -3)));
    }

    public static function inward($postcode)
    {
        self::sanityCheck($postcode);

        return strtoupper(trim(substr($postcode, -3, 3)));
    }

    private static function sanityCheck($postcode)
    {
        $validated = Validator::validatePostcode($postcode);
        if (!$validated) {
            throw new InvalidPostcodeException('Post code provided is not valid');
        }
    }
}
