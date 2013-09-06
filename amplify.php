<?php

/**
 * Amplify is a  customer relationship management  for web app owners
 * 
 * This library provides connectivity with the Amplify API 
 * 
 * Basic usage:
 * 
 * 1. Configure Amplify with your access credentials
 * <code>
 * <?php
 * 
 * $amplify = new Amplify('dummy_app_key','dummy_app_secret','dummy_project_id');
 * ?>
 * </code>
 * 
 * 2. Make requests to the API
 * <code>
 * <?php
 * $amplify = new Amplify('dummy_app_key','dummy_app_secret','dummy_project_id');
 * $users = $intercom->getAllUsers();
 * var_dump($users);
 * ?>
 * </code>
 * 
 * @author    Sandeep Kaushal Verma <sandeep@socialaxishq.com>
 * @copyright Copyright 2013 Betaout Pvt Ltd All rights reserved.
 * @link      http://www.betaout.com/
 * @license   http://opensource.org/licenses/MIT
 * */
// Check for the required json and curl extensions, the Google API PHP Client won't function without them.
if (!function_exists('curl_init')) {
    throw new Exception('Amplify PHP SDK  requires the CURL PHP extension');
}

if (!function_exists('json_decode')) {
    throw new Exception('Amplify PHP SDK requires the JSON PHP extension');
}

if (!function_exists('http_build_query')) {
    throw new Exception('Amplify PHP SDK requires http_build_query()');
}

/**
 * amplify.to API 
 */
class Amplify {
    /*
     * the amplify ApiKey 
     */

    protected $apiKey;

    /*
     * the amplify ApiSecret 
     */
    protected $apiSecret;

    /*
     * the amplify  ProjectId
     */
    protected $projectId;

    /*
     * the amplify requesturl
     *  
     */
    protected $requestUrl;

    /**
     * amplify host
     */
    private $host = 'amplify.to';

    /**
     * amplify version
     */
    private $version = 'v1';

    /*
     * param to be send on amplify 
     */
    protected $params;

    /*
     *  Computes a Hash-based Message Authentication Code (HMAC) using the SHA1 hash function.
     */
    protected $signatureMethod = 'HMAC-SHA1';

    /*
     * signature based string  
     */
    protected $hash;
    /*
     * current time stamp used to create hash 
     */
    protected $timeStamp;
    /*
     * ott refer one time token that use to handshake  
     */
    protected $ott;

    /**
     * Whether we are in debug mode. This is set by the constructor
     */
    private $debug = false;

    /**
     * function end point mapping
     */
    protected $functionUrlMap = array(
        'identify' => 'user/identify/',
        'event' => 'user/event/',
        'update' => 'user/update/',
        'add' => 'user/add/'
    );

   

