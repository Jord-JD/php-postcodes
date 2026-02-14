<?php

namespace JordJD\Postcodes\Objects;

use JordJD\Postcodes\Interfaces\PostcodeServiceInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;

class PostcodesIo implements PostcodeServiceInterface
{
    private $client;

    public function __construct($apiKey = null)
    {
        if ($apiKey) {
            throw new InvalidArgumentException('Postcodes.io does not require an API key.');
        }

        $this->client = new Client(['base_uri' => 'https://api.postcodes.io/', 'timeout'  => 3.0]);
    }

    public function getAddressesByPostcode($postcode)
    {
        $response = $this->client->request('GET', 'postcodes/'.$postcode);

        $result = $this->parseResponse($response);

        return $result;
    }

    private function parseResponse(Response $response)
    {
        if ($response->getStatusCode() != 200) {
            throw new Exception('HTTP response code was not 200. Received HTTP reponse code: '.$response->getStatusCode().' ('.$response->getReasonPhrase().')');
        }

        $object = json_decode($response->getBody());

        if (!$object) {
            throw new Exception('Response JSON could not be decoded.');
        }

        if (!isset($object->status) || !is_numeric($object->status)) {
            throw new Exception('Response status not found or invalid.');
        }

        if ($object->status != 200) {
            throw new Exception('Response status was not 200. Response status: '.$object->status);
        }

        if (!isset($object->result)) {
            throw new Exception('Response does not contain a result.');
        }

        $postcodesIoAddress = $object->result;

        $addresses = [];

        $address = new Address();
        $address->line2 = $postcodesIoAddress->parish;
        $address->townCity = $postcodesIoAddress->admin_district;
        $address->county = $postcodesIoAddress->admin_county;
        $address->postcode = $postcodesIoAddress->postcode;
        $address->longitude = $postcodesIoAddress->longitude;
        $address->latitude = $postcodesIoAddress->latitude;

        $addresses[] = $address;

        return $addresses;
    }
}
