<?php

namespace Zenapply\Viddler\Api\Tests;

use Zenapply\Viddler\Api\Viddler;

class ViddlerTest extends TestCase
{
    public function testItCreatesSuccessfully(){
        $r = new Viddler("token");
        $this->assertInstanceOf(Viddler::class,$r);
    }
}
