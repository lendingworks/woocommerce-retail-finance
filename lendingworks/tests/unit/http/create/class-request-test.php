<?php

namespace WC_Lending_Works\Tests\Http\Create;

use PHPUnit\Framework\TestCase;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter;
use WC_Lending_Works\Lib\Http\Create\Request;
use \WC_Order;
use \WC_Order_Item_Product;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class RequestTest extends TestCase
{
    protected function setUp()
    {
    	$this->woocommerce_stub = $this->createMock(Woocommerce_Adapter::class);
        $this->fake_order = $this->createMock(WC_Order::class);

        $this->request = new Request('http://api.docker:4000/', 'foo', $this->woocommerce_stub);
    }

    public function testGetRequestUrlForTesting()
    {
        $this->request = new Request('http://api.docker:4000/', 'foo', $this->woocommerce_stub);

        $this->assertEquals('http://api.docker:4000/orders', $this->request->get_url());
    }

    public function testGetRequestUrlForProduction()
    {
        $this->request = new Request('https://www.lendingworks.co.uk/api/v2/', 'foo', $this->woocommerce_stub);

        $this->assertEquals('https://www.lendingworks.co.uk/api/v2/orders', $this->request->get_url());
    }

    public function testGetHeaders()
    {
        $this->request = new Request('http://api.docker:4000/', 'foobarbazqux', $this->woocommerce_stub);

        $headers = [
            'Content-type' => 'application/json',
            'Authorization' => 'RetailApiKey foobarbazqux',
        ];

        $this->assertEquals($headers, $this->request->get_headers());
    }

    public function testGetBodyFromOrderWithoutDiscount()
    {
        $this->setStubs();

        $this->fake_order->method('get_total')
            ->willReturn(15.0);

        $body = [
            'amount' => 15.0,
            'products' => [
                [
                    'cost' => 5.0,
                    'quantity' => 1,
                    'description' => 'foo',
                ], [
                    'cost' => 5.0,
                    'quantity' => 1,
                    'description' => 'bar',
                ], [
                    'cost' => 5.0,
                    'quantity' => 1,
                    'description' => 'Shipping: bar',
                ],
            ],
        ];

		$this->woocommerce_stub->method('json_encode')
			->with($body)
			->willReturn(json_encode($body));

        $this->assertEquals($body, json_decode($this->request->get_body($this->fake_order), true));
    }

    public function testGetBodyFromOrderWithDiscount()
    {
        $this->setStubs();

        $this->fake_order->method('get_total_discount')
            ->willReturn(1.0);

        $this->fake_order->method('get_total')
            ->willReturn(14.0);

        $body = [
            'amount' => 14.0,
            'products' => [
                [
                    'cost' => 5.0,
                    'quantity' => 1,
                    'description' => 'foo',
                ], [
                    'cost' => 5.0,
                    'quantity' => 1,
                    'description' => 'bar',
                ], [
                    'cost' => 5.0,
                    'quantity' => 1,
                    'description' => 'Shipping: bar',
                ], [
                    'cost' => -1.0,
                    'quantity' => 1,
                    'description' => 'Discount',
                ],
            ],
        ];

		$this->woocommerce_stub->method('json_encode')
			->with($body)
			->willReturn(json_encode($body));

        $this->assertEquals($body, json_decode($this->request->get_body($this->fake_order), true));
    }

    private function setStubs()
    {
        if (!class_exists(WC_Order_Item_Product::class)) {
            $this->markTestSkipped('This test requires wordpress minimum version 5.0.');
        }

        $product1 = $this->createMock(WC_Order_Item_Product::class);
        $product2 = $this->createMock(WC_Order_Item_Product::class);

        $product1->method('get_subtotal')->willReturn(5);
        $product2->method('get_subtotal')->willReturn(5);
        $product1->method('get_quantity')->willReturn(1);
        $product2->method('get_quantity')->willReturn(1);
        $product1->method('get_name')->willReturn('foo');
        $product2->method('get_name')->willReturn('bar');

        $this->fake_order->method('get_items')
            ->willReturn([$product1, $product2]);

        $this->fake_order->method('get_shipping_total')
            ->willReturn('5');
        $this->fake_order->method('get_shipping_method')
            ->willReturn('bar');
    }
}
