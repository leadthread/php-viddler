<?php

namespace Zenapply\Viddler\Api\Tests\Mocks;

class ViddlerMocked extends \Zenapply\Viddler\Api\Viddler
{
    protected function execute($method, $args, $url)
    {
        return [];
    }
}
