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
        $postcode = self::normalizePostcode($postcode);

        if ($postcode === null) {
            return false;
        }

        $compactPostcode = str_replace(' ', '', $postcode);

        if (in_array($compactPostcode, self::$invalidPostcodes, true)) {
            return false;
        }

        $regex = '#^(GIR0AA|[A-PR-UWYZ]([0-9]{1,2}|([A-HK-Y][0-9]([0-9ABEHMNPRV-Y])?)|[0-9][A-HJKPS-UW])[0-9][ABD-HJLNP-UW-Z]{2})$#';

        $result = preg_match($regex, $compactPostcode);

        return $result ? true : false;
    }

    /**
     * Normalize a postcode to the conventional uppercase format.
     *
     * Invalid input is returned as null. Use validatePostcode() when the
     * postcode must also be checked against the UK postcode pattern.
     */
    public static function normalizePostcode($postcode)
    {
        if (!is_string($postcode)) {
            return null;
        }

        $postcode = preg_replace('/\s+/', '', trim($postcode));

        if ($postcode === '' || strlen($postcode) < 4) {
            return null;
        }

        $postcode = strtoupper($postcode);

        return substr($postcode, 0, -3).' '.substr($postcode, -3);
    }
}
