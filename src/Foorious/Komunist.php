<?php
namespace Foorious;

class Komunist 
{
    const ISTAT_DATA_FILE = __DIR__ . '/../../data/cities.csv';
    const COMUNIJSON_DATA_FILE = __DIR__ . '/../../data/comuni-json-2018-03-31/comuni.json';

    const RETURN_TYPE_ARRAY = 'array';

    const LOCATION_TYPE_CITY = 'city';
    const LOCATION_TYPE_PROVINCE = 'province';
    const LOCATION_TYPE_REGION = 'region';

    private static function _getIstatData() {
        $istat_data = [];

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
        if (!is_readable(self::ISTAT_DATA_FILE)) {
            throw new \Exception('ISTAT file not readable');
        }
        $istat_csv = file_get_contents(self::ISTAT_DATA_FILE);
        if (!$istat_csv) {
            throw new \Exception('cannot read ISTAT data CSV');
        }

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
            $istat_data[$location_id] = [
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

        return $istat_data;
    }

    private static function _getLocationData($location_type, $options=[]) {
        $istat_data = self::_getIstatData();
        if (!$istat_data) {
            throw new \Exception('unable to get ISTAT data');
        }

        $data = [];
    
        switch ($location_type) {
            // case 'zones':
            //     // ITC NORD-OVEST
            //     // ITH NORD-EST
            //     // ITI CENTRO            
            //     // ITF SUD
            //     // ITG ISOLE
            //     // ITZ EXTRA-REGIO            
            //     break;
            case 'location':
                $regions = self::_getLocationData(self::LOCATION_TYPE_REGION, $options);
                $provinces = self::_getLocationData(self::LOCATION_TYPE_PROVINCE, $options);
                $cities = self::_getLocationData(self::LOCATION_TYPE_CITY, $options);

                $data = array_merge($regions, $provinces, $cities);
                break;
            case self::LOCATION_TYPE_REGION:
                $data = [
                    [
                        'id' => 'ITC1',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Piemonte',
                        'nuts3_2010_code' => ''
                    ],
                    [
                        'id' => 'ITC2',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Valle d’Aosta/Vallée d’Aoste',
                        'nuts3_2010_code' => 'ITC2'
                    ],
                    [
                        'id' => 'ITC3',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Liguria',
                        'nuts3_2010_code' => 'ITC3'
                    ],                                        
                    [
                        'id' => 'ITC4',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Lombardia',
                        'nuts3_2010_code' => 'ITC4'
                    ],
                    [
                        'id' => 'ITF1',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Abruzzo',
                        'nuts3_2010_code' => 'ITF1'
                    ],
                    [
                        'id' => 'ITF2',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Molise',
                        'nuts3_2010_code' => 'ITF2'
                    ],                                        
                    [
                        'id' => 'ITF3',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Campania',
                        'nuts3_2010_code' => 'ITF3'
                    ],
                    [
                        'id' => 'ITF4',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Puglia',
                        'nuts3_2010_code' => 'ITF4'
                    ],
                    [
                        'id' => 'ITF5',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Basilicata',
                        'nuts3_2010_code' => 'ITF5'
                    ],                                        
                    [
                        'id' => 'ITF6',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Calabria',
                        'nuts3_2010_code' => 'ITF6'
                    ],
                    [
                        'id' => 'ITG1',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Sicilia',
                        'nuts3_2010_code' => 'ITG1'
                    ],
                    [
                        'id' => 'ITG2',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Sardegna',
                        'nuts3_2010_code' => 'ITG2'
                    ],                                        
                    [
                        'id' => 'ITH1',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Provincia Autonoma di Bolzano/Bozen',
                        'nuts3_2010_code' => 'ITH1'
                    ],
                    [
                        'id' => 'ITH2',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Provincia Autonoma di Trento',
                        'nuts3_2010_code' => 'ITH2'
                    ],
                    [
                        'id' => 'ITH3',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Veneto',
                        'nuts3_2010_code' => 'ITH3'
                    ],                                        
                    [
                        'id' => 'ITH4',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Friuli-Venezia Giulia',
                        'nuts3_2010_code' => 'ITH4'
                    ],
                    [
                        'id' => 'ITH5',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Emilia-Romagna',
                        'nuts3_2010_code' => 'ITH5'
                    ],
                    [
                        'id' => 'ITI1',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Toscana',
                        'nuts3_2010_code' => 'ITI1'
                    ],                                        
                    [
                        'id' => 'ITI2',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Umbria',
                        'nuts3_2010_code' => 'ITI2'
                    ],
                    [
                        'id' => 'ITI3',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Marche',
                        'nuts3_2010_code' => 'ITI3'
                    ],
                    [
                        'id' => 'ITI4',
                        'type' => self::LOCATION_TYPE_REGION,
                        'name' => 'Lazio',
                        'nuts3_2010_code' => 'ITI4'
                    ]
                ];
                break;
            case self::LOCATION_TYPE_PROVINCE:
                foreach ($istat_data as $city_data) {
                    if ($city_data['is_province']) {
                        $city_data['type'] = self::LOCATION_TYPE_PROVINCE;
                        $data[] = $city_data;
                    }
                }            
                break;                
            case self::LOCATION_TYPE_CITY:
                foreach ($istat_data as $city_data) {
                    $city_data['type'] = self::LOCATION_TYPE_CITY;
                    $data[] = $city_data;
                }            
                break;            
            default:   
                throw new \Exception('unknown location type: ' . $location_type);
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

    public static function getLocations($location_type='', $options, $return_type) {
        $country = 'IT';
        $region = $options['region'] ? $options['region'] : '';
        $province = $options['province'] ? $options['province'] : '';
        
        // in the future, we will return objects instead of arrays (although one will be able to use toArray()).
        // let's force passing output type explicityl to keep compatibility/ease migration in the future
        if ($return_type != self::RETURN_TYPE_ARRAY) {
            throw new \Exception('unsupported output type');
        }

        $istat_data = self::_getIstatData();
        $locations = [];
        
        if (!$location_type) {
            $location_type = 'location'; // return all
        }
        $locations = self::_getLocationData($location_type, [
            'country' => $country,
            'region' => $region,
            'province' => $province
        ]);

        return $locations;     
    }

    public static function getCityByPostcode($postcode, $return_type) {
        // in the future, we will return objects instead of arrays (although one will be able to use toArray()).
        // let's force passing output type explicityl to keep compatibility/ease migration in the future
        if ($return_type != self::RETURN_TYPE_ARRAY) {
            throw new \Exception('unsupported output type');
        }

        if (!is_readable(self::COMUNIJSON_DATA_FILE)) {
            throw new \Exception('cannot read comuni JSON');
        }
        $istat_data = self::_getIstatData();
        
        $comunijson_json = json_decode(file_get_contents(self::COMUNIJSON_DATA_FILE), true);
        if (!$comunijson_json) {
            throw new \Exception('unable to parse JSON');
        }

        foreach ($comunijson_json as $city) {
            if (in_array($postcode, $city['cap'])) {
                // we have data, but we have to query Istat data to get NUTS codes (and therefore location ID). Use cad code since it's one thing the 2 data sources have in common
                $istat_cities = self::_getLocationData('city');
                foreach ($istat_cities as $istat_city) {
                    if ($istat_city['cad_code'] == $city['codiceCatastale']) {
                        $location_id = $istat_city['nuts3_2010_code'] . $city['codiceCatastale'];

                        return $istat_data[$location_id];
                    }
                }
            }
        }

        return false;
    }

    public static function getCityByCadCode($cad_code, $return_type) {
        // in the future, we will return objects instead of arrays (although one will be able to use toArray()).
        // let's force passing output type explicityl to keep compatibility/ease migration in the future
        if ($return_type != self::RETURN_TYPE_ARRAY) {
            throw new \Exception('unsupported output type');
        }

        if (!is_readable(self::COMUNIJSON_DATA_FILE)) {
            throw new \Exception('cannot read comuni JSON');
        }
        $istat_data = self::_getIstatData();
        
        $comunijson_json = json_decode(file_get_contents(self::COMUNIJSON_DATA_FILE), true);
        if (!$comunijson_json) {
            throw new \Exception('unable to parse JSON');
        }

        foreach ($comunijson_json as $city) {
            if ($cad_code == $city['codiceCatastale']) {
                // we have data, but we have to query Istat data to get NUTS codes (and therefore location ID). Use cad code since it's one thing the 2 data sources have in common
                $istat_cities = self::_getLocationData('city');
                foreach ($istat_cities as $istat_city) {
                    if ($istat_city['cad_code'] == $city['codiceCatastale']) {
                        $location_id = $istat_city['nuts3_2010_code'] . $city['codiceCatastale'];

                        return $istat_data[$location_id];
                    }
                }
            }
        }

        return false;
    }

}
?>