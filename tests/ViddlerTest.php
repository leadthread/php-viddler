<?php

namespace Zenapply\Viddler\Api\Tests;

use Zenapply\Viddler\Api\Tests\Mocks\ViddlerMocked;
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
        $v = new ViddlerMocked("token");
        $resp = $v->viddler_users_auth(array('user' => "user", 'password' => "pass"));
        $this->assertInternalType('array', $resp);
    }

    public function testCallPost()
    {
        $v = new ViddlerMocked("token");
        $resp = $v->viddler_encoding_cancel(array('user' => "user", 'password' => "pass"));
        $this->assertInternalType('array', $resp);
    }

    public function testCallBinary()
    {
        $v = new ViddlerMocked("token");
        $resp = $v->viddler_videos_setThumbnail(array('user' => "user", 'password' => "pass"));
        $this->assertInternalType('array', $resp);
    }
}
