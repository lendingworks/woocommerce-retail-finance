<?php

namespace WC_Lending_Works\Tests;

use PHPUnit\Framework\TestCase;
use WC_Lending_Works\Lib\Payment_Gateway;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class Payment_Gateway_Test extends TestCase
{
    public function setUp()
    {
        global $woocommerce, $wp_query;

        $this->woocommerce = $woocommerce;
        $this->wp_query = $wp_query;
    }

    public function tearDown()
    {
        global $woocommerce, $wp_query;

        $woocommerce = $this->woocommerce;
        $wp_query = $this->wp_query;
    }

    public function test_can_instanciate()
    {
        $payment_gateway = new Payment_Gateway();

        $this->assertInstanceOf(Payment_Gateway::class, $payment_gateway);
    }

    public function test_can_init_form_fields()
    {
        $payment_gateway = new Payment_Gateway();

        $payment_gateway->init_form_fields();

        $this->assertArraySubset([
            'enabled' => [
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable Lending Works Retail finance',
                'default' => 'no',
            ],
            'api_key' => [
                'title' => 'Your Lending Works API key',
                'type' => 'password',
                'description' => 'Enter your Lending Works API key'
            ]
        ], $payment_gateway->form_fields);
    }

    public function test_validate_admin_options()
	{
		$payment_gateway = new Payment_Gateway();

		$options = [
			'min_total' => 49,
			'max_total' => 25001
		];

		$this->assertEquals([
			'min_total' => 50,
			'max_total' => 25000
		], $payment_gateway->validate_admin_options($options));
	}

    public function test_needs_setup()
    {
        $payment_gateway = new Payment_Gateway();

        $payment_gateway->set_api_key(123);

        $this->assertFalse($payment_gateway->needs_setup());

        $payment_gateway->set_api_key(null);

        $this->assertTrue($payment_gateway->needs_setup());
    }

    public function test_cannot_refund_order()
    {
        $payment_gateway = new Payment_Gateway();

        $order = $this->createMock(\WC_Order::class);

        $this->assertFalse($payment_gateway->can_refund_order($order));
    }

    public function test_can_set_api_key()
    {
        $payment_gateway = new Payment_Gateway();

        $this->assertEquals($payment_gateway->set_api_key('foo')->get_api_key(), 'foo');
    }

    public function test_can_set_test_mode()
    {
        $payment_gateway = new Payment_Gateway();

        $this->assertTrue($payment_gateway->set_test_mode(true)->get_test_mode());
    }

    public function test_can_set_fulfillment()
    {
        $payment_gateway = new Payment_Gateway();

        $this->assertTrue($payment_gateway->set_fulfillment(true)->is_fulfillment());
    }

    public function test_can_set_min_total()
    {
        $payment_gateway = new Payment_Gateway();

        $this->assertEquals($payment_gateway->set_min_total(10)->get_min_total(), 10);
    }

    public function test_can_set_max_total()
    {
        $payment_gateway = new Payment_Gateway();

        $this->assertEquals($payment_gateway->set_max_total(100)->get_max_total(), 100);
    }
}
