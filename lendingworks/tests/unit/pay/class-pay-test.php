<?php

namespace WC_Lending_Works\Tests\Pay;


use PHPUnit\Framework\TestCase;
use WC_Lending_Works\Lib\Pay\Pay;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter;
use WC_Lending_Works\Lib\Payment_Gateway;
use WC_Order;
use const WC_Lending_Works\PLUGIN_NAME;
use const WC_Lending_Works\PLUGIN_VERSION;
use const WC_Lending_Works\PLUGIN_DIR;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class Pay_Test extends TestCase
{
    private $woocommerce_stub;

    public function setUp()
    {
        $this->woocommerce_stub = $this->createMock(Woocommerce_Adapter::class);
    }

    public function test_can_instanciate()
    {
        $pay = new Pay($this->woocommerce_stub);

        $this->assertInstanceOf(Pay::class, $pay);
    }

    public function test_can_print_form()
    {
        $order_stub = $this->createMock(WC_Order::class);

        $this->woocommerce_stub->expects($this->once())
            ->method('get_order')
            ->with(1)
            ->willReturn($order_stub);

        $this->woocommerce_stub->expects($this->once())
            ->method('get_order_meta')
            ->with($order_stub, Payment_Gateway::ORDER_TOKEN_METADATA_KEY)
            ->willReturn('foobarbazqux');

        $this->woocommerce_stub->expects($this->once())
            ->method('encrypt')
            ->with('foobarbazqux')
            ->willReturn('TUVXYZ');

        $this->woocommerce_stub->expects($this->once())
            ->method('webhook_url')
            ->willReturn('http://api.docker:4000/orders/');

        $pay = new Pay($this->woocommerce_stub);

        $pay->print_form(1);

        $this->expectOutputString('<form id="order_review" method="POST" action="http://api.docker:4000/orders/?nonce=TUVXYZ">
				 <input type="hidden" name="order_id" value="" />
				 <input type="hidden" name="reference" value="" />
				 <input type="hidden" name="status" value="" />
				 <input type="hidden" name="nonce" value="TUVXYZ" />
				 <input type="submit" style="display: none;"/>
			 </form>');
    }

    public function test_can_load_scripts_in_test_mode()
    {
        $this->woocommerce_stub->expects($this->once())
            ->method('add_script')
            ->with(PLUGIN_NAME, 'https://retail-sandbox.secure.lendingworks.co.uk/checkout.js', ['jquery'], PLUGIN_VERSION);

        $fake_order = $this->createMock(WC_Order::class);

        $this->woocommerce_stub->expects($this->once())
            ->method('get_order')
            ->with(1)
            ->willReturn($fake_order);

        $this->woocommerce_stub->expects($this->once())
            ->method('checkout_url')
            ->willReturn('http://example.com/callback');

        $this->woocommerce_stub->expects($this->once())
            ->method('add_inline_script')
            ->with(PLUGIN_NAME,  $this->matchesRegularExpression('/checkoutHandler\.[\w+]?\.?js\.php/'), [
                'order' => $fake_order,
                'webhook_url' => 'http://example.com/callback'
            ], PLUGIN_DIR);

        $pay = new Pay($this->woocommerce_stub, true);

        $pay->load_scripts(1);
    }

    public function test_can_avoid_loading_script_if_order_already_has_lendingworks_status()
    {
        $fake_order = $this->createMock(WC_Order::class);

        $this->woocommerce_stub->expects($this->once())
            ->method('get_order')
            ->with(1)
            ->willReturn($fake_order);

        $this->woocommerce_stub->expects($this->once())
            ->method('get_order_meta')
            ->with($fake_order, Payment_Gateway::ORDER_STATUS_METADATA_KEY)
            ->willReturn('created');

        $this->woocommerce_stub->expects($this->once())
            ->method('notify')
            ->with('Your order has already been paid.', 'error');

        $this->woocommerce_stub->expects($this->once())
            ->method('checkout_url')
            ->willReturn('http://wwww.example.com/checkout');

        $this->woocommerce_stub->expects($this->once())
            ->method('redirect')
            ->with('http://wwww.example.com/checkout');

        $pay = new Pay($this->woocommerce_stub, true);
        $pay->load_scripts(1);
    }
}
