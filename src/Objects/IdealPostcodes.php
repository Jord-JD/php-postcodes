<?php

namespace JordJD\Postcodes\Objects;

use JordJD\Postcodes\Interfaces\PostcodeServiceInterface;
use JordJD\Postcodes\Exceptions\InvalidPostcodeException;
use JordJD\Postcodes\Utils\Validator;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

class IdealPostcodes implements PostcodeServiceInterface
{
    private $apiKey = null;
    private $client = null;

    public function __construct($apiKey, ClientInterface $client = null)
    {
        if (!$apiKey) {
            throw new Exception('No Ideal Postcodes API key specified.');
        }

        $this->apiKey = $apiKey;

        $this->client = $client ?: new Client(['timeout' => 3.0]);
    }

    public function getAddressesByPostcode($postcode)
    {
        if (!Validator::validatePostcode($postcode)) {
            throw new InvalidPostcodeException('Post code provided is not valid');
        }

        $postcode = str_replace(' ', '', Validator::normalizePostcode($postcode));
        $response = $this->client->request(
            'GET',
            'https://api.ideal-postcodes.co.uk/v1/postcodes/'.rawurlencode($postcode),
            [
                'headers' => ['Authorization' => 'api_key="'.$this->apiKey.'"'],
                'http_errors' => false,
            ]
        );

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

        if (!isset($object->code) || !is_numeric($object->code)) {
            throw new Exception('Response code not found or invalid.');
        }

        if ($object->code != 2000) {
            throw new Exception('Response code was not 2000. Response message: '.((isset($object->message) ? $object->message : '(none)')));
        }

        if (!isset($object->result)) {
            throw new Exception('Response does not contain a result.');
        }

        $addresses = [];

        foreach ($object->result as $idealPostcodesAddress) {
            $address = new Address();
            $address->companyName = isset($idealPostcodesAddress->organisation_name) ? $idealPostcodesAddress->organisation_name : '';
            $address->line1 = isset($idealPostcodesAddress->line_1) ? $idealPostcodesAddress->line_1 : '';
            $address->line2 = isset($idealPostcodesAddress->line_2) ? $idealPostcodesAddress->line_2 : '';
            $address->line3 = isset($idealPostcodesAddress->line_3) ? $idealPostcodesAddress->line_3 : '';
            $address->townCity = isset($idealPostcodesAddress->post_town) ? $idealPostcodesAddress->post_town : '';
            $address->county = isset($idealPostcodesAddress->county) ? $idealPostcodesAddress->county : '';
            $address->country = isset($idealPostcodesAddress->country) ? $idealPostcodesAddress->country : 'United Kingdom';
            $address->postcode = isset($idealPostcodesAddress->postcode) ? $idealPostcodesAddress->postcode : '';
            $address->longitude = isset($idealPostcodesAddress->longitude) ? $idealPostcodesAddress->longitude : null;
            $address->latitude = isset($idealPostcodesAddress->latitude) ? $idealPostcodesAddress->latitude : null;
            $addresses[] = $address;
        }

        return $addresses;
    }
}
