<?php

namespace JordJD\Postcodes\Tests;

use JordJD\Postcodes\Objects\PostcodesIo;
use PHPUnit\Framework\TestCase;

final class PostcodesIoTest extends TestCase
{
    public function validationProvider()
    {
        return [
            ['ST163DP'],
            ['TN30YA'],
        ];
    }

    /**
     * @dataProvider validationProvider
     */
    public function testLookup($postcode)
    {
        try {
            $postcodeLookupService = new PostcodesIo();
            $addresses = $postcodeLookupService->getAddressesByPostcode($postcode);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Postcodes.io lookup unavailable: ' . $e->getMessage());
            return;
        }

        $address = $addresses[0];

        $this->assertNotEmpty($address->townCity);
        $this->assertNotEmpty($address->postcode);
        $this->assertIsNumeric($address->longitude);
        $this->assertIsNumeric($address->latitude);
    }
}
