<?php

namespace WC_Lending_Works\Tests\Order;

use PHPUnit\Framework\TestCase;
use WC_Lending_Works\Lib\Http;
use WC_Lending_Works\Lib\Order\Order_Repository;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter;
use WC_Order;
use WP_Error;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class Order_Repository_Test extends TestCase
{
    /**
     * @var Request $request
     */
    private $request;

    public function setUp()
    {
        $this->fake_woocommerce = $this->createMock(Woocommerce_Adapter::class);
        $this->create_request = $this->createMock(Http\Create\Request::class);
        $this->fulfill_request = $this->createMock(Http\Fulfill\Request::class);

        $this->create_request->method('get_url')
            ->willReturn('http://example.com/orders');
        $this->create_request->method('get_headers')
            ->willReturn([
                'foo' => 'bar'
            ]);
        $this->create_request->method('get_body')
            ->willReturn(['baz' => 'qux']);

        $this->fulfill_request->method('get_url')
            ->willReturn('http://example.com/orders/loan-request/fulfill');
        $this->fulfill_request->method('get_headers')
            ->willReturn([
                'foo' => 'bar'
            ]);
        $this->fulfill_request->method('get_body')
            ->willReturn(['baz' => 'qux']);
    }

    public function test_can_instanciate()
    {
        $repository = new Order_Repository($this->fake_woocommerce, $this->create_request, $this->fulfill_request);

        $this->assertInstanceOf(Order_Repository::class, $repository);
    }

    public function test_can_create_order()
    {
        $order = $this->createMock(WC_Order::class);

        $this->fake_woocommerce->expects($this->once())
            ->method('post')
            ->with('http://example.com/orders', [
                'headers' => ['foo' => 'bar'],
                'body' => ['baz' => 'qux']
            ])
            ->willReturn([
                'body' => '{ "token": "foobar"}',
            ]);

        $repository = new Order_Repository($this->fake_woocommerce, $this->create_request, $this->fulfill_request);

        $response = $repository->create($order);

        $this->assertInstanceOf(Http\Create\Response::class, $response);
        $this->assertEquals($response->get_order_token(), 'foobar');
    }

    public function test_cannot_create_order()
    {
        $order = $this->createMock(WC_Order::class);

        $this->fake_woocommerce->expects($this->once())
            ->method('post')
            ->with('http://example.com/orders', [
                'headers' => ['foo' => 'bar'],
                'body' => ['baz' => 'qux']
            ])
            ->willReturn(new WP_Error());

        $repository = new Order_Repository($this->fake_woocommerce, $this->create_request, $this->fulfill_request);

        $response = $repository->create($order);

        $this->assertInstanceOf(Http\Create\Response::class, $response);
        $this->assertTrue($response->is_error());
    }

    public function test_can_fulfill_order()
    {
        $order = $this->createMock(WC_Order::class);

        $this->fake_woocommerce->expects($this->once())
            ->method('post')
            ->with('http://example.com/orders/loan-request/fulfill', [
                'headers' => ['foo' => 'bar'],
                'body' => ['baz' => 'qux'],
            ])
            ->willReturn([
                'body' => '{}',
                'response' => ['code' => 200, 'message' => 'OK']
            ]);

        $repository = new Order_Repository($this->fake_woocommerce, $this->create_request, $this->fulfill_request);

        $response = $repository->fulfill($order);

        $this->assertInstanceOf(Http\Fulfill\Response::class, $response);
        $this->assertFalse($response->is_error());
    }

    public function test_cannot_fulfill_order()
    {
        $order = $this->createMock(WC_Order::class);

        $this->fake_woocommerce->expects($this->once())
            ->method('post')
            ->with('http://example.com/orders/loan-request/fulfill', [
                'headers' => ['foo' => 'bar'],
                'body' => ['baz' => 'qux'],
            ])
            ->willReturn(new WP_Error());

        $repository = new Order_Repository($this->fake_woocommerce, $this->create_request, $this->fulfill_request);

        $response = $repository->fulfill($order);

        $this->assertInstanceOf(Http\Fulfill\Response::class, $response);
        $this->assertTrue($response->is_error());
    }
}
