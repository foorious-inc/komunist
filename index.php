<?php
/*
Ok, so this is the API.

We're currently using two file: Istat e Comuni JSON. The reason for this is simply that Comuni JSON doesn't have NUTS codes.
*/
$display_errors = reset(explode(':', $_SERVER['HTTP_HOST'])) == 'localhost' && 0;
error_reporting(E_ALL);
ini_set('display_errors', $display_errors);

// array holding allowed origin domains. can be '*' for all, or array for specific domains
$allowed_origins = '*'; 
// $allowed_origins = [
//     '(http(s)://)?(www\.)?my\-domain\.com',
//     'etc',
//     'etc'
// ];

$allow = false;
if ($allowed_origins == '*') {
    $allow = true;
} else {
    if (!is_array($allowed_origins)) {
        $allowed_origins = [$allowed_origins];
    }

    if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] != '') {
        if (is_array($allowed_origins)) {
            foreach ($allowed_origins as $allowed_origin) {
                if (preg_match('#' . $allowed_origin . '#', $_SERVER['HTTP_ORIGIN'])) {
                    $allow = true;
                    break;
                }
            }
        }
    }
}

if ($allow) {
    header('Access-Control-Allow-Origin: ' . (!empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : ''));
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        
        exit;
    }
}

require_once 'vendor/autoload.php';
require_once 'src/Foorious/Komunist.php';

define('VALID_TOKEN', 'LOC_BN78FGHiH839X'); // this is not for security, just so we can more easily disable the API if there is ever abused

try {
    // set routes
    route('GET', '/api/v1/locations', function($args) {
        $location_type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
        $locations = \Foorious\Komunist::getLocations($location_type, [
            'region' => !empty($_GET['region']) ? $_GET['region'] : '', 
            'province' => !empty($_GET['province']) ? $_GET['province'] : ''
        ], \Foorious\Komunist::RETURN_TYPE_ARRAY);

        return response(json_encode([
            'count' => count($locations),
            'locations' => $locations
        ]), 200, ['content-type' => 'application/json']);        
    });

    route('GET', '/api/v1/postcodes/:postcode', function($args) {
        $postcode = $args['postcode'];
        if (!$postcode) {
            throw new \Exception('postcode missing');
        }
        if (!is_numeric($postcode)) {
            throw new \Exception('invalid postcode');
        }

        $city = \Foorious\Komunist::getCityByPostcode($postcode, \Foorious\Komunist::RETURN_TYPE_ARRAY);

        $status_code = $city ? 200 : 404;
        return response(json_encode([
            'ok' => true,

            'city' => $city
        ]), $status_code);        
    });        

    dispatch(VALID_TOKEN);
} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
?>