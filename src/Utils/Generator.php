<?php

namespace JordJD\Postcodes\Utils;

use Faker\Factory as FakerFactory;

abstract class Generator
{
    public static function generatePostcode()
    {
        $faker = FakerFactory::create('en_GB');

        $validated = false;

        while (!$validated) {
            $postcode = $faker->postcode;
            $validated = Validator::validatePostcode($postcode);
        }

        return $postcode;
    }
}
