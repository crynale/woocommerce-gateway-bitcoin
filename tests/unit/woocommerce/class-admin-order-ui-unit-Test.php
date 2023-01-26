<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Admin_Order_UI
 */
class Admin_Order_UI_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}


	/**
	 * @covers ::register_address_transactions_meta_box
	 */
	public function test_register_address_transactions_meta_box_bitcoin_order(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::once( true ),
			)
		);

		$order_post     = new \stdClass();
		$order_post->ID = 123;

		$GLOBALS['post'] = $order_post;

		$sut = new Admin_Order_UI( $api, $logger );

		\WP_Mock::userFunction(
			'add_meta_box',
			array(
				'times' => 1,
			)
		);

		$sut->register_address_transactions_meta_box();

	}

	/**
	 * Don't show the metabox if the current order is not a Bitcoin order.
	 *
	 * @covers ::register_address_transactions_meta_box
	 */
	public function test_register_address_transactions_meta_box_not_bitcoin_order(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::once( false ),
			)
		);

		$order_post     = new \stdClass();
		$order_post->ID = 123;

		$GLOBALS['post'] = $order_post;

		$sut = new Admin_Order_UI( $api, $logger );

		\WP_Mock::userFunction(
			'add_meta_box',
			array(
				'times' => 0,
			)
		);

		$sut->register_address_transactions_meta_box();

	}

}
