<?php

namespace JordJD\Postcodes\Objects;

use JordJD\Postcodes\Interfaces\PostcodeServiceInterface;
use JordJD\Postcodes\Exceptions\InvalidPostcodeException;
use JordJD\Postcodes\Utils\Validator;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;

class PostcodeAnywhere implements PostcodeServiceInterface
{
    private $apiKey = null;
    private $client = null;

    public function __construct($apiKey, ClientInterface $client = null)
    {
        if (!$apiKey) {
            throw new Exception('No Postcode Anywhere API key specified.');
        }

        $this->apiKey = $apiKey;

        $this->client = $client ?: new Client(['timeout' => 3.0]);
    }

    public function getAddressesByPostcode($postcode)
    {
        $findResponseAddresses = $this->getFindResponseAddressesByPostcode($postcode);

        $addresses = [];

        foreach ($findResponseAddresses as $findResponseAddress) {
            $addresses[] = $this->getAddressById($findResponseAddress->Id);
        }

        return $addresses;
    }

    public function getFindResponseAddressesByPostcode($postcode)
    {
        if (!Validator::validatePostcode($postcode)) {
            throw new InvalidPostcodeException('Post code provided is not valid');
        }

        $postcode = Validator::normalizePostcode($postcode);
        $pendingContainers = [null];
        $seenContainers = [];
        $addresses = [];

        while ($pendingContainers) {
            $container = array_pop($pendingContainers);
            $items = $this->find($postcode, $container);

            foreach ($items as $item) {
                if (isset($item->Type) && strtolower($item->Type) === 'address') {
                    $addresses[] = $item;
                    continue;
                }

                if (isset($item->Id) && !isset($seenContainers[$item->Id])) {
                    $seenContainers[$item->Id] = true;
                    $pendingContainers[] = $item->Id;

                    if (count($seenContainers) > 100) {
                        throw new Exception('Loqate returned too many nested address containers.');
                    }
                }
            }
        }

        return $addresses;
    }

    public function getAddressById($id)
    {
        if (!is_string($id) || trim($id) === '') {
            throw new InvalidArgumentException('A Loqate address ID must be specified.');
        }

        $response = $this->client->request(
            'GET',
            'https://api.addressy.com/Capture/Interactive/Retrieve/v1.30/json6.ws',
            [
                'query' => ['Key' => $this->apiKey, 'Id' => $id],
                'http_errors' => false,
            ]
        );
        $items = $this->parseItemsResponse($response, 'Loqate Retrieve');

        if (!$items) {
            throw new Exception('Loqate Retrieve response does not contain an address.');
        }

        $retrieveAddress = $items[0];

        $address = new Address();
        $address->companyName = isset($retrieveAddress->Company) ? $retrieveAddress->Company : '';
        $address->line1 = isset($retrieveAddress->Line1) ? $retrieveAddress->Line1 : '';
        $address->line2 = isset($retrieveAddress->Line2) ? $retrieveAddress->Line2 : '';
        $address->line3 = isset($retrieveAddress->Line3) ? $retrieveAddress->Line3 : '';
        $address->townCity = isset($retrieveAddress->City) ? $retrieveAddress->City : '';
        $address->county = isset($retrieveAddress->ProvinceName) ? $retrieveAddress->ProvinceName : '';
        $address->postcode = isset($retrieveAddress->PostalCode) ? $retrieveAddress->PostalCode : '';
        $address->country = isset($retrieveAddress->CountryName) ? $retrieveAddress->CountryName : 'United Kingdom';
        $address->longitude = isset($retrieveAddress->Longitude) ? $retrieveAddress->Longitude : null;
        $address->latitude = isset($retrieveAddress->Latitude) ? $retrieveAddress->Latitude : null;

        return $address;
    }

    private function find($postcode, $container)
    {
        $query = [
            'Key' => $this->apiKey,
            'Text' => $postcode,
            'Countries' => 'GBR',
            'Limit' => 100,
        ];

        if ($container !== null) {
            $query['Container'] = $container;
        }

        $response = $this->client->request(
            'GET',
            'https://api.addressy.com/Capture/Interactive/Find/v1.20/json6.ws',
            ['query' => $query, 'http_errors' => false]
        );

        return $this->parseItemsResponse($response, 'Loqate Find');
    }

    private function parseItemsResponse($response, $operation)
    {
        if ($response->getStatusCode() != 200) {
            throw new Exception($operation.' HTTP response code was not 200. Received '.$response->getStatusCode().' ('.$response->getReasonPhrase().')');
        }

        $object = json_decode((string) $response->getBody());

        if (!is_object($object)) {
            throw new Exception($operation.' response JSON could not be decoded.');
        }

        if (!isset($object->Items) || !is_array($object->Items)) {
            throw new Exception($operation.' response does not contain an Items array.');
        }

        foreach ($object->Items as $item) {
            if (isset($item->Error)) {
                $message = isset($item->Description) ? $item->Description : $item->Error;
                throw new Exception($operation.' failed: '.$message);
            }
        }

        return $object->Items;
    }
}
