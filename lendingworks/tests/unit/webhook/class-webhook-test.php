<?php

namespace WC_Lending_Works\Tests\Webhook;

use PHPUnit\Framework\TestCase;
use WC_Lending_Works\Lib\Payment_Gateway;
use WC_Lending_Works\Lib\Webhook\Webhook;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter;
use WC_Order;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class Webhook_Test extends TestCase
{
    private $woocommerce_mock;

    private $order_mock;

    private $old_post;

    public function setUp()
    {
        $this->woocommerce_mock = $this->createMock(Woocommerce_Adapter::class);

        $this->order_mock = $this->createMock(WC_Order::class);

        $this->woocommerce_mock->expects($this->once())
            ->method('get_order_meta')
            ->with($this->order_mock, Payment_Gateway::ORDER_TOKEN_METADATA_KEY)
            ->willReturn('foobarbazqux');

        $this->woocommerce_mock->expects($this->once())
            ->method('get_order')
            ->with(1)
            ->willReturn($this->order_mock);

        $this->old_post = $_POST;
    }

    public function tearDown()
    {
        $_POST = $this->old_post;
    }

    public function test_can_redirect_with_error_when_invalid_nonce()
    {
        $_POST = [
            'order_id' => 1,
            'reference' => 'SMPL123456789',
            'status' => 'approved',
            'nonce' => 'TUVWXYZ',
        ];

        $this->woocommerce_mock->method('unslash')
            ->with($_POST)
            ->willReturn($_POST);

        $this->woocommerce_mock->method('sanitize')
            ->withConsecutive([$_POST['order_id']], [$_POST['reference']], [$_POST['status']], [$_POST['nonce']])
            ->willReturnOnConsecutiveCalls($_POST['order_id'], $_POST['reference'], $_POST['status'], $_POST['nonce']);

        $this->woocommerce_mock->expects($this->once())
            ->method('authenticate')
            ->with('TUVWXYZ', 'foobarbazqux')
            ->willReturn(false);

        $this->woocommerce_mock->expects($this->once())
            ->method('checkout_url')
            ->willReturn('http://www.example.com/checkout');

        $webhook = new Webhook($this->woocommerce_mock);

        $this->assertEquals(['result' => 'failure', 'redirect' => 'http://www.example.com/checkout'], $webhook->process());
    }

    /**
     * @dataProvider approved_status_provider
     */
    public function test_can_process_accepted_approved_referred_callback($status)
    {
        $_POST = [
            'order_id' => 1,
            'reference' => 'SMPL123456789',
            'status' => $status,
            'nonce' => 'TUVWXYZ',
        ];

        $this->woocommerce_mock->method('unslash')
            ->with($_POST)
            ->willReturn($_POST);

        $this->woocommerce_mock->method('sanitize')
            ->withConsecutive([$_POST['order_id']], [$_POST['reference']], [$_POST['status']], [$_POST['nonce']])
            ->willReturnOnConsecutiveCalls($_POST['order_id'], $_POST['reference'], $_POST['status'], $_POST['nonce']);

        $this->woocommerce_mock->expects($this->once())
            ->method('authenticate')
            ->with('TUVWXYZ', 'foobarbazqux')
            ->willReturn(true);

        $this->woocommerce_mock->expects($this->exactly(2))
            ->method('update_order_meta')
            ->withConsecutive(
                [$this->order_mock, Payment_Gateway::ORDER_STATUS_METADATA_KEY, $status],
                [$this->order_mock, Payment_Gateway::ORDER_REFERENCE_METADATA_KEY, 'SMPL123456789']
            );

        $this->order_mock->expects($this->once())
            ->method('payment_complete');

        $this->order_mock->expects($this->once())
            ->method('get_checkout_order_received_url')
            ->willReturn('http://www.example.com/thankyou');

        $this->woocommerce_mock->expects($this->once())
            ->method('redirect')
            ->with('http://www.example.com/thankyou');

        $webhook = new Webhook($this->woocommerce_mock);

        $webhook->process();
    }

    /**
     * @dataProvider cancelled_status_provider
     */
    public function test_can_process_cancelled_expired_callback($status)
    {
        $_POST = [
            'order_id' => 1,
            'reference' => '',
            'status' => $status,
            'nonce' => 'TUVWXYZ',
        ];

        $this->woocommerce_mock->method('unslash')
            ->with($_POST)
            ->willReturn($_POST);

        $this->woocommerce_mock->method('sanitize')
            ->withConsecutive([$_POST['order_id']], [$_POST['reference']], [$_POST['status']], [$_POST['nonce']])
            ->willReturnOnConsecutiveCalls($_POST['order_id'], $_POST['reference'], $_POST['status'], $_POST['nonce']);

        $this->woocommerce_mock->expects($this->once())
            ->method('authenticate')
            ->with('TUVWXYZ', 'foobarbazqux')
            ->willReturn(true);

        $this->woocommerce_mock->expects($this->once())
            ->method('update_order_meta')
            ->with($this->order_mock, Payment_Gateway::ORDER_STATUS_METADATA_KEY, $status);

        $this->order_mock->expects($this->once())
            ->method('update_status')
            ->with('pending', 'Loan cancelled or expired');

        $this->woocommerce_mock->expects($this->once())
            ->method('notify')
            ->with('Your Loan quote was cancelled or expired.', 'error');

        $this->woocommerce_mock->expects($this->once())
            ->method('checkout_url')
            ->willReturn('http://www.example.com/checkout');

        $this->woocommerce_mock->expects($this->once())
            ->method('redirect')
            ->with('http://www.example.com/checkout');

        $webhook = new Webhook($this->woocommerce_mock);

        $webhook->process();
    }

    public function test_can_process_declined_callback()
    {
        $_POST = [
            'order_id' => 1,
            'reference' => 'SMPL123456789',
            'status' => 'declined',
            'nonce' => 'TUVWXYZ',
        ];

        $this->woocommerce_mock->method('unslash')
            ->with($_POST)
            ->willReturn($_POST);

        $this->woocommerce_mock->method('sanitize')
            ->withConsecutive([$_POST['order_id']], [$_POST['reference']], [$_POST['status']], [$_POST['nonce']])
            ->willReturnOnConsecutiveCalls($_POST['order_id'], $_POST['reference'], $_POST['status'], $_POST['nonce']);

        $this->woocommerce_mock->expects($this->once())
            ->method('authenticate')
            ->with('TUVWXYZ', 'foobarbazqux')
            ->willReturn(true);

        $this->woocommerce_mock->expects($this->exactly(2))
            ->method('update_order_meta')
            ->withConsecutive(
                [$this->order_mock, Payment_Gateway::ORDER_STATUS_METADATA_KEY, 'declined'],
                [$this->order_mock, Payment_Gateway::ORDER_REFERENCE_METADATA_KEY, 'SMPL123456789']
            );

        $this->order_mock->expects($this->once())
            ->method('update_status')
            ->with('failed', 'Loan declined');

        $this->woocommerce_mock->expects($this->once())
            ->method('notify')
            ->with('Please use an alternative payment method.', 'error');

        $this->woocommerce_mock->expects($this->once())
            ->method('checkout_url')
            ->willReturn('http://www.example.com/checkout');

        $this->woocommerce_mock->expects($this->once())
            ->method('redirect')
            ->with('http://www.example.com/checkout');

        $webhook = new Webhook($this->woocommerce_mock);

        $webhook->process();
    }

    public function test_cannot_process_invalid_status_callback()
    {
        $_POST = [
            'order_id' => 1,
            'reference' => 'SMPL123456789',
            'status' => 'invalid',
            'nonce' => 'TUVWXYZ',
        ];

        $this->woocommerce_mock->method('unslash')
            ->with($_POST)
            ->willReturn($_POST);

        $this->woocommerce_mock->method('sanitize')
            ->withConsecutive([$_POST['order_id']], [$_POST['reference']], [$_POST['status']], [$_POST['nonce']])
            ->willReturnOnConsecutiveCalls($_POST['order_id'], $_POST['reference'], $_POST['status'], $_POST['nonce']);

        $this->woocommerce_mock->expects($this->once())
            ->method('authenticate')
            ->with('TUVWXYZ', 'foobarbazqux')
            ->willReturn(true);

        $this->woocommerce_mock->expects($this->never())
            ->method('update_order_meta')
            ->withConsecutive(
                [$this->order_mock, Payment_Gateway::ORDER_STATUS_METADATA_KEY, 'invalid'],
                [$this->order_mock, Payment_Gateway::ORDER_REFERENCE_METADATA_KEY, 'SMPL123456789']
            );

        $this->order_mock->expects($this->never())
            ->method('update_status');

        $this->woocommerce_mock->expects($this->once())
            ->method('notify')
            ->with('Status invalid', 'error');

        $this->woocommerce_mock->expects($this->once())
            ->method('checkout_url')
            ->willReturn('http://www.example.com/checkout');

        $this->woocommerce_mock->expects($this->once())
            ->method('redirect')
            ->with('http://www.example.com/checkout');

        $webhook = new Webhook($this->woocommerce_mock);

        $webhook->process();
    }

    public function approved_status_provider()
    {
        return [
            ['accepted'],
            ['approved'],
            ['referred']
        ];
    }

    public function cancelled_status_provider()
    {
        return [
            ['cancelled'],
            ['expired'],
        ];
    }
}