    /**
     * The constructor
     *
     * @param  string $apiKey  The Amplify application Key
     * @param  string $apiSecret The Amplify application Secret
     * @param  string $projectId The Amplify ProjectId
     * @param  string $debug  Optional debug flag
     * @return void
     * */
    public function __construct($apiKey, $apiSecret, $debug = false) {
        $this->setApiKey($apiKey);
        $this->setApiSecret($apiSecret);
        $this->setTimeStamp(time());
        $this->setOtt();
        $this->debug = $debug;
    }

    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'amplify-php-1.0',
    );

    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    public function setProjectId($projectId) {
        $this->projectId = $projectId;
        return $this;
    }

    public function getProjectId() {
        return $this->projectId;
    }

    public function setPublicationUrl() {
        $this->publicationUrl = "http://" . $this->getProjectId() . "." . $this->host . "/" . $this->version;
    }

    public function getPublicationUrl() {
        return $this->publicationUrl;
    }

    public function setHash($hash) {
        $this->hash = $hash;
        return $this;
    }

    public function getHash() {
        return $this->hash;
    }

    public function setParams($params) {
        $this->params = $params;
        return $this;
    }

    public function getParams() {
        return $this->params;
    }

    public function setApiSecret($apiSecret) {
        $this->apiSecret = $apiSecret;
        return $this;
    }

    public function getApiSecret() {
        return $this->apiSecret;
    }

    public function getRequestUrl() {
        return $this->requestUrl;
    }

    public function setRequestUrl($requestUrl) {
        $this->requestUrl = $requestUrl;
        return $this;
    }

    public function setTimeStamp($timeStamp) {
        $this->timeStamp = $timeStamp;
    }

    public function getTimeStamp() {
        $timeStamp = $this->timeStamp;
        if (empty($timeStamp))
            $this->setTimeStamp(time());
        return $this->timeStamp;
    }

    public function setOtt() {
        if (isset($_COOKIE['amplifysessionid']) && !empty($_COOKIE['amplifysessionid'])) {
            $this->ott = $_COOKIE['amplifysessionid'];
        }
    }

    public function getOtt() {

        return $this->ott;
    }

    public function makeParams($params = false) {

        if (!is_array($params) && !empty($params))
            throw new Exception("paramter should be associative array!");

        if (isset($this->ott)) {
            $params['amplifysessionid'] = $this->getOtt();
        }
        try {
            if (!isset($params['apiKey']))
                $params['apiKey'] = $this->getApiKey();
            if (!isset($params['timestamp']))
                $params['timestamp'] = $this->getTimeStamp();
            ksort($params);
            $paramUrl = http_build_query($params, null, "&");
            $this->setParams($paramUrl);
        } catch (Exception $ex) {
            throw new Exception($ex->getCode() . ":" . $ex->getMessage());
        }
    }

    function http_call($functionName, $argumentsArray) {
        $apiKey = $this->getApiKey();
        $apiSecret = $this->getApiSecret();
        if (empty($apiKey))
            throw new Exception("Invalid Api call, Api key must be provided!");
        if (empty($apiSecret))
            throw new Exception("Invalid Api call, Api Secret must be provided!");
        if (!isset($this->functionUrlMap[$functionName]))
            throw new Exception("Invalid Function call!");
        try {
            $requestUrl = $this->getPublicationUrl() . $this->functionUrlMap[$functionName]; //there should be error handling to make sure function name exist
            if (isset($argumentsArray[0]) && is_array($argumentsArray[0]) && count($argumentsArray[0]) > 0)
                $this->makeParams($argumentsArray[0]);
            else
                $this->makeParams();
            $requestUrl.="?" . $this->getParams();
            $this->setRequestUrl($requestUrl);
            $this->signString();
            $requestUrl = $this->getRequestUrl() . "&hash=" . $this->getHash();
            return $this->makeRequest($requestUrl);
        } catch (Exception $ex) {
            throw new Exception($ex->getCode() . ":" . $ex->getMessage());
        }
    }

    protected function signString() {
        switch ($this->signatureMethod) {
            case 'HMAC-SHA1':
                $key = $this->encode_rfc3986($this->apiSecret);
                $params = $this->getParams();
                $hash = urlencode(base64_encode(hash_hmac('sha1', $params, $key, true)));
                $this->setHash($hash);
                break;
            default :
                throw new Exception("Signature method is not valid");
                break;
        }
    }

    protected function encode_rfc3986($string) {
        return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode(($string))));
    }

    protected function makeRequest($requestUrl, $ch = null) {
        if (!$ch) {
            $ch = curl_init();
        }
        $options = self::$CURL_OPTS;
        $options[CURLOPT_URL] = $requestUrl;

        if ($this->debug) {
            $options[CURLOPT_URL] = true;
        }
// disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
// for 2 seconds if the server does not support this header.
        if (isset($options[CURLOPT_HTTPHEADER])) {
            $existing_headers = $options[CURLOPT_HTTPHEADER];
            $existing_headers[] = 'Expect:';
            $options[CURLOPT_HTTPHEADER] = $existing_headers;
        } else {
            $options[CURLOPT_HTTPHEADER] = array('Expect:');
        }

        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        if ($result === false) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    /*
     * Identify system user if it unknowm leave parameter blank
     * amplify->identify(`sandeep@socialaxishq.com`,‘Sandeep’);
     * Replace with name and email of current user
     */

    public function identify($email = '', $name = '') {

        $argumentsArray = array('email' => $email, 'name' => $name);
        $response = $this->http_call('identify', $argumentsArray);
        if ($response->responseCode == '200') {
            setcookie($_SERVER['SERVER_NAME'], $response->amplifySession, time() + 604800, "/", $_SERVER['SERVER_NAME']);
        }
    }

    /*
     * add new event with properties    
     * amplify->event(`sandeep@socialaxishq.com`,array('addtocart'=>array('product'=>’Samsung Note2’,'category'=>’Mobile’,'price'=>’456.78’)));
     */

    public function event($email, $eventArray) {
        $argumentsArray = array('email' => $email, 'event' => $eventArray);
        $this->http_call('event', $argumentsArray);
    }

    /*
     * add user  properties    
     * amplify->update(`sandeep@socialaxishq.com`,array('country'=>’India’,'city'=>’Noida’));
     */

    public function update($email, $propetyArray) {
        $argumentsArray = array('email' => $email, 'properties' => $propetyArray);
        $this->http_call('update', $argumentsArray);
    }

    /*
     * add new user  properties    
     * amplify->add(`sandeep@socialaxishq.com`,array('total_comments'=>’5’,'total_shares'=>’4’));
     */

    public function add($email, $propetyArray) {
        $argumentsArray = array('email' => $email, 'properties' => $propetyArray);
        $this->http_call('add', $argumentsArray);
    }

   

}

?>