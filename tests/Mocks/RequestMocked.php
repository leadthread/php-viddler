<?php

namespace LeadThread\Viddler\Api\Tests\Mocks;

class RequestMocked extends \LeadThread\Viddler\Api\Request
{
    protected function sendRequest($url, $params)
    {
        return [];
    }

    public function checkResponseForErrors($response)
    {
        return parent::checkResponseForErrors($response);
    }

    public function getBinaryArgs()
    {
        return parent::getBinaryArgs();
    }
}
