<?php

namespace WC_Lending_Works\Tests;

use WC_Lending_Works\Lib\Checkout\Checkout;
use WC_Lending_Works\Lib\Http;
use WC_Lending_Works\Lib\Order\Order_Repository;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter_Legacy;
use WP_UnitTestCase;

class OrderCreatedOnCheckout extends WP_UnitTestCase
{
    public function setUp()
    {
        // Mocking http response
        add_filter( 'pre_http_request', function($preempt, $args, $url) {
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body' => json_encode(['token' => 'foobar']),
            ];
        }, 10, 3 );
    }

    public function test_creating_an_order_after_clicking_checkout()
    {
        $order = \wc_create_order();

        global $woocommerce;
        if (version_compare($woocommerce->version, '3.0', '<')) {
            $woocommerce_adapter = new Woocommerce_Adapter_Legacy();
            $create_request = new Http\Create\Request_Legacy('http://example.com/orders', 'foobarbaz', $woocommerce_adapter);
        } else {
            $woocommerce_adapter = new Woocommerce_Adapter();
            $create_request = new Http\Create\Request('http://example.com/orders', 'foobarbaz', $woocommerce_adapter);
        }

        $fulfill_request = new Http\Fulfill\Request('http://example.com/orders', 'foobarbaz', $woocommerce_adapter);
        $order_repository = new Order_Repository($woocommerce_adapter, $create_request, $fulfill_request);
        $checkout = new Checkout($woocommerce_adapter, $order_repository);

        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        $checkout->checkout($order_id);

        $this->assertEquals($woocommerce_adapter->get_order_meta($order, 'lendingworks_order_token'), 'foobar');
    }
}
