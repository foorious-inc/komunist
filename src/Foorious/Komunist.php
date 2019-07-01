<?php
namespace Foorious;

class Komunist
{
    const ISTAT_DATA_FILE = __DIR__ . '/../../data/Elenco-comuni-italiani_2019-05-15.csv';
    const COMUNIJSON_DATA_FILE = __DIR__ . '/../../data/comuni-json_2019-05-15.json';

    const RETURN_TYPE_ARRAY = 'array';

    const LOCATION_TYPE_CITY = 'city';
    const LOCATION_TYPE_PROVINCE = 'province';
    const LOCATION_TYPE_REGION = 'region';

    const ISTAT_COL_CITY_NAME_IT_INDEX =       6;
    const ISTAT_COL_CITY_NAME_ETC_INDEX =      7;
    const ISTAT_COL_REGION_NAME_INDEX =        10;
    const ISTAT_COL_IS_PROV_FLAG_INDEX =       12;
    const ISTAT_COL_IS_PROV_ISO_CODE =         13;
    const ISTAT_COL_CAD_CODE_INDEX =           18;
    const ISTAT_COL_POPULATION_INDEX =         19;
    const ISTAT_COL_NUTS3_2010_INDEX =         22;

    private static $_provincesByNUTSCode = []; // all provinces, organized by NUTS code
    private static $_cities = []; // all cities
    private static $_citiesByCadCode = []; // all cities, organized by postcode
    private static $_postcodes = []; // all postcodes, organized by cad codes

    private static function _init() {
        // build some caches and indexes just in the meantime that there's no database
        try {
            if (count(self::$_cities) > 0) { // already initialized
                return;
            }

            // first, build index of postcodes (needed by _getLocationsData)
            if (!is_readable(self::COMUNIJSON_DATA_FILE)) {
                throw new \Exception('Comuni JSON file missing/unreadable');
            }
            $comunijson_raw = file_get_contents(self::COMUNIJSON_DATA_FILE);
            if (!$comunijson_raw) {
                throw new \Exception('cannot read comuni JSON (no file contents)');
            }
            $comunijson_json = json_decode($comunijson_raw, true);
            if (!$comunijson_json) {
                throw new \Exception('unable to parse JSON');
            }
            foreach ($comunijson_json as $city) {
                if (!empty(self::$_postcodes[$city['codiceCatastale']])) {
                    throw new \Exception('trying to add postcode to index twice');
                }

                self::$_postcodes[$city['codiceCatastale']] = $city['cap'];
            }

            // index provinces by NUTS code
            if (empty(self::$_provincesByNUTSCode)) {
                $provinces = self::_getLocationData(self::LOCATION_TYPE_PROVINCE);

                foreach ($provinces as $province) {
                    if (empty($province['nuts3_2010_code'])) {
                        continue;
                    }

                    self::$_provincesByNUTSCode[$province['nuts3_2010_code']] = $province;
                }
            }

            // index cities by cad code
            if (empty(self::$_citiesByCadCode)) {
                $cities = self::_getLocationData(self::LOCATION_TYPE_CITY);
                foreach ($cities as $city) {
                    if (empty($city['cad_code'])) {
                        throw new \Exception('no CAD code!?');
                    }
                    if (!empty(self::$_citiesByCadCode[$city['cad_code']])) {
                        throw new \Exception('trying to add city to cad codes index again');
                    }

                    self::$_citiesByCadCode[$city['cad_code']] = $city;
                }
            }

            // save index of all cities
            self::$_cities = $cities;
        } catch (\Exception $e) {
           die('unable to create index: ' . $e->getMessage());
        }
    }

