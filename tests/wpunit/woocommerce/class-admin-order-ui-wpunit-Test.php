<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use WC_Order;
use WP_Post;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Admin_Order_UI
 */
class Admin_Order_UI_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::print_address_transactions_metabox
	 * @covers ::__construct
	 */
	public function test_print_address_transactions_metabox(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::once(
					function( int $order_id ) {
						return true;
					}
				),
			)
		);

		$sut = new Admin_Order_UI( $api, $logger );

		$order    = new WC_Order();
		$order_id = $order->save();
		/** @var WP_Post $post */
		$post = get_post( $order_id );

		add_filter(
			'wc_get_template',
			function() {
				throw new \Exception( 'wc_get_template' );
			}
		);

		$e = null;
		try {
			$sut->print_address_transactions_metabox( $post );
		} catch ( \Exception $exception ) {
			$e = $exception;
		}

		// Is there a better way to say wc_get_template was called?
		$this->assertNotNull( $e );
		$this->assertEquals( 'wc_get_template', $e->getMessage() );

	}

	/**
	 * @covers ::print_address_transactions_metabox
	 */
	public function test_print_address_transactions_metabox_not_bitcoin_gateway(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::once(
					function( int $order_id ) {
						return false;
					}
				),
			)
		);

		$sut = new Admin_Order_UI( $api, $logger );

		$order    = new WC_Order();
		$order_id = $order->save();
		/** @var WP_Post $post */
		$post = get_post( $order_id );

		add_filter(
			'wc_get_template',
			function() {
				throw new \Exception();
			}
		);

		$e = null;
		try {
			$sut->print_address_transactions_metabox( $post );
		} catch ( \Exception $exception ) {
			$e = $exception;
		}

		// Is there a better way to say wc_get_template was called?
		$this->assertNull( $e );

	}

	/**
	 * @covers ::print_address_transactions_metabox
	 */
	public function test_print_address_transactions_metabox_exception_in_api(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_order_has_bitcoin_gateway' => Expected::once(
					function( int $order_id ) {
						return true;
					}
				),
				'get_formatted_order_details'  => Expected::once(
					function( $order ) {
						throw new \Exception( 'no btc address exception' );
					}
				),
			)
		);

		$sut = new Admin_Order_UI( $api, $logger );

		$order    = new WC_Order();
		$order_id = $order->save();
		/** @var WP_Post $post */
		$post = get_post( $order_id );

		$sut->print_address_transactions_metabox( $post );

		// Is there a better way to say wc_get_template was called?
		$this->assertTrue( $logger->hasWarningThatContains( 'no btc address exception' ) );

	}

}
