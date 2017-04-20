<?php

namespace LeadThread\Viddler\Api;

/**
 * Viddler PHP Wrapper for Viddler's API
 * Documentation: http://developers.viddler.com
 * Version 4.2 (18Jul2014)
 */
class Viddler
{
    public $apiKey = null;
    public $secure  = false;
    protected $requestClass = Request::class;

    // Constructor
    public function __construct($apiKey = null, $secure = false)
    {
        $this->apiKey = (! empty($apiKey)) ? $apiKey : $this->apiKey;
        $this->secure = (! is_null($secure) && is_bool($secure)) ? $secure : $this->secure;
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
        $request = new $this->requestClass($this->apiKey, $method, $args, $this->secure);
        return $request->execute();
    }
}