    private static function _getIstatData() {
        $istat_data = [];

        ///////////////////////////////// add Istat data to cache /////////////////////////////////
        /*
        Fields we're currently interested in (2019):
        ---
        6 Denominazione in italiano
        7 Denominazione altra lingua
        10 Denominazione regione
        12 Flag Comune capoluogo di provincia/città metropolitana/libero consorzio
        13 Sigla automobilistica
        18 Codice Catastale del comune
        19 Popolazione legale 2011 (09/10/2011)
        22 NUTS3

        Everything (2019):
        ---
        0 Codice Regione
        1 Codice dell'Unità territoriale sovracomunale (valida a fini statistici)
        2 Codice Provincia (Storico)(1)
        3 Progressivo del Comune (2)
        4 Codice Comune formato alfanumerico
        5 Denominazione (Italiana e straniera)
        6 Denominazione in italiano
        7 Denominazione altra lingua
        8 Codice Ripartizione Geografica
        9 Ripartizione geografica
        10 Denominazione regione
        11 "Denominazione dell'Unità territoriale sovracomunale (valida a fini statistici)"
        12 Flag Comune capoluogo di provincia/città metropolitana/libero consorzio
        13 Sigla automobilistica
        14 Codice Comune formato numerico
        15 Codice Comune numerico con 110 province (dal 2010 al 2016)
        16 Codice Comune numerico con 107 province (dal 2006 al 2009)
        17 Codice Comune numerico con 103 province (dal 1995 al 2005)
        18 Codice Catastale del comune
        19 Popolazione legale 2011 (09/10/2011)
        20 NUTS1
        21 NUTS2(3)
        22 NUTS3
        */

        /*
        Fields we're currently interested in (2018):
        ---
        5 Denominazione in italiano
        6 Denominazione in tedesco
        12 Flag Comune capoluogo di provincia
        13 Sigla automobilistica
        18 Codice Catastale del comune
        19 Popolazione legale 2011 (09/10/2011)
        22 Codice NUTS3 2010

        Everything (2018):
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

            $is_province = (bool) $fields[self::ISTAT_COL_IS_PROV_FLAG_INDEX];
            $population = (int) str_replace(',', '', $fields[self::ISTAT_COL_POPULATION_INDEX]);

            if ($is_province) {
                // add province as separate entry
                $location_id = $fields[self::ISTAT_COL_NUTS3_2010_INDEX];
                $istat_data[$location_id] = [
                    'type' => self::LOCATION_TYPE_PROVINCE,

                    'id' => $location_id,
                    'name' => $fields[self::ISTAT_COL_CITY_NAME_IT_INDEX] . ($fields[self::ISTAT_COL_CITY_NAME_ETC_INDEX] ? '/' . $fields[self::ISTAT_COL_CITY_NAME_ETC_INDEX] : ''),
                    'iso_code' => $fields[self::ISTAT_COL_IS_PROV_ISO_CODE],

                    'nuts3_2010_code' => $fields[self::ISTAT_COL_NUTS3_2010_INDEX],

                    'region' => [
                        'id' => $fields[21],
                        'name' => $fields[self::ISTAT_COL_REGION_NAME_INDEX]
                    ]
                ];
            }

            $location_id = $fields[self::ISTAT_COL_NUTS3_2010_INDEX] . $fields[self::ISTAT_COL_CAD_CODE_INDEX];
            $istat_data[$location_id] = [
                'type' => self::LOCATION_TYPE_CITY,

                'id' => $location_id,
                'name' => $fields[self::ISTAT_COL_CITY_NAME_IT_INDEX] . ($fields[self::ISTAT_COL_CITY_NAME_ETC_INDEX] ? '/' . $fields[self::ISTAT_COL_CITY_NAME_ETC_INDEX] : ''),

                'nuts3_2010_code' => $fields[self::ISTAT_COL_NUTS3_2010_INDEX],
                'cad_code' => $fields[self::ISTAT_COL_CAD_CODE_INDEX],

                'population' => $population,

                'region' => [
                    'id' => $fields[21],
                    'name' => $fields[self::ISTAT_COL_REGION_NAME_INDEX]
                ]
            ];
        }

        return $istat_data;
    }

    private static function _getProvinceData($city_data) {
        return !empty(self::$_provincesByNUTSCode[$city_data['nuts3_2010_code']]) ? self::$_provincesByNUTSCode[$city_data['nuts3_2010_code']] : false;
    }

    private static function _getLocationData($location_type, $options=[]) {
        if (empty(self::$_postcodes)) {
            throw new \Exception('postcode index missing');
        }

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
                    if ($city_data['type'] == self::LOCATION_TYPE_PROVINCE) {
                        $data[] = $city_data;
                    }
                }
                break;
            case self::LOCATION_TYPE_CITY:
                foreach ($istat_data as $city_data) {
                    if ($city_data['type'] == self::LOCATION_TYPE_CITY) {
                        if (!empty(self::$_postcodes[$city_data['cad_code']])) {
                            $city_data['postcodes'] = self::$_postcodes[$city_data['cad_code']];
                        } else {
                            $city_data['postcodes'] = [];
                        }
                        $province_data = self::_getProvinceData($city_data);
                        $city_data['province'] = $province_data;
                        $data[] = $city_data;
                    }
                }
                break;
            default:
                throw new \Exception('unknown location type: ' . $location_type);
        }

        // transform in ID-based array
        $data_tmp = [];
        foreach ($data as $k=>$v) {
            if (!$data[$k]['id']) {
                throw new \Exception('location does not have ID');
            }

            $data_tmp[$data[$k]['id']] = $v;
        }
        $data = $data_tmp;

        // filter data
        if (is_array($options) && count($options)) {
            foreach ($data as $location_id => $location) {
                if (isset($options['country']) && $options['country']) {
                    if (substr($location['id'], 0, 2) != $options['country']) { // to figure out country, look at first 2 letter of ID.
                        unset($data[$location_id]);

                        continue;
                    }
                }
                if (isset($options['region']) && $options['region']) {
                    if (substr($location['id'], 0, 4) != substr($options['region'], 0, 4)) {  // to figure out region, look at first 4 letters of ID.
                        unset($data[$location_id]);

                        continue;
                    }
                }
                if (isset($options['province']) && $options['province']) {
                    if (substr($location['id'], 0, 5) != substr($options['province'], 0, 5)) { // to figure out province, look at first 5 letters of ID.
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
        self::_init();

        $country = 'IT';
        $region = !empty($options['region']) ? $options['region'] : '';
        $province = !empty($options['province']) ? $options['province'] : '';

        // in the future, we will return objects instead of arrays (although one will be able to use toArray()).
        // let's force passing output type explicityl to keep compatibility/ease migration in the future
        if ($return_type != self::RETURN_TYPE_ARRAY) {
            throw new \Exception('unsupported output type');
        }

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

    public static function getLocationById($location_id, $location_type='') {
        $locations = self::getLocations($location_type, [], self::RETURN_TYPE_ARRAY);
        foreach ($locations as $location) {
            if ($location['id'] == $location_id) {
                return $location;
            }
        }

        return false;
    }

    public static function getCityByPostcode($postcode, $return_type) {
        throw new \Exception('actually, a single postcode can span multiple cities, this method doesn\'t make sense');
    }

    public static function getCityByCadCode($cad_code, $return_type) {
        self::_init();

        // in the future, we will return objects instead of arrays (although one will be able to use toArray()).
        // let's force passing output type explicityl to keep compatibility/ease migration in the future
        if ($return_type != self::RETURN_TYPE_ARRAY) {
            throw new \Exception('unsupported output type');
        }

        $city = !empty(self::$_citiesByCadCode[$cad_code]) ? self::$_citiesByCadCode[$cad_code] : null;

        return $city;
    }
}