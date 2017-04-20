<?php

namespace LeadThread\Viddler\Api\Tests\Mocks;

class ViddlerMocked extends \LeadThread\Viddler\Api\Viddler
{
    protected $requestClass = RequestMocked::class;
}
