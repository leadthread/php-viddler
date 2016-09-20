<?php

namespace Zenapply\Viddler\Api\Tests;

use Zenapply\Viddler\Api\Viddler;
use Zenapply\Viddler\Api\Exceptions\ViddlerInvalidApiKeyException;

class ViddlerTest extends TestCase
{
    public function testItCreatesSuccessfully()
    {
        $r = new Viddler("token");
        $this->assertInstanceOf(Viddler::class, $r);
    }

    public function testCall()
    {
        $this->setExpectedException(ViddlerInvalidApiKeyException::class);
        $v = new Viddler("token");
        $resp = $v->viddler_users_auth(array('user' => "user", 'password' => "pass"));
    }
}
