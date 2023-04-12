<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\Frontend;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use Exception;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\Frontend\AJAX
 */
class AJAX_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::get_order_details
	 */
	public function test_bad_nonce(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$sut = new AJAX( $api, $logger );

		\WP_Mock::userFunction(
			'check_ajax_referer',
			array(
				'return' => false,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_send_json_error',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'array' ), \WP_Mock\Functions::type( 'int' ) ),
				'times'  => 1,
				'return' => function() {
					throw new Exception();
				},
			)
		);

		self::expectException( \Exception::class );

		$sut->get_order_details();
	}
}
