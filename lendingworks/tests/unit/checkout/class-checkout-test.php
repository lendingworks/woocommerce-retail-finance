<?php

namespace WC_Lending_Works\Tests\Checkout;

use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use WC_Lending_Works\Lib\Checkout\Checkout;
use WC_Lending_Works\Lib\Http\Create\Response;
use WC_Lending_Works\Lib\Order\Order_Repository;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter;
use WC_Lending_Works\Lib\Payment_Gateway;
use WC_Order;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class Checkout_Test extends TestCase
{
    private $woocommerce_stub;
    private $order_repository_stub;
    private $checkout;
    private $order;
    private $response;

    public function setUp()
    {
        $this->woocommerce_stub = $this->createMock(Woocommerce_Adapter::class);
        $this->order_repository_stub = $this->createMock(Order_Repository::class);
        $this->checkout = new Checkout($this->woocommerce_stub, $this->order_repository_stub);

        $this->order = $this->createMock(WC_Order::class);
        $this->response = $this->createMock(Response::class);

        $this->woocommerce_stub->method('get_order')->willReturn($this->order);
        $this->order_repository_stub->method('create')->willReturn($this->response);
    }

    public function test_can_instanciate()
    {
        $this->assertInstanceOf(Checkout::class, $this->checkout);
    }

    public function test_can_checkout_an_order()
    {
        $this->response->method('is_error')->willReturn(false);
        $this->response->method('get_order_token')->willReturn('foobar');

        $this->woocommerce_stub->expects($this->once())
            ->method('update_order_meta')
            ->with($this->order, Payment_Gateway::ORDER_TOKEN_METADATA_KEY, 'foobar')
            ->willReturn('foobarbazqux');

        $this->order->expects($this->once())
            ->method('update_status')
            ->with('pending', 'Awaiting loan approval.');

        $this->order->method('get_checkout_payment_url')
            ->with(true)
            ->willReturn('http://foo.bar');

        $this->woocommerce_stub->expects($this->never())
            ->method('notify');

        $this->assertEquals($this->checkout->checkout(1), [
            'result' => 'success',
            'redirect' => 'http://foo.bar',
        ]);
    }

    public function test_cannot_checkout_backend_order_creation_error()
    {
        $this->response->method('is_error')
            ->willReturn(true);

        $this->order->method('get_checkout_payment_url')->willReturn('http://foo.bar');

        $this->assertEquals($this->checkout->checkout(1), [
            'result' => 'failure',
            'redirect' => 'http://foo.bar'
        ]);
    }

    public function test_cannot_checkout_request_format_error()
    {
        $this->order_repository_stub->method('create')
            ->will($this->throwException(new UnexpectedValueException()));

        $this->order->method('get_checkout_payment_url')
            ->willReturn('http://foo.bar');

        $this->assertEquals($this->checkout->checkout(1), [
            'result' => 'failure',
            'redirect' => 'http://foo.bar'
        ]);
    }

    public function test_can_disable_gateway()
    {
        $this->woocommerce_stub->expects($this->once())
            ->method('flag_payment_gateway_disabled_for_user');

        $this->checkout->disable_gateway();
    }
}
