<?php
require 'vendor/autoload.php';

define('DATA_FILE', __DIR__ . '/cities.csv');
define('VALID_TOKEN', 'BN78FGH'); // this is not for security, just so we can more easily disable the API if there is ever abuse


try {
    if (!isset($_GET['access_token'])) {
        throw new \Exception('access token missing');
    }
    if ($_GET['access_token'] != VALID_TOKEN) {
        throw new \Exception('invalid access token');
    }

    $cities_csv = file_get_contents(DATA_FILE);

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

    $_CACHE = [];

    $lines = explode("\n", trim($cities_csv, "\n"));

    for ($i=0; $i<count($lines); $i++) {
        if ($i == 0) {
            continue;
        }

        $line = $lines[$i];

        $fields = explode(';', $line);

        $is_province = (bool) $fields[12];
        $population = (int) str_replace(',', '', $fields[19]);
        $location_id = $fields[22] . '-' . $fields[18];
        $_CACHE[$location_id] = [
            'id' => $location_id,
            'name' => $fields[5] . ($fields[6] ? '/' . $fields[6] : ''),

            'nuts_2010_code' => $fields[22],
            'cad_code' => $fields[18],
            'license_plate_code' =>$fields[13],

            'population' => $population,
            'is_province' => $is_province
        ];
    }

    function get_data($_CACHE, $data_type, $options=[]) {
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
                $regions = get_data($_CACHE, 'regions', $options);
                foreach ($regions as $location) {
                    $location['type'] = 'region';

                    $data[] = $location;
                }

                $provinces = get_data($_CACHE, 'provinces', $options);
                foreach ($provinces as $location) {
                    $location['type'] = 'province';

                    $data[] = $location;
                }

                $cities = get_data($_CACHE, 'cities', $options);
                foreach ($cities as $location) {
                    $location['type'] = 'city';

                    $data[] = $location;
                }                
                break;
            case 'regions':
                $data = [
                    [
                        'id' => 'ITC1',
                        'name' => 'Piemonte',
                        'nuts_2010_code' => ''
                    ],
                    [
                        'id' => 'ITC2',
                        'name' => 'Valle d’Aosta/Vallée d’Aoste',
                        'nuts_2010_code' => 'ITC2'
                    ],
                    [
                        'id' => 'ITC3',
                        'name' => 'Liguria',
                        'nuts_2010_code' => 'ITC3'
                    ],                                        
                    [
                        'id' => 'ITC4',
                        'name' => 'Lombardia',
                        'nuts_2010_code' => 'ITC4'
                    ],
                    [
                        'id' => 'ITF1',
                        'name' => 'Abruzzo',
                        'nuts_2010_code' => 'ITF1'
                    ],
                    [
                        'id' => 'ITF2',
                        'name' => 'Molise',
                        'nuts_2010_code' => 'ITF2'
                    ],                                        
                    [
                        'id' => 'ITF3',
                        'name' => 'Campania',
                        'nuts_2010_code' => 'ITF3'
                    ],
                    [
                        'id' => 'ITF4',
                        'name' => 'Puglia',
                        'nuts_2010_code' => 'ITF4'
                    ],
                    [
                        'id' => 'ITF5',
                        'name' => 'Basilicata',
                        'nuts_2010_code' => 'ITF5'
                    ],                                        
                    [
                        'id' => 'ITF6',
                        'name' => 'Calabria',
                        'nuts_2010_code' => 'ITF6'
                    ],
                    [
                        'id' => 'ITG1',
                        'name' => 'Sicilia',
                        'nuts_2010_code' => 'ITG1'
                    ],
                    [
                        'id' => 'ITG2',
                        'name' => 'Sardegna',
                        'nuts_2010_code' => 'ITG2'
                    ],                                        
                    [
                        'id' => 'ITH1',
                        'name' => 'Provincia Autonoma di Bolzano/Bozen',
                        'nuts_2010_code' => 'ITH1'
                    ],
                    [
                        'id' => 'ITH2',
                        'name' => 'Provincia Autonoma di Trento',
                        'nuts_2010_code' => 'ITH2'
                    ],
                    [
                        'id' => 'ITH3',
                        'name' => 'Veneto',
                        'nuts_2010_code' => 'ITH3'
                    ],                                        
                    [
                        'id' => 'ITH4',
                        'name' => 'Friuli-Venezia Giulia',
                        'nuts_2010_code' => 'ITH4'
                    ],
                    [
                        'id' => 'ITH5',
                        'name' => 'Emilia-Romagna',
                        'nuts_2010_code' => 'ITH5'
                    ],
                    [
                        'id' => 'ITI1',
                        'name' => 'Toscana',
                        'nuts_2010_code' => 'ITI1'
                    ],                                        
                    [
                        'id' => 'ITI2',
                        'name' => 'Umbria',
                        'nuts_2010_code' => 'ITI2'
                    ],
                    [
                        'id' => 'ITI3',
                        'name' => 'Marche',
                        'nuts_2010_code' => 'ITI3'
                    ],
                    [
                        'id' => 'ITI4',
                        'name' => 'Lazio',
                        'nuts_2010_code' => 'ITI4'
                    ]
                ];
                break;
            case 'provinces':
                foreach ($_CACHE as $city_data) {
                    if ($city_data['is_province']) {
                        $data[] = $city_data;
                    }
                }            
                break;                
            case 'cities':
                foreach ($_CACHE as $city_data) {
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
            foreach ($data as $location_id=>$location) {
                foreach (['country', 'region', 'province'] as $option_name) {
                    if (isset($options[$option_name]) && $options[$option_name]) {
                        if (strpos(strtoupper($location['nuts_2010_code']), strtoupper($options[$option_name])) !== 0) {
                            unset($data[$location_id]);
                        }
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
    route('GET', '/api/v1/italy/locations', function($_CACHE) {
        $data = get_data($_CACHE, 'locations', [
            'country' => 'IT',
            'region' => isset($_GET['region']) ? $_GET['region'] : '',
            'province' => isset($_GET['province']) ? $_GET['province'] : ''
        ]);

        return response(json_encode([
            'count' => count($data),
            'locations' => $data
        ]), 200, ['content-type' => 'application/json']);
    });        
    route('GET', '/api/v1/italy/regions', function($_CACHE) {
        $data = get_data($_CACHE, 'regions', [
            'country' => 'IT',
            'region' => isset($_GET['region']) ? $_GET['region'] : '',
            'province' => isset($_GET['province']) ? $_GET['province'] : ''
        ]);

        return response(json_encode([
            'count' => count($data),
            'regions' => $data
        ]), 200, ['content-type' => 'application/json']);
    });    
    route('GET', '/api/v1/italy/provinces', function($_CACHE) {
        $data = get_data($_CACHE, 'provinces', [
            'country' => 'IT',
            'region' => isset($_GET['region']) ? $_GET['region'] : '',
            'province' => isset($_GET['province']) ? $_GET['province'] : ''
        ]);

        return response(json_encode([
            'count' => count($data),
            'provinces' => $data
        ]), 200, ['content-type' => 'application/json']);
    });     
    route('GET', '/api/v1/italy/cities', function($_CACHE) {
        $data = get_data($_CACHE, 'cities', [
            'country' => 'IT',
            'region' => isset($_GET['region']) ? $_GET['region'] : '',
            'province' => isset($_GET['province']) ? $_GET['province'] : ''
        ]);
        
        return response(json_encode([
            'count' => count($data),
            'cities' => $data
        ]), 200, ['content-type' => 'application/json']);
    });
   

    dispatch($_CACHE, null);
} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
?>