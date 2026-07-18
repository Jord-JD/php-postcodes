<?php

namespace JordJD\Postcodes\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use JordJD\Postcodes\Exceptions\InvalidPostcodeException;
use JordJD\Postcodes\Objects\IdealPostcodes;
use JordJD\Postcodes\Objects\PostcodeAnywhere;
use JordJD\Postcodes\Objects\PostcodesIo;
use PHPUnit\Framework\TestCase;

final class PostcodeServicesTest extends TestCase
{
    public function testPostcodesIoLookupMapsCurrentResponse()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 200,
                'result' => [
                    'postcode' => 'ST16 3DP',
                    'parish' => 'Stafford',
                    'admin_district' => 'Stafford',
                    'admin_county' => 'Staffordshire',
                    'country' => 'England',
                    'longitude' => -2.12,
                    'latitude' => 52.8,
                ],
            ])),
        ]);

        $service = new PostcodesIo(null, new Client(['handler' => $mock]));
        $addresses = $service->getAddressesByPostcode(' st16 3dp ');

        $this->assertCount(1, $addresses);
        $this->assertSame('Stafford', $addresses[0]->townCity);
        $this->assertSame('England', $addresses[0]->country);
        $this->assertSame('https://api.postcodes.io/postcodes/ST163DP', (string) $mock->getLastRequest()->getUri());
    }

    public function testPostcodesIoRejectsInvalidInputBeforeMakingRequest()
    {
        $this->expectException(InvalidPostcodeException::class);

        $service = new PostcodesIo(null, new Client(['handler' => new MockHandler()]));
        $service->getAddressesByPostcode(null);
    }

    public function testIdealPostcodesLookupMapsCurrentResponseAndAuthentication()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'code' => 2000,
                'result' => [[
                    'organisation_name' => 'Example Ltd',
                    'line_1' => '1 Example Street',
                    'line_2' => '',
                    'line_3' => '',
                    'post_town' => 'London',
                    'county' => 'Greater London',
                    'country' => 'England',
                    'postcode' => 'SW1A 2AA',
                    'longitude' => -0.1276,
                    'latitude' => 51.5034,
                ]],
            ])),
        ]);

        $service = new IdealPostcodes('ak_test', new Client(['handler' => $mock]));
        $addresses = $service->getAddressesByPostcode('SW1A2AA');

        $this->assertCount(1, $addresses);
        $this->assertSame('Example Ltd', $addresses[0]->companyName);
        $this->assertSame('api_key="ak_test"', $mock->getLastRequest()->getHeaderLine('Authorization'));
    }

    public function testLoqateLookupResolvesContainersUsingCurrentJsonApi()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'Items' => [[
                    'Id' => 'GB|RM|C|ST163DP',
                    'Type' => 'Postcode',
                    'Text' => 'ST16 3DP',
                ]],
            ])),
            new Response(200, [], json_encode([
                'Items' => [[
                    'Id' => 'GB|RM|A|123',
                    'Type' => 'Address',
                    'Text' => '1 Example Street',
                ]],
            ])),
            new Response(200, [], json_encode([
                'Items' => [[
                    'Company' => 'Example Ltd',
                    'Line1' => '1 Example Street',
                    'Line2' => '',
                    'Line3' => '',
                    'City' => 'Stafford',
                    'ProvinceName' => 'Staffordshire',
                    'PostalCode' => 'ST16 3DP',
                    'CountryName' => 'United Kingdom',
                ]],
            ])),
        ]);

        $service = new PostcodeAnywhere('AA11-AA11-AA11-AA11', new Client(['handler' => $mock]));
        $addresses = $service->getAddressesByPostcode('ST16 3DP');

        $this->assertCount(1, $addresses);
        $this->assertSame('1 Example Street', $addresses[0]->line1);
        $this->assertSame('Stafford', $addresses[0]->townCity);
        $this->assertSame('ST16 3DP', $addresses[0]->postcode);
        $this->assertStringContainsString('/Capture/Interactive/Retrieve/v1.30/json6.ws', (string) $mock->getLastRequest()->getUri());
    }
}
