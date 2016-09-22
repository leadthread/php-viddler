<?php

namespace Zenapply\Viddler\Api;

class Request
{
    protected $method;
    protected $apiKey;
    protected $options;
    protected $secure;

    /**
     * Methods that require Binary transfer
     */
    protected $binaryMethods = [
        'viddler.videos.setThumbnail'
    ];

    /**
     * Methods that require HTTPS
     */
    protected $secureMethods = [
        'viddler.users.auth',
        'viddler.users.register'
    ];

    /**
     * Methods that require POST
     */
    protected $postMethods = [
        'viddler.encoding.cancel',
        'viddler.encoding.encode',
        'viddler.encoding.setOptions',
        'viddler.encoding.setSettings',
        'viddler.logins.add',
        'viddler.logins.delete',
        'viddler.logins.update',
        'viddler.playlists.addVideo',
        'viddler.playlists.create',
        'viddler.playlists.delete',
        'viddler.playlists.moveVideo',
        'viddler.playlists.removeVideo',
        'viddler.playslists.setDetails',
        'viddler.resellers.removeSubaccount',
        'viddler.users.register',
        'viddler.users.setSettings',
        'viddler.users.setProfile',
        'viddler.users.setOptions',
        'viddler.users.setPlayerBranding',
        'viddler.videos.addClosedCaptioning',
        'viddler.videos.comments.add',
        'viddler.videos.comments.remove',
        'viddler.videos.delClosedCaptioning',
        'viddler.videos.delete',
        'viddler.videos.delFile',
        'viddler.videos.disableAds',
        'viddler.videos.enableAds',
        'viddler.videos.favorite',
        'viddler.videos.setClosedCaptioning',
        'viddler.videos.setDetails',
        'viddler.videos.setPermalink',
        'viddler.videos.setThumbnail',
    ];

    /**
     * A Mapping of what Exception to throw when Viddler returns a certain error code
     */
    protected $exceptions = [
        "203"     => Exceptions\ViddlerUploadingDisabledException::class,
        "202"     => Exceptions\ViddlerInvalidFormTypeException::class,
        "200"     => Exceptions\ViddlerSizeLimitExceededException::class,
        "105"     => Exceptions\ViddlerUsernameExistsException::class,
        "104"     => Exceptions\ViddlerTermsNotAcceptedException::class,
        "103"     => Exceptions\ViddlerInvalidPasswordException::class,
        "102"     => Exceptions\ViddlerAccountSuspendedException::class,
        "101"     => Exceptions\ViddlerForbiddenException::class,
        "100"     => Exceptions\ViddlerNotFoundException::class,
        "8"       => Exceptions\ViddlerInvalidApiKeyException::class,
        "default" => Exceptions\ViddlerException::class
    ];

    public function __construct($apiKey, $method, $options, $secure = false)
    {
        $this->method  = $method;
        $this->apiKey  = $apiKey;
        $this->secure  = $secure;
        $this->options = $options;
    }

    /**
     * Constructs the Request requirements and then sends the Request returning the response
     */
    public function execute()
    {
        // Get the parameters
        $params = $this->getParams();

        // Get the url
        $url = $this->getUrl($params);

        // Run it
        return $this->sendRequest($url, $params);
    }

    /**
     * Sends the actual curl request
     */
    protected function sendRequest($url, $params)
    {
        // Construct the cURL call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Figure POST vs. GET
        if ($this->isPost()) {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($this->isBinary()) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getBinaryArgs());
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        //Get the response
        $response = curl_exec($ch);

        if (!$response) {
            throw new Exceptions\ViddlerException(curl_error($ch));
        } else {
            $response = unserialize($response);
        }

        curl_close($ch);

        $response = $this->checkResponseForErrors($response);

        return $response;
    }

    /**
     * Builds the query object from the options property
     */
    protected function getParams()
    {
        $params = ["key=".$this->apiKey];
        if (@count($this->options[0]) > 0 && is_array($this->options[0])) {
            foreach ($this->options[0] as $k => $v) {
                if ($k != "response_type" && $k != "apiKey") {
                    array_push($params, "$k=$v");
                }
            }
        }
        return $params;
    }

    /**
     * Builds the URL for the request
     */
    protected function getUrl($params)
    {
        // Figure protocol http:// or https://
        $protocol = $this->getProtocol();

        // The base
        $url = $protocol . "://api.viddler.com/api/v2/" . $this->method . ".php";

        // Add on the params
        if (!$this->isPost() && @count($params) > 0 && is_array($params)) {
            $url .= "?" . implode("&", $params);
        }

        return $url;
    }

    /**
     * Returns the binary arguments for the request
     */
    protected function getBinaryArgs()
    {
        $bArgs = array();
        foreach ($this->options[0] as $k => $v) {
            if ($k != 'file') {
                $bArgs[$k] = $v;
            }
        }

        if (!isset($bArgs['key'])) {
            $bArgs['key'] = $this->apiKey;
        }
            // Update for PHP 5.5.0 and above to use new CURLFile class
        if (version_compare(PHP_VERSION, '5.5.0', '>=') === true) {
            $bArgs['file'] = curl_file_create($this->options[0]['file']);
        } else {
            $bArgs['file'] = '@' . $this->options[0]['file'];
        }

        return $bArgs;
    }

    /**
     * Throws an Exception if the response contains an error
     */
    protected function checkResponseForErrors($response)
    {
        if (isset($response["error"])) {
            $msg = [];
            $parts = ["code", "description", "details"];
            
            foreach ($parts as $part) {
                if (!empty($response["error"][$part])) {
                    $msg[] = $part.": ".$response["error"][$part];
                }
            }

            $msg = implode(" | ", $msg);
            $code = $response["error"]["code"];

            if (!array_key_exists($code, $this->exceptions)) {
                $code = "default";
            }

            throw new $this->exceptions[$code]($msg);
        }

        return $response;
    }

    /**
     * Checks if the method should be run as a POST
     */
    protected function isPost()
    {
        return (in_array($this->method, $this->postMethods)) ? true : false;
    }

    /**
     * Checks if the method is a binary method
     */
    protected function isBinary()
    {
        return (in_array($this->method, $this->binaryMethods)) ? true : false;
    }

    /**
     * Returns the correct protocol for the provided method
     */
    protected function getProtocol()
    {
        return (in_array($this->method, $this->secureMethods) || $this->secure === true) ? "https" : "http";
    }
}
