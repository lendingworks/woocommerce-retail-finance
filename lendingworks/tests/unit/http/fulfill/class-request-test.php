<?php

namespace WC_Lending_Works\Tests\Http\Fulfill;

use PHPUnit\Framework\TestCase;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter;
use WC_Lending_Works\Lib\Http\Fulfill\Request;
use WC_Lending_Works\Lib\Payment_Gateway;
use \WC_Order;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class RequestTest extends TestCase
{
	private $fake_order;

	private $woocommerce_stub;

	protected function setUp()
	{
		$this->fake_order = $this->createMock(WC_Order::class);
		$this->woocommerce_stub = $this->createMock(Woocommerce_Adapter::class);

		$this->request = new Request('http://api.docker:4000/', 'foo', $this->woocommerce_stub);
	}

	public function testGetRequestUrlForTesting()
	{
		$this->request = new Request('http://api.docker:4000/', 'foo', $this->woocommerce_stub);

		$this->assertEquals('http://api.docker:4000/loan-requests/fulfill', $this->request->get_url());
	}

	public function testGetRequestUrlForProduction()
	{
		$this->request = new Request('https://www.lendingworks.co.uk/api/v2/', 'foo', $this->woocommerce_stub);

		$this->assertEquals('https://www.lendingworks.co.uk/api/v2/loan-requests/fulfill', $this->request->get_url());
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

	public function testCanGetBody()
	{
		$this->woocommerce_stub->expects($this->once())
			->method('get_order_meta')
			->with($this->fake_order, Payment_Gateway::ORDER_REFERENCE_METADATA_KEY)
			->willReturn('SMPL123456789');

		$this->woocommerce_stub->expects($this->once())
			->method('json_encode')
			->willReturn(json_encode([ 'reference' => 'SMPL123456789' ]));

		$this->assertEquals('{"reference":"SMPL123456789"}', $this->request->get_body($this->fake_order));
	}
}
