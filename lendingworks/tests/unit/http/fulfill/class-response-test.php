<?php

namespace WC_Lending_Works\Tests\Http\Fulfill;

use PHPUnit\Framework\TestCase;
use WC_Lending_Works\Lib\Http\Fulfill\Response;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ResponseTest extends TestCase
{
	public function testCanBeInstanciated()
	{
		$response = new Response([]);

		$this->assertInstanceOf(Response::class, $response);
	}
}
