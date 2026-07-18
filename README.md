# PHP Postcodes

[![Tests](https://github.com/Jord-JD/php-postcodes/actions/workflows/tests.yml/badge.svg)](https://github.com/Jord-JD/php-postcodes/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/dt/jord-jd/php-postcodes.svg)](https://packagist.org/packages/jord-jd/php-postcodes/stats)

This library handles various UK postcode related tasks.

## Features

* Address lookup by postcode
* Postcode validation
* Generate valid UK postcodes
* Get a postcode's outward and inward codes

## Installation

To install, just run the following composer command.

`composer require jord-jd/php-postcodes`

## Setup

### Postcode Lookup Services

Using some of the data retrieval features provided by this library requires a postcode lookup service.
It currently supports the following postcode lookup services.

* [Ideal Postcodes](https://ideal-postcodes.co.uk)
* [Loqate](https://www.loqate.com/) (the provider formerly known as Postcode Anywhere/PCA Predict)
* [Postcodes.io](https://postcodes.io/)

Ideal Postcodes and Postcode Anywhere can return individual premises. Postcodes.io only provides postcode-level geographic and administrative data, so its `getAddressesByPostcode()` implementation returns a single `Address` object for the postcode rather than a list of premises.

Sign up at the respective website if you need to use these features.

You can then use the following code to get an appropriate postcode lookup service object.

```php
$postcodeLookupService = new \JordJD\Postcodes\Objects\IdealPostcodes('API_KEY');
// OR
$postcodeLookupService = new \JordJD\Postcodes\Objects\PostcodeAnywhere('API_KEY');
// OR
$postcodeLookupService = new \JordJD\Postcodes\Objects\PostcodesIo();
```

## Usage

### Get addresses by postcode

To retrieve the addresses associated with a UK postcode, just pass it to the method shown below. 
You will receive an array of address objects, appropriately split by their address lines and other details.

The number and detail of results depend on the lookup service. In particular, Postcodes.io always returns one postcode-level result; use a premises-capable provider when you need every deliverable address.

```php
$addresses = $postcodeLookupService->getAddressesByPostcode('ST163DP');
```

### Validate postcode

You can validate a UK postcode is correct using the `Validator` utility class. An example of 
how to do so is shown below.

```php
$validated = \JordJD\Postcodes\Utils\Validator::validatePostcode('ST163DP');
```

Please note that the postcode validation is case insensitive.

You can also normalize user input to the conventional uppercase format. Invalid
non-string or incomplete input returns `null`.

```php
$postcode = \JordJD\Postcodes\Utils\Validator::normalizePostcode(" sw1a\t2aa ");
// SW1A 2AA
```

### Generate postcode

This library allows you generate a random, valid UK postcode. This makes use of the
`Generator` utility class, as shown below.

```php
$postcode = \JordJD\Postcodes\Utils\Generator::generatePostcode();
```

### Get outward and inward codes

> The first part of the Postcode eg PO1 is called the outward code as it identifies the town or district to which the letter is to be sent for further sorting. The second part of the postcode eg 1EB is called the inward code.

```php
$outwardCode = \JordJD\Postcodes\Utils\Tokenizer::outward('ST163DP'); // Returns ST16
$inwardCode = \JordJD\Postcodes\Utils\Tokenizer::inward('ST163DP'); // Returns 3DP
```

## HTTP clients and errors

Ideal Postcodes, Loqate and Postcodes.io use their current HTTPS JSON APIs. Each
service accepts an optional Guzzle `ClientInterface` implementation as its
second constructor argument, which is useful for custom timeouts, proxies,
logging and tests.

```php
$client = new \GuzzleHttp\Client(['timeout' => 10]);
$service = new \JordJD\Postcodes\Objects\IdealPostcodes('API_KEY', $client);
```

Invalid postcodes throw `InvalidPostcodeException` before any API request is
made. Provider authentication, HTTP and response errors continue to throw an
exception with a descriptive message.

## Compatibility

PHP 7.1 through the current PHP 8.x releases are supported. Composer selects a
compatible maintained Guzzle and test-tool version for the PHP runtime in use.
