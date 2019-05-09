<?php

namespace WC_Lending_Works\Tests\Http\Create;

use WC_Lending_Works\Lib\Http\Create\Request_Legacy;
use WC_Order;

class Request_Legacy_Test
{
    protected function setUp()
    {
		$this->woocommerce_stub = $this->createMock(Woocommerce_Adapter::class);
        $this->fake_order = $this->createMock(WC_Order::class);

        $this->request = new Request_Legacy('http://api.docker:4000/', 'foo', $this->woocommerce_stub);
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
                    'cost' => 1.0,
                    'quantity' => 1,
                    'description' => 'Discount',
                ],
            ],
        ];

        $this->assertEquals($body, json_decode($this->request->get_body($this->fake_order), true));
    }

    private function setStubs()
    {
        $product1 = [
            'qty' => '1',
            'name' => 'foo',
        ];
        $product2 = [
            'qty' => '1',
            'name' => 'bar',
        ];

        $this->fake_order
            ->method('get_item_total')
            ->with($product1)
            ->willReturn('5');
        $this->fake_order
            ->method('get_item_total')
            ->with($product2)
            ->willReturn('5');

        $this->fake_order->method('get_items')
            ->willReturn([$product1, $product2]);

        $this->fake_order->method('get_shipping_total')
            ->willReturn('5');
        $this->fake_order->method('get_shipping_method')
            ->willReturn('bar');
    }
}
