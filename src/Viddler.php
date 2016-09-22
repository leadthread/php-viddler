<?php

namespace Zenapply\Viddler\Api;

/**
 * Viddler PHP Wrapper for Viddler's API
 * Documentation: http://developers.viddler.com
 * Version 4.2 (18Jul2014)
 */
class Viddler
{
    public $apiKey = null;
    public $secure  = false;

    // Methods that require Binary transfer
    protected $binaryMethods = [
        'viddler.videos.setThumbnail'
    ];

    // Methods that require HTTPS
    protected $secureMethods = [
        'viddler.users.auth',
        'viddler.users.register'
    ];
    
    // Methods that require POST
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

    // Constructor
    public function __construct($apiKey = null, $secure = false)
    {
        $this->apiKey  = (! empty($apiKey)) ? $apiKey : $this->apiKey;
        $this->secure   = (! is_null($secure) && is_bool($secure)) ? $secure : $this->secure;
    }


    /**
     * Can be called like such:
     * $__api = new Viddler_API("YOUR KEY");
     * $array = $__api->viddler_users_getProfile(array("user"=>"phpfunk"));
     */
    public function __call($method, $args)
    {
        return self::call($method, $args);
    }

    /**
     * Format the Method
     * Accepted Formats:
     *
     * $viddler->viddler_users_auth();
     */
    protected function call($method, $args)
    {
        $method = str_replace("_", ".", $method);

        // Get the url
        $url = $this->buildUrl($method, $args);

        // Execute it
        return $this->execute($method, $args, $url);
    }

    /**
     * Returns the correct protocol for the provided method
     */
    protected function getProtocol($method)
    {
        return (in_array($method, $this->secureMethods) || $this->secure === true) ? "https" : "http";
    }

    protected function buildUrl($method, $args)
    {
        // Used to construct the querystring.
        $query = array();

        // Figure protocol http:// or https://
        $protocol = $this->getProtocol($method);

        // Build API endpoint URL
        // This is generally used to switch the end-point for uploads. See /examples/uploadExample.php in PHPViddler 2
        if (isset($args[1])) {
            $url = $args[1];
        } else {
            $url = $protocol . "://api.viddler.com/api/v2/" . $method . ".php";
        }

        if ($this->isPost($method)) { // Is a post method
            array_push($query, "key=" . $this->apiKey); // Adds API key to the POST arguments array
        } else {
            $url .= "?key=" . $this->apiKey;
        }

        //Figure the query string
        if (@count($args[0]) > 0 && is_array($args[0])) {
            foreach ($args[0] as $k => $v) {
                if ($k != "response_type" && $k != "apiKey") {
                    array_push($query, "$k=$v");
                }
            }
            $query = implode("&", $query);
            if (!$this->isPost($method)) {
                $url .= (!empty($query)) ? "&" . $query : "";
            }
        } else {
            $query = null;
            $args[0] = array();
        }

        return $url;
    }

    protected function isPost($method)
    {
        return (in_array($method, $this->postMethods)) ? true : false;
    }

    protected function isBinary($method)
    {
        return (in_array($method, $this->binaryMethods)) ? true : false;
    }

    /**
     * Executes the request
     */
    protected function execute($method, $args, $url)
    {
        // Construct the cURL call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Figure POST vs. GET
        if ($this->isPost($method)) {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($this->isBinary($method)) {
                $binary_args = array();
                foreach ($args[0] as $k => $v) {
                    if ($k != 'file') {
                        $binary_args[$k] = $v;
                    }
                }

                if (!isset($binary_args['key'])) {
                    $binary_args['key'] = $this->apiKey;
                }
                    // Update for PHP 5.5.0 and above to use new CURLFile class
                if (version_compare(PHP_VERSION, '5.5.0', '>=') == true) {
                    $binary_args['file'] = curl_file_create($args[0]['file']);
                } else {
                    $binary_args['file'] = '@' . $args[0]['file'];
                }

                curl_setopt($ch, CURLOPT_POSTFIELDS, $binary_args);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
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

            switch ($response["error"]["code"]) {
                case "203":
                    throw new Exceptions\ViddlerUploadingDisabledException($msg);
                case "202":
                    throw new Exceptions\ViddlerInvalidFormTypeException($msg);
                case "200":
                    throw new Exceptions\ViddlerSizeLimitExceededException($msg);
                case "105":
                    throw new Exceptions\ViddlerUsernameExistsException($msg);
                case "104":
                    throw new Exceptions\ViddlerTermsNotAcceptedException($msg);
                case "103":
                    throw new Exceptions\ViddlerInvalidPasswordException($msg);
                case "102":
                    throw new Exceptions\ViddlerAccountSuspendedException($msg);
                case "101":
                    throw new Exceptions\ViddlerForbiddenException($msg);
                case "100":
                    throw new Exceptions\ViddlerNotFoundException($msg);
                case "8":
                    throw new Exceptions\ViddlerInvalidApiKeyException($msg);
                default:
                    throw new Exceptions\ViddlerException($msg);
            }
        }

        return $response;
    }
}
