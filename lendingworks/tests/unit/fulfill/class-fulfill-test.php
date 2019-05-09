<?php

namespace WC_Lending_Works\Tests\Fulfill;

use PHPUnit\Framework\TestCase;
use stdClass;
use UnexpectedValueException;
use WC_Lending_Works\Lib\Fulfill\Fulfill;
use WC_Lending_Works\Lib\Http\Fulfill\Response;
use WC_Lending_Works\Lib\Order\Order_Repository;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter;
use WC_Lending_Works\Lib\Payment_Gateway;
use WC_Order;
use const WC_Lending_Works\PLUGIN_NAME;
use const WC_Lending_Works\PLUGIN_VERSION;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class Fulfill_Test extends TestCase
{
    private $woocommerce_stub;
    private $order_repository_stub;
    private $fulfill;
    private $order;
    private $response;

    public function setUp()
    {
        $this->woocommerce_stub = $this->createMock(Woocommerce_Adapter::class);
        $this->order_repository_stub = $this->createMock(Order_Repository::class);
        $this->fulfill = new Fulfill($this->woocommerce_stub, $this->order_repository_stub, false);

        $this->order = $this->createMock(WC_Order::class);

        $this->response = $this->createMock(Response::class);

        $this->order_repository_stub->method('fulfill')->willReturn($this->response);

        $_POST['order_id'] = 1;
    }

    public function test_can_instanciate()
    {
        $this->assertInstanceOf(Fulfill::class, $this->fulfill);
    }

    public function test_can_process_order_options()
    {
        $this->woocommerce_stub->expects($this->exactly(2))
            ->method('get_order_meta')
            ->withConsecutive(
                [$this->order, Payment_Gateway::ORDER_REFERENCE_METADATA_KEY],
                [$this->order, Payment_Gateway::ORDER_FULFILLED_METADATA_KEY]
            )
            ->willReturn('SMPL123456789', null);

        $this->order->expects($this->once())
            ->method('get_status')
            ->willReturn('processing');

        $gateway = new StdClass();
        $gateway->id = 'wc-lending-works';
        $this->woocommerce_stub->expects($this->once())
            ->method('get_payment_gateway')
            ->willReturn([
                'wc-lending-works' => $gateway
            ]);

        $this->woocommerce_stub->expects($this->once())
            ->method('get_payment_method')
            ->willReturn('wc-lending-works');

        $this->order->expects($this->once())
            ->method('get_total_refunded')
            ->willReturn(0.0);

        if (method_exists($this->order, 'get_id')) {
            $this->order->expects($this->once())
                ->method('get_id')
                ->willReturn(1);
        } else {
            $this->order->id = 1;
        }

        $fulfill = new Fulfill($this->woocommerce_stub, $this->order_repository_stub, true);
        $fulfill->process_order_options($this->order);

        $this->expectOutputString("<p class='form-field form-field-wide lw-wc-order-fulfill'>
					  <label for='fulfill-item'>Lending Works:</label>
					  <input id='fulfill-item' type='submit' class='button fulfill-items' value='Fulfill order' 
						data-order-reference='SMPL123456789'
						data-order-id='1' />
				  </p>");
    }

    public function test_can_disable_fulfill_button_is_status_not_fulfillable()
    {
        $this->woocommerce_stub->expects($this->exactly(2))
            ->method('get_order_meta')
            ->withConsecutive(
                [$this->order, Payment_Gateway::ORDER_REFERENCE_METADATA_KEY],
                [$this->order, Payment_Gateway::ORDER_FULFILLED_METADATA_KEY]
            )
            ->willReturn('SMPL123456789', 'fulfilled');

        $this->order->expects($this->once())
            ->method('get_status')
            ->willReturn('processing');

        $gateway = new StdClass();
        $gateway->id = 'wc-lending-works';
        $this->woocommerce_stub->expects($this->once())
            ->method('get_payment_gateway')
            ->willReturn([
                'wc-lending-works' => $gateway
            ]);

        $this->woocommerce_stub->expects($this->once())
            ->method('get_payment_method')
            ->willReturn('wc-lending-works');

        $this->order->expects($this->once())
            ->method('get_total_refunded')
            ->willReturn(0.0);

        if (method_exists($this->order, 'get_id')) {
            $this->order->expects($this->once())
                ->method('get_id')
                ->willReturn(1);
        } else {
            $this->order->id = 1;
        }

        $fulfill = new Fulfill($this->woocommerce_stub, $this->order_repository_stub, true);
        $fulfill->process_order_options($this->order);

        $this->expectOutputString("<p class='form-field form-field-wide lw-wc-order-fulfill'>
					  <label for='fulfill-item'>Lending Works:</label>
					  <input id='fulfill-item' type='submit' class='button fulfill-items' value='Fulfill order' 
						data-order-reference='SMPL123456789'
						data-order-id='1' disabled />
				  </p>");
    }

    public function test_cannot_show_fulfill_button_when_fulfillment_disallowed()
    {
        $this->woocommerce_stub->expects($this->exactly(2))
            ->method('get_order_meta')
            ->withConsecutive(
                [$this->order, Payment_Gateway::ORDER_REFERENCE_METADATA_KEY],
                [$this->order, Payment_Gateway::ORDER_FULFILLED_METADATA_KEY]
            )
            ->willReturn('SMPL123456789', 'fulfilled');

        $this->order->expects($this->once())
            ->method('get_status')
            ->willReturn('processing');

        $gateway = new StdClass();
        $gateway->id = 'wc-lending-works';
        $this->woocommerce_stub->expects($this->never())
            ->method('get_payment_gateway')
            ->willReturn([
                'wc-lending-works' => $gateway
            ]);

        $this->woocommerce_stub->expects($this->never())
            ->method('get_payment_method')
            ->willReturn('wc-lending-works');

        $this->order->expects($this->never())
            ->method('get_total_refunded')
            ->willReturn(0.0);

        if (method_exists($this->order, 'get_id')) {
            $this->order->expects($this->once())
                ->method('get_id')
                ->willReturn(1);
        } else {
            $this->order->id = 1;
        }

        $this->fulfill->process_order_options($this->order);
    }

    public function test_cannot_show_fulfill_button_when_order_not_lendingworks()
    {
        $this->woocommerce_stub->expects($this->exactly(2))
            ->method('get_order_meta')
            ->withConsecutive(
                [$this->order, Payment_Gateway::ORDER_REFERENCE_METADATA_KEY],
                [$this->order, Payment_Gateway::ORDER_FULFILLED_METADATA_KEY]
            )
            ->willReturn('SMPL123456789', 'fulfilled');

        $this->order->expects($this->once())
            ->method('get_status')
            ->willReturn('processing');

        $gateway = new StdClass();
        $gateway->id = 'test';
        $this->woocommerce_stub->expects($this->once())
            ->method('get_payment_gateway')
            ->willReturn([
                'test' => $gateway
            ]);

        $this->woocommerce_stub->expects($this->once())
            ->method('get_payment_method')
            ->willReturn('test');

        $this->order->expects($this->never())
            ->method('get_total_refunded')
            ->willReturn(0.0);

        if (method_exists($this->order, 'get_id')) {
            $this->order->expects($this->once())
                ->method('get_id')
                ->willReturn(1);
        } else {
            $this->order->id = 1;
        }

        $fulfill = new Fulfill($this->woocommerce_stub, $this->order_repository_stub, true);
        $fulfill->process_order_options($this->order);
    }

    public function test_cannot_show_fulfill_button_when_order_refunded()
    {
        $this->woocommerce_stub->expects($this->exactly(2))
            ->method('get_order_meta')
            ->withConsecutive(
                [$this->order, Payment_Gateway::ORDER_REFERENCE_METADATA_KEY],
                [$this->order, Payment_Gateway::ORDER_FULFILLED_METADATA_KEY]
            )
            ->willReturn('SMPL123456789', 'fulfilled');

        $this->order->expects($this->once())
            ->method('get_status')
            ->willReturn('processing');

        $gateway = new StdClass();
        $gateway->id = 'wc-lending-works';
        $this->woocommerce_stub->expects($this->once())
            ->method('get_payment_gateway')
            ->willReturn([
                'wc-lending-works' => $gateway
            ]);

        $this->woocommerce_stub->expects($this->once())
            ->method('get_payment_method')
            ->willReturn('wc-lending-works');

        $this->order->expects($this->once())
            ->method('get_total_refunded')
            ->willReturn(1.1);

        if (method_exists($this->order, 'get_id')) {
            $this->order->expects($this->once())
                ->method('get_id')
                ->willReturn(1);
        } else {
            $this->order->id = 1;
        }

        $fulfill = new Fulfill($this->woocommerce_stub, $this->order_repository_stub, true);
        $fulfill->process_order_options($this->order);
    }

    public function test_can_load_script()
    {
        $this->woocommerce_stub->expects($this->once())
            ->method('add_script')
            ->with(PLUGIN_NAME, '/wp-content/plugins/lendingworks/templates/fulfillOrder.js', [], PLUGIN_VERSION);

        $this->fulfill->load_script();
    }

    public function test_can_fulfill_an_order()
    {
        $this->response->method('is_error')
            ->willReturn(false);

        $this->woocommerce_stub->expects($this->once())
            ->method('get_order')
            ->willReturn($this->order);

        $this->order_repository_stub->expects($this->once())
            ->method('fulfill');

        $this->woocommerce_stub->expects($this->once())
            ->method('response')
            ->with('Order fulfilled');

        $this->fulfill->ajax_fulfill_order();
    }

    public function test_cannot_fulfill_order_backend_error()
    {

        $this->response->method('is_error')
            ->willReturn(true);

        $this->response->method('get_error_message')
            ->willReturn('Backend error');

        $this->woocommerce_stub->expects($this->once())
            ->method('get_order')
            ->willReturn($this->order);

        $this->order_repository_stub->expects($this->once())
            ->method('fulfill')
            ->willReturn($this->response);

        $this->woocommerce_stub->expects($this->once())
            ->method('error')
            ->with('Backend error', 500);

        $this->fulfill->ajax_fulfill_order();
    }

    public function test_cannot_fulfill_request_format_error()
    {
        $this->order_repository_stub->method('fulfill')
            ->will($this->throwException(new UnexpectedValueException('Error')));

        $this->woocommerce_stub->expects($this->once())
            ->method('get_order')
            ->willReturn($this->order);

        $this->woocommerce_stub->expects($this->once())
            ->method('error')
            ->with('Error', 500);

        $this->fulfill->ajax_fulfill_order();
    }

    public function test_can_fulfill_order_automatically_upon_completed_when_option_enabled()
    {
        $this->order_repository_stub->expects($this->once())
            ->method('fulfill');

        $this->woocommerce_stub->expects($this->once())
            ->method('get_order_meta')
            ->with($this->order, Payment_Gateway::ORDER_FULFILLED_METADATA_KEY)
            ->willReturn(null);

        $this->fulfill->complete_order(1, $this->order);
    }

    public function test_cannot_fulfill_order_on_completed_when_option_disabled()
    {
        $this->order_repository_stub->expects($this->never())
            ->method('fulfill');

        $this->woocommerce_stub->expects($this->once())
            ->method('get_order_meta')
            ->with($this->order, Payment_Gateway::ORDER_FULFILLED_METADATA_KEY)
            ->willReturn('fulfilled');

        $this->fulfill->complete_order(1, $this->order);
    }

    public function test_cannot_fulfill_order_on_completed_when_rder_already_fulfilled()
    {
        $fulfill = new Fulfill($this->woocommerce_stub, $this->order_repository_stub, true);

        $this->order_repository_stub->expects($this->never())
            ->method('fulfill');

        $fulfill->complete_order(1, $this->order);
    }
}
