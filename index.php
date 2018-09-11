<?php
/*
Ok, so this is the API.

We're currently using two file: Istat e Comuni JSON. The reason for this is simply that Comuni JSON doesn't have NUTS codes.
*/

$display_errors = reset(explode(':', $_SERVER['HTTP_HOST'])) == 'localhost';
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

require 'vendor/autoload.php';

define('ISTAT_DATA_FILE', __DIR__ . '/data/cities.csv'); // "CODICI STATISTICI DELLE UNITÀ AMMINISTRATIVE TERRITORIALI: COMUNI, CITTÀ METROPOLITANE, PROVINCE E REGIONI" from Istat, see https://www.istat.it/it/archivio/6789
define('COMUNIJSON_DATA_FILE', __DIR__ . '/data/comuni-json-2018-03-31/comuni.json'); // https://github.com/matteocontrini/comuni-json/
define('VALID_TOKEN', 'LOC_BN78FGHiH839X'); // this is not for security, just so we can more easily disable the API if there is ever abused

try {
    if (!isset($_GET['access_token'])) {
        throw new \Exception('access token missing');
    }
    if ($_GET['access_token'] != VALID_TOKEN) {
        throw new \Exception('invalid access token');
    }

    $_ISTAT_DATA = [];

    ///////////////////////////////// add Istat data to cache /////////////////////////////////
    /* 
    Fields we're currently interested in:
    ---
    5 Denominazione in italiano
    6 Denominazione in tedesco
    12 Flag Comune capoluogo di provincia    
    13 Sigla automobilistica
    18 Codice Catastale del comune
    19 Popolazione legale 2011 (09/10/2011)
    22 Codice NUTS3 2010

    Everything:
    ---
    0 Codice Regione
    1 Codice Città Metropolitana
    2 Codice Provincia (1)
    3 Progressivo del Comune (2)
    4 Codice Comune formato alfanumerico
    5 Denominazione in italiano
    6 Denominazione in tedesco
    7 Codice Ripartizione Geografica
    8 Ripartizione geografica
    9 Denominazione regione
    10 Denominazione Città metropolitana
    11 Denominazione provincia
    12 Flag Comune capoluogo di provincia
    13 Sigla automobilistica
    14 Codice Comune formato numerico
    15 Codice Comune numerico con 110 province (dal 2010 al 2016)
    16 Codice Comune numerico con 107 province (dal 2006 al 2009)
    17 Codice Comune numerico con 103 province (dal 1995 al 2005)
    18 Codice Catastale del comune
    19 Popolazione legale 2011 (09/10/2011)
    20 Codice NUTS1 2010
    21 Codice NUTS2 2010 (3) 
    22 Codice NUTS3 2010
    23 Codice NUTS1 2006
    24 Codice NUTS2 2006 (3)
    25 Codice NUTS3 2006
    */
    $istat_csv = file_get_contents(ISTAT_DATA_FILE);
    
    $lines = explode("\n", trim($istat_csv, "\n"));
    for ($i=0; $i<count($lines); $i++) {
        if ($i == 0) {
            continue;
        }

        $line = $lines[$i];

        $fields = explode(';', $line);

        $is_province = (bool) $fields[12];
        $population = (int) str_replace(',', '', $fields[19]);
        $location_id = $fields[22] . $fields[18];
        $_ISTAT_DATA[$location_id] = [
            'id' => $location_id,
            'name' => $fields[5] . ($fields[6] ? '/' . $fields[6] : ''),

            'nuts3_2010_code' => $fields[22],
            'cad_code' => $fields[18],
            'license_plate_code' =>$fields[13],

            'population' => $population,
            'is_province' => $is_province,

            'region' => [
                'id' => $fields[21],
                'name' => $fields[9]
            ]
        ];
    }

    ///////////////////////////////// add postcodes to cache /////////////////////////////////

    function get_location_data($_ISTAT_DATA, $data_type, $options=[]) {
        $data = [];
    
        switch ($data_type) {
            // case 'zones':
            //     // ITC NORD-OVEST
            //     // ITH NORD-EST
            //     // ITI CENTRO            
            //     // ITF SUD
            //     // ITG ISOLE
            //     // ITZ EXTRA-REGIO            
            //     break;
            case 'locations':
                $regions = get_location_data($_ISTAT_DATA, 'regions', $options);
                $provinces = get_location_data($_ISTAT_DATA, 'provinces', $options);
                $cities = get_location_data($_ISTAT_DATA, 'cities', $options);

                $data = array_merge($regions, $provinces, $cities);
                break;
            case 'regions':
                $data = [
                    [
                        'id' => 'ITC1',
                        'type' => 'region',
                        'name' => 'Piemonte',
                        'nuts3_2010_code' => ''
                    ],
                    [
                        'id' => 'ITC2',
                        'type' => 'region',
                        'name' => 'Valle d’Aosta/Vallée d’Aoste',
                        'nuts3_2010_code' => 'ITC2'
                    ],
                    [
                        'id' => 'ITC3',
                        'type' => 'region',
                        'name' => 'Liguria',
                        'nuts3_2010_code' => 'ITC3'
                    ],                                        
                    [
                        'id' => 'ITC4',
                        'type' => 'region',
                        'name' => 'Lombardia',
                        'nuts3_2010_code' => 'ITC4'
                    ],
                    [
                        'id' => 'ITF1',
                        'type' => 'region',
                        'name' => 'Abruzzo',
                        'nuts3_2010_code' => 'ITF1'
                    ],
                    [
                        'id' => 'ITF2',
                        'type' => 'region',
                        'name' => 'Molise',
                        'nuts3_2010_code' => 'ITF2'
                    ],                                        
                    [
                        'id' => 'ITF3',
                        'type' => 'region',
                        'name' => 'Campania',
                        'nuts3_2010_code' => 'ITF3'
                    ],
                    [
                        'id' => 'ITF4',
                        'type' => 'region',
                        'name' => 'Puglia',
                        'nuts3_2010_code' => 'ITF4'
                    ],
                    [
                        'id' => 'ITF5',
                        'type' => 'region',
                        'name' => 'Basilicata',
                        'nuts3_2010_code' => 'ITF5'
                    ],                                        
                    [
                        'id' => 'ITF6',
                        'type' => 'region',
                        'name' => 'Calabria',
                        'nuts3_2010_code' => 'ITF6'
                    ],
                    [
                        'id' => 'ITG1',
                        'type' => 'region',
                        'name' => 'Sicilia',
                        'nuts3_2010_code' => 'ITG1'
                    ],
                    [
                        'id' => 'ITG2',
                        'type' => 'region',
                        'name' => 'Sardegna',
                        'nuts3_2010_code' => 'ITG2'
                    ],                                        
                    [
                        'id' => 'ITH1',
                        'type' => 'region',
                        'name' => 'Provincia Autonoma di Bolzano/Bozen',
                        'nuts3_2010_code' => 'ITH1'
                    ],
                    [
                        'id' => 'ITH2',
                        'type' => 'region',
                        'name' => 'Provincia Autonoma di Trento',
                        'nuts3_2010_code' => 'ITH2'
                    ],
                    [
                        'id' => 'ITH3',
                        'type' => 'region',
                        'name' => 'Veneto',
                        'nuts3_2010_code' => 'ITH3'
                    ],                                        
                    [
                        'id' => 'ITH4',
                        'type' => 'region',
                        'name' => 'Friuli-Venezia Giulia',
                        'nuts3_2010_code' => 'ITH4'
                    ],
                    [
                        'id' => 'ITH5',
                        'type' => 'region',
                        'name' => 'Emilia-Romagna',
                        'nuts3_2010_code' => 'ITH5'
                    ],
                    [
                        'id' => 'ITI1',
                        'type' => 'region',
                        'name' => 'Toscana',
                        'nuts3_2010_code' => 'ITI1'
                    ],                                        
                    [
                        'id' => 'ITI2',
                        'type' => 'region',
                        'name' => 'Umbria',
                        'nuts3_2010_code' => 'ITI2'
                    ],
                    [
                        'id' => 'ITI3',
                        'type' => 'region',
                        'name' => 'Marche',
                        'nuts3_2010_code' => 'ITI3'
                    ],
                    [
                        'id' => 'ITI4',
                        'type' => 'region',
                        'name' => 'Lazio',
                        'nuts3_2010_code' => 'ITI4'
                    ]
                ];
                break;
            case 'provinces':
                foreach ($_ISTAT_DATA as $city_data) {
                    if ($city_data['is_province']) {
                        $city_data['type'] = 'province';
                        $data[] = $city_data;
                    }
                }            
                break;                
            case 'cities':
                foreach ($_ISTAT_DATA as $city_data) {
                    $city_data['type'] = 'city';
                    $data[] = $city_data;
                }            
                break;            
            default:   
                throw new \Exception('cannot handle route');
        }

        // transform in ID-based array
        $data_tmp = [];
        foreach ($data as $k=>$v) {
            if (!$data[$k]['id']) {
                throw new Exception('location does not have ID');
            }

            $data_tmp[$data[$k]['id']] = $v;
        }
        $data = $data_tmp;

        // filter data
        if (is_array($options) && count($options)) {
            foreach ($data as $location_id => $location) {
                if (isset($options['country']) && $options['country']) {
                    // to figure out country, look at first 2 letter of ID.
                    if (substr($location['id'], 0, 2) != $options['country']) {
                        unset($data[$location_id]);

                        continue;
                    }
                }
                if (isset($options['region']) && $options['region']) {
                    // to figure out country, look at first 4 letters of ID.
                    if (substr($location['id'], 0, 4) != substr($options['region'], 0, 4)) {
                        unset($data[$location_id]);

                        continue;
                    }
                }                                
                if (isset($options['province']) && $options['province']) {
                    // to figure out country, look at first 5 letters of ID.
                    if (substr($location['id'], 0, 5) != substr($options['province'], 0, 5)) {
                        unset($data[$location_id]);

                        continue;
                    }
                }                
            }
        }

        // sort data
        usort($data, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $data;
    }

    // set routes
    route('GET', '/api/v1/locations', function($args) {
        global $_ISTAT_DATA;

        $type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';

        switch ($type) {
            case 'region':
                $data = get_location_data($_ISTAT_DATA, 'regions', [
                    'country' => 'IT',
                    'region' => isset($_GET['region']) ? $_GET['region'] : '',
                    'province' => isset($_GET['province']) ? $_GET['province'] : ''
                ]);
                break;
            case 'province':
                $data = get_location_data($_ISTAT_DATA, 'provinces', [
                    'country' => 'IT',
                    'region' => isset($_GET['region']) ? $_GET['region'] : '',
                    'province' => isset($_GET['province']) ? $_GET['province'] : ''
                ]);
                break;
            case 'city':
                $data = get_location_data($_ISTAT_DATA, 'cities', [
                    'country' => 'IT',
                    'region' => isset($_GET['region']) ? $_GET['region'] : '',
                    'province' => isset($_GET['province']) ? $_GET['province'] : ''
                ]);
                break;
            default:
                $data = get_location_data($_ISTAT_DATA, 'locations', [
                    'country' => 'IT',
                    'region' => isset($_GET['region']) ? $_GET['region'] : '',
                    'province' => isset($_GET['province']) ? $_GET['province'] : ''
                ]);
        }

        return response(json_encode([
            'count' => count($data),
            'locations' => $data
        ]), 200, ['content-type' => 'application/json']);        
    });

    route('GET', '/api/v1/postcodes/:postcode', function($args) {
        global $_ISTAT_DATA;

        $comunijson_json = json_decode(file_get_contents(COMUNIJSON_DATA_FILE), true);
        if (!$comunijson_json) {
            throw new \Exception('unable to parse JSON');
        }

        foreach ($comunijson_json as $city) {
            if (in_array($args['postcode'], $city['cap'])) {
                // we have data, but we have to query Istat data to get NUTS codes (and therefore location ID). Use cad code since it's one thing the 2 data sources have in common
                $istat_cities = get_location_data($_ISTAT_DATA, 'cities');
                foreach ($istat_cities as $istat_city) {
                    if ($istat_city['cad_code'] == $city['codiceCatastale']) {
                        $location_id = $istat_city['nuts3_2010_code'] . $city['codiceCatastale'];

                        return response(json_encode([
                            'ok' => true,
                            'data' => $_ISTAT_DATA[$location_id]
                        ]), 200, ['content-type' => 'application/json']);        
                    }
                }
            }
        }

        return response(json_encode([
            'ok' => true,
        ]), 404);        
    });        

    dispatch($_ISTAT_DATA);
} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
?>