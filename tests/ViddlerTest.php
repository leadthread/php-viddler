<?php

namespace LeadThread\Viddler\Api\Tests;

use LeadThread\Viddler\Api\Tests\Mocks\ViddlerMocked;
use LeadThread\Viddler\Api\Tests\Mocks\RequestMocked;
use LeadThread\Viddler\Api\Viddler;
use LeadThread\Viddler\Api\Exceptions\ViddlerInvalidApiKeyException;

class ViddlerTest extends TestCase
{
    public function testItCreatesSuccessfully()
    {
        $r = new Viddler("apiKey");
        $this->assertInstanceOf(Viddler::class, $r);
    }

    public function testCall()
    {
        $v = new ViddlerMocked("apiKey");
        $resp = $v->viddler_users_auth(array('user' => "user", 'password' => "pass"));
        $this->assertInternalType('array', $resp);
    }

    public function testCallPost()
    {
        $v = new ViddlerMocked("apiKey");
        $resp = $v->viddler_encoding_cancel(array('user' => "user", 'password' => "pass"));
        $this->assertInternalType('array', $resp);
    }

    public function testBinaryArgs()
    {
        $v = new RequestMocked("apiKey", "viddler.videos.setThumbnail", [[
            'file' => 'file',
            'random' => 'value'
        ]]);
        $args = $v->getBinaryArgs();
        $this->assertInternalType('array', $args);
    }

    public function testValidResponse()
    {
        $valid = [
            "viddler_api" => [
                "version" => "3.9.0"
            ]
        ];
        $v = new RequestMocked("apiKey", "method", []);
        $response = $v->checkResponseForErrors($valid);

        $this->assertEquals($valid, $response);
    }

    public function testExceptions()
    {
        $exceptions = [
            "203"     => \LeadThread\Viddler\Api\Exceptions\ViddlerUploadingDisabledException::class,
            "202"     => \LeadThread\Viddler\Api\Exceptions\ViddlerInvalidFormTypeException::class,
            "200"     => \LeadThread\Viddler\Api\Exceptions\ViddlerSizeLimitExceededException::class,
            "105"     => \LeadThread\Viddler\Api\Exceptions\ViddlerUsernameExistsException::class,
            "104"     => \LeadThread\Viddler\Api\Exceptions\ViddlerTermsNotAcceptedException::class,
            "103"     => \LeadThread\Viddler\Api\Exceptions\ViddlerInvalidPasswordException::class,
            "102"     => \LeadThread\Viddler\Api\Exceptions\ViddlerAccountSuspendedException::class,
            "101"     => \LeadThread\Viddler\Api\Exceptions\ViddlerForbiddenException::class,
            "100"     => \LeadThread\Viddler\Api\Exceptions\ViddlerNotFoundException::class,
            "8"       => \LeadThread\Viddler\Api\Exceptions\ViddlerInvalidApiKeyException::class,
            "default" => \LeadThread\Viddler\Api\Exceptions\ViddlerException::class,
            "random"  => \LeadThread\Viddler\Api\Exceptions\ViddlerException::class
        ];

        $v = new RequestMocked("apiKey", "method", []);
        foreach ($exceptions as $code => $exception) {
            try {
                $v->checkResponseForErrors([
                    "error" => ["code" => $code]
                ]);
                $this->fail('No exception thrown');
            } catch (\Exception $e) {
                $this->assertInstanceOf($exception, $e);
            }
        }
    }
}
