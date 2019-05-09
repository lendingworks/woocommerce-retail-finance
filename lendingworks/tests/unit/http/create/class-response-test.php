<?php

namespace WC_Lending_Works\Tests\Http\Create;

use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use WC_Lending_Works\Lib\Http\Create\Response;
use \WP_Error;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ResponseTest extends TestCase
{
    public function testCanBeInstanciated()
    {
        $response = new Response([]);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testIsErrorWhenResultIsWPErrorClass()
    {
        $response = new Response(new WP_Error());

        $this->assertTrue($response->is_error());
    }

    public function testIsErrorWhenResultIsSomicResponseWithErrorInBody()
    {
        $response = new Response([
            'headers' => ['foo' => 'bar'],
            'response' => [
                'code' => 400,
                'message' => 'Bad Request',
            ],
        ]);

        $this->assertTrue($response->is_error());
    }

    public function testCanGetErrorFromWPErrorClass()
    {
        $error = $this->createMock(WP_Error::class);
        $error->method('get_error_message')
            ->willReturn('Bad Request');

        $response = new Response($error);

        $this->assertEquals($response->get_error_message(), 'Bad Request');
    }

    public function testCanGetErrorMessageFromSomicResponse()
    {
        $response = new Response([
            'headers' => ['foo' => 'bar'],
            'response' => [
                'code' => 400,
                'message' => 'Bad Request',
            ],
            'body' => '{}',
        ]);

        $this->assertEquals($response->get_error_message(), 'Bad Request');
    }

    public function testCanGetErrorMessageFromSomicBody()
    {
        $response = new Response([
            'headers' => ['foo' => 'bar'],
            'response' => [
                'code' => 400,
                'message' => 'Bad Request',
            ],
            'body' => '{ "statusCode": 400, "message": "Bad Request Json"}',
        ]);

        $this->assertEquals($response->get_error_message(), 'Bad Request Json');
    }

    public function testCanGetTokenFromJsonResponseBody()
    {
        $response = new Response([
            'headers' => ['foo' => 'bar'],
            'response' => [
                'code' => 200,
                'message' => 'OK',
            ],
            'body' => '{"token":"foobar.bazqux"}'
        ]);

        $this->assertEquals($response->get_order_token(), 'foobar.bazqux');
    }

    public function testCanThrowAnExceptionWhenReturnedJsonIsInvalid()
    {
        $response = new Response([
            'headers' => ['foo' => 'bar'],
            'response' => [
                'code' => 200,
                'message' => 'OK',
            ],
            'body' => 'invalid json'
        ]);

        $this->expectException(UnexpectedValueException::class);

        $response->get_order_token();
    }
}
