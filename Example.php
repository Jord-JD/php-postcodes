<?php

require_once 'vendor/autoload.php';
use JordJD\Postcodes\Objects\IdealPostcodes;
use JordJD\Postcodes\Objects\PostcodeAnywhere;
use JordJD\Postcodes\Utils\Generator;
use JordJD\Postcodes\Utils\Validator;

$postcode = Generator::generatePostcode();

var_dump($postcode);

$validated = Validator::validatePostcode($postcode);

var_dump($validated);

$idealPostcodes = new IdealPostCodes('API_KEY');
$addresses = $idealPostcodes->getAddressesByPostcode('ST16 3DP');
var_dump($addresses);

$postcodeAnywhere = new PostcodeAnywhere('API_KEY');
$addresses = $postcodeAnywhere->getAddressesByPostcode('ST16 3DP');
var_dump($addresses);
