<?php

class Ring {
    private $_apiProto      = 'https://';
    private $_apiHost       = "api.ring.com";
    private $_apiVersion    = 9;

    private $_urlSession    = '/clients_api/session';
    private $_urlDings      = '/clients_api/dings/active';
    private $_urlDevices    = '/clients_api/ring_devices';
    private $_urlHistory    = '/clients_api/doorbots/history';
    private $_urlRecording  = '/clients_api/dings/{id}/recording';

    private $_authToken     = null;

    private function _httpCall($method, $call, $data, $username = null, $password = null) {
        $urlParameters = '';
        $headers = array();
        $headers[] = 'Accept-Encoding: gzip, deflate';
        $headers[] = 'User-Agent: Dalvik/1.6.0 (Linux; U; Android 4.4.4; Build/KTU84Q)';
        
        
        $ch = curl_init();
        if ($method == 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
            $postBody = $this->_arrayToUrlString($data);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
        } else {
            $urlParameters = '?'.$this->_arrayToUrlString($data);
        }
        
        $url = $this->_apiProto.$this->_apiHost.$call.$urlParameters;
        print "Call: ".$url."\n";
        curl_setopt($ch, CURLOPT_URL, $url);

        if (isset($username) || isset($password)) {
            curl_setopt($ch, CURLOPT_USERPWD,  $username.":".$password);
        }
            
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING , "gzip, deflate");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        /*
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        */
        $serverResponse = curl_exec($ch);
        $httpCode       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //var_dump($serverResponse);
        curl_close ($ch);
        
        // Try JSON Decode
        $json = json_decode($serverResponse);
        if ($json) {
            return $json;
        } else {
            return $serverResponse;
        }
    }

    function authenticate($username, $password) {
        $postData['device[os]']                             = 'android';
        $postData['device[hardware_id]']                    = '180940d0-7285-3366-8c64-6ea91491982c';
        $postData['device[app_brand]']                      = 'ring';
        $postData['device[metadata][device_model]']         = 'VirtualBox';
        $postData['device[metadata][resolution]']           = '600x800';
        $postData['device[metadata][app_version]']          = '1.7.29';
        $postData['device[metadata][app_instalation_date]'] = '';
        $postData['device[metadata][os_version]']           = '4.4.4';
        $postData['device[metadata][manufacturer]']         = 'innotek GmbH';
        $postData['device[metadata][is_tablet]']            = 'true';
        $postData['device[metadata][linphone_initialized]'] = 'true';
        $postData['device[metadata][language]']             = 'en';
        $postData['api_version']                            = $this->_apiVersion;

        $headers = array();
        
        $response = $this->_httpCall('POST', $this->_urlSession, $postData, $username, $password);
        
        print "Authenticated as ".$response->profile->first_name.' '.$response->profile->last_name."\n";
        print "Authentication token is ".$response->profile->authentication_token."\n";
        $this->_authToken = $response->profile->authentication_token;
    }

    function poll() {
        $result = array();
        $data = array();
        $data['api_version'] = $this->_apiVersion;
        $data['auth_token']  = $this->_authToken;
        $response = $this->_httpCall('GET', $this->_urlDings, $data);
        foreach($response as $status) {
            foreach($status as $k => $v) {
                $result[$status->id][$k] = $v;
            }
            $result[$status->id]['is_motion']   = false;
            $result[$status->id]['is_ding']     = false;
            if ($status->state == 'ringing') {
                if ($status->kind == 'motion') {
                    $result[$status->id]['is_motion'] = true;
                }
                if ($status->kind =='ding') {
                    $result[$status->id]['is_ding'] = true;
                }
            }
        }
        
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }
    
    function history() {
        $result = array();
        $data = array();
        $data['api_version'] = $this->_apiVersion;
        $data['auth_token']  = $this->_authToken;
        $data['limit']       = 30;
        $response = $this->_httpCall('GET', $this->_urlHistory, $data);
        return $response;
    }

    function recording($id) {
        $result = array();
        $data = array();
        $data['api_version'] = $this->_apiVersion;
        $data['auth_token']  = $this->_authToken;
        $response = $this->_httpCall('GET', $this->_urlTemplate($this->_urlRecording, array('id' => $id)), $data);
        return $response;
    }

    function devices() {
        $result = array();
        $data = array();
        $data['api_version'] = $this->_apiVersion;
        $data['auth_token']  = $this->_authToken;
        $response = $this->_httpCall('GET', $this->_urlDevices, $data);
        return $response;
    }

    private function _arrayToUrlString($array) {
        $string = '';
        foreach($array as $k => $v) {
            $string .= urlencode($k).'='.urlencode($v).'&';
        }
        return substr($string,0, -1);
    }
    
    private function _urlTemplate($url, $data) {
        foreach($data as $k => $v) {
            $url = str_replace('{'.$k.'}', $v, $url);
        }
        return $url;
    }
}
