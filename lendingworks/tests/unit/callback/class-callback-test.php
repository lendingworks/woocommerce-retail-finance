<?php

namespace WC_Lending_Works\Tests\Callback;

use PHPUnit\Framework\TestCase;
use WC_Lending_Works\Lib\Callback\Callback;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter;
use WC_Lending_Works\Lib\Payment_Gateway;

class Callback_Test extends TestCase
{
	private $woocommerce;
	private $callback;
	private $api_key;

	public function setUp()
	{
		$this->woocommerce = $this->createMock(Woocommerce_Adapter::class);

		$this->api_key = 'foobarbazqux';
		$this->callback = new Callback($this->woocommerce, $this->api_key);
	}

	public function test_can_register_route_for_callback()
	{
		$this->woocommerce->expects($this->once())
			->method('add_route')
			->with(
			'lendingworks',
			'/orders/update-status',
			[
				'methods'  => \WP_REST_Server::EDITABLE,
				'callback' => [ $this->callback, 'process' ],
			]
		);

		$this->callback->regiter_callback_route();
	}

	public function test_cannot_process_when_missing_signature()
	{
		$json = json_encode([
			'reference' => 'SMPL123456789',
			'status'    => 'approved',
			'timestamp' => '1561976685',
		]);

		$_POST = [
			'json' => $json,
		];

		$this->woocommerce->expects($this->once())
			->method('error')
			->with(['message' => 'Missing authentication or payload'], 400);

		$this->callback->process();
	}

	public function test_can_create_a_new_query_type_by_loan_request_reference_meta()
	{
		$query['meta_query'] = [];
		$query_vars[Payment_Gateway::ORDER_REFERENCE_METADATA_KEY] = 'SMPL123456789';

		$query = $this->callback->handle_custom_query($query, $query_vars);

		$this->assertEquals([
			'key' => Payment_Gateway::ORDER_REFERENCE_METADATA_KEY,
			'value' => 'SMPL123456789',
		], $query['meta_query'][0]);

	}

	public function test_cannot_process_when_missing_body()
	{
		$json = json_encode([
			'reference' => 'SMPL123456789',
			'status'    => 'approved',
			'timestamp' => '1561976685',
		]);

		unset($_POST['json']);

		$_SERVER += [
			'HTTP_X_HOOK_SIGNATURE' => $this->getSignature($json, $this->api_key),
		];

		$this->woocommerce->expects($this->once())
			->method('error')
			->with(['message' => 'Missing authentication or payload'], 400);

		$this->callback->process();
	}

	public function test_cannot_process_with_wrong_signature()
	{
		$json = json_encode([
			'reference' => 'SMPL123456789',
			'status'    => 'approved',
			'timestamp' => '1561976685',
		]);

		$_POST = [
			'json' => $json,
		];

		unset($_SERVER['HTTP_X_HOOK_SIGNATURE']);
		$_SERVER += [
			'HTTP_X_HOOK_SIGNATURE' => $this->getSignature($json, 'foobar'),
		];

		$this->woocommerce->expects($this->once())
			->method('error')
			->with(['message' => 'Invalid credentials.'], 403);

		$this->callback->process();
	}

	public function test_cannot_process_when_no_matching_order_for_loan_request_reference()
	{
		$json = json_encode([
			'reference' => 'SMPL123456789',
			'status'    => 'approved',
			'timestamp' => '1561976685',
		]);

		$_POST = [
			'json' => $json,
		];

		unset($_SERVER['HTTP_X_HOOK_SIGNATURE']);
		$_SERVER += [
			'HTTP_X_HOOK_SIGNATURE' => $this->getSignature($json, $this->api_key),
		];

        $this->woocommerce->method('unslash')
            ->withConsecutive([$_SERVER['HTTP_X_HOOK_SIGNATURE']], [$_POST['json']])
            ->willReturn($_SERVER['HTTP_X_HOOK_SIGNATURE'], $_POST['json']);

        $this->woocommerce->method('sanitize')
            ->withConsecutive([$_SERVER['HTTP_X_HOOK_SIGNATURE']], [$_POST['json']])
            ->willReturnOnConsecutiveCalls($_SERVER['HTTP_X_HOOK_SIGNATURE'], $_POST['json']);


        $this->woocommerce->method('get_order_by_meta')
			->with(Payment_Gateway::ORDER_REFERENCE_METADATA_KEY, 'SMPL123456789')
			->willReturn([]);

		$this->woocommerce->expects($this->once())
			->method('response')
			->with(['message' => 'No order found.']);

		$this->callback->process();
	}

	public function test_can_process_and_update_order_by_loan_request_reference()
	{
		$json = json_encode([
			'reference' => 'SMPL123456789',
			'status'    => 'approved',
			'timestamp' => '1561976685',
		]);

		unset($_POST['json']);
		$_POST = [
			'json' => $json,
		];

		unset($_SERVER['HTTP_X_HOOK_SIGNATURE']);
		$_SERVER += [
			'HTTP_X_HOOK_SIGNATURE' => $this->getSignature($json, $this->api_key),
		];

        $this->woocommerce->method('unslash')
            ->withConsecutive([$_SERVER['HTTP_X_HOOK_SIGNATURE']], [$_POST['json']])
            ->willReturn($_SERVER['HTTP_X_HOOK_SIGNATURE'], $_POST['json']);

        $this->woocommerce->method('sanitize')
            ->withConsecutive([$_SERVER['HTTP_X_HOOK_SIGNATURE']], [$_POST['json']])
            ->willReturnOnConsecutiveCalls($_SERVER['HTTP_X_HOOK_SIGNATURE'], $_POST['json']);

		$order = $this->createMock(\WC_Order::class);

		$this->woocommerce->expects($this->once())
			->method('get_order_by_meta')
			->with(Payment_Gateway::ORDER_REFERENCE_METADATA_KEY, 'SMPL123456789')
			->willReturn([ $order ]);

		$this->woocommerce->expects($this->once())
			->method('update_order_meta')
			->with($order, Payment_Gateway::ORDER_STATUS_METADATA_KEY, 'approved')
			->willReturn([ $order ]);

		$this->woocommerce->expects($this->once())
			->method('response')
			->with(['message' => 'Order status for loan request reference SMPL123456789 updated' ]);

		$this->callback->process();
	}

	private function getSignature($body, $key)
	{
		$signature_body = $body . $key;
		$hashed_signature = hash('sha512', $signature_body, true);
		return base64_encode($hashed_signature);
	}
}
