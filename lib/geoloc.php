<?php
/*
Copyright (c) 2010, Maurice Svay
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are
met:

* Redistributions of source code must retain the above copyright notice,
this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in the
documentation and/or other materials provided with the distribution.
* Neither the name of Maurice Svay nor the names of its
contributors may be used to endorse or promote products derived from
this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER
OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

function geolocate() {
    //Get list of access points
    ob_start();
    passthru("/System/Library/PrivateFrameworks/Apple80211.framework/Versions/Current/Resources/airport -s");
    $c=ob_get_contents();
    ob_end_clean();
    
    if (empty($c)) {
        $geoip = file_get_contents("http://ipinfodb.com/ip_query.php?output=json&timezone=false");
        $res = json_decode($geoip);
        
        $location['latitude'] = $res->Latitude;
        $location['longitude'] = $res->Longitude;
        $location['country'] = $res->CountryName;
        $location['region'] = $res->RegionName;
        $location['city'] = $res->City;
        $location['street'] = false;
        $location['street_number'] = false;
        $location['postal_code'] = false;
        $location['accuracy'] = false;
    } else {
        //Parse APs list
        $ap = array();
        $lines = explode("\n", $c);
        array_shift($lines);
        foreach($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $line = preg_split("/\s+/", $line);
                $ap[] = array(
                    'mac_address' => $line[1],
                    'signal_strength' => (int)$line[2],
                    'ssid' => $line[0]
                );
            }
        }
        $post = array(
            "host" => "Test",
            "radio_type" => "unknown",
            "request_address" => true,
            "version" => "1.1.0",
            "wifi_towers" => $ap
        );
        $json = json_encode($post);
        
        //Ask google where we are
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/x-googlegears'));
        curl_setopt($ch, CURLOPT_URL, 'www.google.com/loc/json');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);
        
        $location['latitude'] = $res->location->latitude;
        $location['longitude'] = $res->location->longitude;
        $location['country'] = $res->location->address->country;
        $location['region'] = $res->location->address->region;
        $location['city'] = $res->location->address->city;
        $location['street'] = $res->location->address->street;
        $location['street_number'] = $res->location->address->street_number;
        $location['postal_code'] = $res->location->address->postal_code;
        $location['accuracy'] = $res->location->accuracy;
    }
    
    return $location;
}