<?php

namespace Tranquility;

use Tranquility\Enum\System\ExternalServiceResponseType as EnumServiceResponseType;

class Geolocation {
    
    const GEOLOCATION_URI = 'http://maps.googleapis.com/maps/api/geocode/';
    
    /**
     * Performs geolocation of an address. If the address is provided as an
     * array, it will be converted in sequence into a string prior to 
     * performing geolocation
     * 
     * @param  mixed $address Array or string containing address details
     * @return array
     */
    public static function performGeolocation($address) {
        // If address is provided as an array, convert into a string
        if (is_array($address)) {
            $address = Geolocation::_joinAddressParts($address);
        }
        $address = urlencode($address);
        
                // Setup full URL to use for geolocation service
        $uri = Geolocation::GEOLOCATION_URI."json?sensor=false&address=".utf8_encode($address);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // Execute call
        $data = curl_exec($ch);
        if ($data == false) {
            // Log error and return false response
            $response = array(
                'status' => EnumServiceResponseType::UnexpectedError,
                'latitude' => 0,
                'longitude' => 0
            );
            return $response;
        }
        
        // Successful call
        curl_close($ch);
        $geolocationData = json_decode($data, true);
        
        // Check if the response is in a recognised format
        if (!is_array($geolocationData) || !isset($geolocationData['status'])) {
            $response = array(
                'status' => EnumServiceResponseType::UnexpectedError,
                'latitude' => 0,
                'longitude' => 0
            );
            return false;
        }
        
        // Check the result code
        if ($geolocationData['status'] != 'OK') {
            switch ($geolocationData['status']) {
                case 'ZERO_RESULTS':
                    // Successful service call, but no results
                    $response = array(
                        'status' => EnumServiceResponseType::Success,
                        'latitude' => 0,
                        'longitude' => 0
                    );
                case 'OVER_QUERY_LIMIT':
                    $response = array(
                        'status' => EnumServiceResponseType::ExceededApiLimit,
                        'latitude' => 0,
                        'longitude' => 0
                    );
                case 'REQUEST_DENIED':
                case 'INVALID_REQUEST':
                default:
                    $response = array(
                        'status' => EnumServiceResponseType::InvalidRequest,
                        'latitude' => 0,
                        'longitude' => 0
                    );
            }
            return $response;
        }
        
        // We were successful, so return an array with latitude and longitude
        $coordinates = array(
            'status' => EnumServiceResponseType::Success,
            'latitude' => $geolocationData['results'][0]['geometry']['location']['lat'],
            'longitude' => $geolocationData['results'][0]['geometry']['location']['lng']
        );
        return $coordinates;
    }
    
    private function _joinAddressParts($address, $separator = ",") {
        $addressParts = array();
        
        $addressLine1 = Utility::extractValue($address, 'addressLine1', '');
        if ($addressLine1 !== '') {
            $addressParts[] = $addressLine1;
        }
        
        $addressLine2 = Utility::extractValue($address, 'addressLine2', '');
        if ($addressLine2 !== '') {
            $addressParts[] = $addressLine2;
        }
        
        $city     = Utility::extractValue($address, 'city', '');
        $state    = Utility::extractValue($address, 'state', '');
        $postcode = Utility::extractValue($address, 'postcode', '');
        $address_line_3 = trim($city.' '.$state.' '.$postcode);
        if ($address_line_3 != '') {
            $addressParts[] = $address_line_3;
        }
        
        $country = Utility::extractValue($address, 'country', '');
        if ($country != '') {
            $addressParts[] = $country;
        }
        
        // Glue address parts together and return
        return implode($separator, $addressParts);
    }
}
