<?php

namespace JordJD\Postcodes\Tests;

use JordJD\Postcodes\Exceptions\InvalidPostcodeException;
use JordJD\Postcodes\Utils\Generator;
use JordJD\Postcodes\Utils\Tokenizer;
use JordJD\Postcodes\Utils\Validator;
use PHPUnit\Framework\TestCase;

final class BasicUsageTest extends TestCase
{
    public function validationProvider()
    {
        return [
            ['ST163DP'],
            ['TN30YA'],
            ['ST78PP'],
            ['CM233WE'],
            ['E16AW'],
            ['E106QX'],
            ['ST16 3DP'],
            ['st16 3dp'],
        ];
    }

    /**
     * @dataProvider validationProvider
     */
    public function testValidation($postcode)
    {
        $this->assertTrue(Validator::validatePostcode($postcode));
    }

    public function validationFailureProvider()
    {
        return [
            ['ST163DPA'],
            ['XF2P90'],
            ['Ollie'],
            ['cake'],
            ['ST16 3DPA'],
            ['KT18 5DN'],
            ['AB15 4YR'],
            ['B62 8RS'],
        ];
    }

    /**
     * @dataProvider validationFailureProvider
     */
    public function testValidationFailure($postcode)
    {
        $this->assertFalse(Validator::validatePostcode($postcode));
    }

    public function testGeneration()
    {
        $postcodes = [];

        for ($i = 0; $i < 100; $i++) {
            $postcodes[] = Generator::generatePostcode();
        }

        foreach ($postcodes as $postcode) {
            $this->assertTrue(Validator::validatePostcode($postcode));
        }
    }

    public function outwardAndInwardCodesProvider()
    {
        return [
            [
                'postcode' => 'ST163DP',
                'outward'  => 'ST16',
                'inward'   => '3DP',
            ],
            [
                'postcode' => 'TN30YA',
                'outward'  => 'TN3',
                'inward'   => '0YA',
            ],
            [
                'postcode' => 'ST78PP',
                'outward'  => 'ST7',
                'inward'   => '8PP',
            ],
            [
                'postcode' => 'CM233WE',
                'outward'  => 'CM23',
                'inward'   => '3WE',
            ],
            [
                'postcode' => 'E16AW',
                'outward'  => 'E1',
                'inward'   => '6AW',
            ],
            [
                'postcode' => 'E106QX',
                'outward'  => 'E10',
                'inward'   => '6QX',
            ],
            [
                'postcode' => 'ST16 3DP',
                'outward'  => 'ST16',
                'inward'   => '3DP',
            ],
            [
                'postcode' => 'E1 6AW',
                'outward'  => 'E1',
                'inward'   => '6AW',
            ],
            [
                'postcode' => 'e1 6aw',
                'outward'  => 'E1',
                'inward'   => '6AW',
            ],
        ];
    }

    /**
     * @dataProvider outwardAndInwardCodesProvider
     */
    public function testOutwardAndInwardCodes($postcode, $outward, $inward)
    {
        $this->assertEquals($outward, Tokenizer::outward($postcode));
        $this->assertEquals($inward, Tokenizer::inward($postcode));
    }

    public function testOutwardCodeWithInvalidPostcode()
    {
        $this->expectException(InvalidPostcodeException::class);
        $this->expectExceptionMessage('Post code provided is not valid');

        Tokenizer::outward('ST163DPA');
    }

    public function testInwardCodeWithInvalidPostcode()
    {
        $this->expectException(InvalidPostcodeException::class);
        $this->expectExceptionMessage('Post code provided is not valid');

        Tokenizer::inward('ST163DPA');
    }
}
