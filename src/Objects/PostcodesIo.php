<?php

namespace JordJD\Postcodes\Objects;

use JordJD\Postcodes\Interfaces\PostcodeServiceInterface;
use JordJD\Postcodes\Exceptions\InvalidPostcodeException;
use JordJD\Postcodes\Utils\Validator;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class PostcodesIo implements PostcodeServiceInterface
{
    private $client;

    public function __construct($apiKey = null, ClientInterface $client = null)
    {
        if ($apiKey) {
            throw new InvalidArgumentException('Postcodes.io does not require an API key.');
        }

        $this->client = $client ?: new Client(['timeout' => 3.0]);
    }

    public function getAddressesByPostcode($postcode)
    {
        if (!Validator::validatePostcode($postcode)) {
            throw new InvalidPostcodeException('Post code provided is not valid');
        }

        $postcode = str_replace(' ', '', Validator::normalizePostcode($postcode));
        $response = $this->client->request('GET', 'https://api.postcodes.io/postcodes/'.rawurlencode($postcode), ['http_errors' => false]);

        $result = $this->parseResponse($response);

        return $result;
    }

    private function parseResponse(ResponseInterface $response)
    {
        if ($response->getStatusCode() != 200) {
            throw new Exception('HTTP response code was not 200. Received HTTP reponse code: '.$response->getStatusCode().' ('.$response->getReasonPhrase().')');
        }

        $object = json_decode((string) $response->getBody());

        if (!is_object($object)) {
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
        $address->line2 = isset($postcodesIoAddress->parish) ? $postcodesIoAddress->parish : '';
        $address->townCity = isset($postcodesIoAddress->admin_district) ? $postcodesIoAddress->admin_district : '';
        $address->county = isset($postcodesIoAddress->admin_county) ? $postcodesIoAddress->admin_county : '';
        $address->country = isset($postcodesIoAddress->country) ? $postcodesIoAddress->country : 'United Kingdom';
        $address->postcode = isset($postcodesIoAddress->postcode) ? $postcodesIoAddress->postcode : '';
        $address->longitude = isset($postcodesIoAddress->longitude) ? $postcodesIoAddress->longitude : null;
        $address->latitude = isset($postcodesIoAddress->latitude) ? $postcodesIoAddress->latitude : null;

        $addresses[] = $address;

        return $addresses;
    }
}
