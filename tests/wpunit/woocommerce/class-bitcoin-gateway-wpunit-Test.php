<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use Codeception\Stub\Expected;
use Exception;
use BrianHenryIE\WC_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use WC_Order;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Bitcoin_Gateway
 */
class Bitcoin_Gateway_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::process_admin_options
	 */
	public function test_generates_new_addresses_when_xpub_changes(): void {

		$GLOBALS['bh_wc_bitcoin_gateway'] = $this->makeEmpty(
			API_Interface::class,
			array(
				'generate_new_wallet'               => Expected::once(
					function( string $xpub_after, string $gateway_id = null ) {
						return array();
					}
				),
				'generate_new_addresses_for_wallet' => Expected::once(
					function( string $xpub, int $generate_count ): array {
						assert( 2 === $generate_count );
						return array();
					}
				),
			)
		);

		$sut                   = new Bitcoin_Gateway();
		$sut->settings['xpub'] = 'before';

		$xpub_after = 'after';

		$_POST['woocommerce_bitcoin_gateway_xpub'] = $xpub_after;

		$sut->process_admin_options();
	}


	/**
	 * @covers ::process_admin_options
	 */
	public function test_does_not_generate_new_addresses_when_xpub_does_not_change(): void {

		$GLOBALS['bh_wc_bitcoin_gateway'] = $this->makeEmpty(
			API_Interface::class,
			array()
		);

		$sut                   = new Bitcoin_Gateway();
		$sut->settings['xpub'] = 'same';

		$_POST['woocommerce_bitcoin_gateway_xpub'] = 'same';

		assert( false === as_next_scheduled_action( Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK ) );

		$sut->process_admin_options();

		$this->assertFalse( as_next_scheduled_action( Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK ) );
	}

	/**
	 * @covers ::is_available
	 */
	public function test_checks_for_available_address_for_availability_true(): void {

		$GLOBALS['bh_wc_bitcoin_gateway'] = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_fresh_address_available_for_gateway' => Expected::once(
					function( Bitcoin_Gateway $gateway ) {
						return true;
					}
				),
			)
		);

		$sut          = new Bitcoin_Gateway();
		$sut->enabled = 'yes';

		$result = $sut->is_available();

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::is_available
	 */
	public function test_checks_for_available_address_for_availability_false(): void {

		$GLOBALS['bh_wc_bitcoin_gateway'] = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_fresh_address_available_for_gateway' => Expected::once(
					function( Bitcoin_Gateway $gateway ) {
						return false;
					}
				),
			)
		);

		$sut          = new Bitcoin_Gateway();
		$sut->enabled = 'yes';

		$result = $sut->is_available();

		$this->assertFalse( $result );
	}

	/**
	 * @covers ::is_available
	 */
	public function test_checks_for_available_address_for_availability_uses_cache(): void {

		$GLOBALS['bh_wc_bitcoin_gateway'] = $this->makeEmpty( API_Interface::class );

		$sut = new class() extends Bitcoin_Gateway {
			public function __construct() {
				parent::__construct();
				$this->is_available = false;
			}
		};

		$result = $sut->is_available();

		$this->assertFalse( $result );
	}

	/**
	 * @covers ::is_available
	 */
	public function test_checks_for_available_address_for_availability_false_when_no_api_class(): void {

		$GLOBALS['bh_wc_bitcoin_gateway'] = null;

		$sut = new Bitcoin_Gateway();

		$result = $sut->is_available();

		$this->assertFalse( $result );
	}

	/**
	 * @covers ::process_payment
	 */
	public function test_process_payment_returns_exception_on_bad_order_id(): void {

		$GLOBALS['bh_wc_bitcoin_gateway'] = $this->makeEmpty( API_Interface::class );

		$sut = new Bitcoin_Gateway();

		$exception = null;
		try {
			$sut->process_payment( 123 );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertNotNull( $exception );
		$this->assertEquals( 'Error creating order.', $exception->getMessage() );
	}

	/**
	 * @covers ::process_payment
	 */
	public function test_process_payment_returns_exception_on_missing_api_instance(): void {

		$GLOBALS['bh_wc_bitcoin_gateway'] = null;

		$sut = new Bitcoin_Gateway();

		$order    = new WC_Order();
		$order_id = $order->save();

		$exception = null;
		try {
			$sut->process_payment( $order_id );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertNotNull( $exception );
		$this->assertEquals( 'API unavailable for new Bitcoin gateway order.', $exception->getMessage() );
	}


	/**
	 * @covers ::process_payment
	 */
	public function test_process_payment_returns_exception_when_no_address_available(): void {

		$GLOBALS['bh_wc_bitcoin_gateway'] = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_fresh_address_for_order' => Expected::once(
					function( WC_Order $order ) {
						throw new Exception( 'This message will not be shown!' );
					}
				),
			)
		);

		$sut = new Bitcoin_Gateway();

		$order    = new WC_Order();
		$order_id = $order->save();

		$exception = null;
		try {
			$sut->process_payment( $order_id );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertNotNull( $exception );
		$this->assertEquals( 'Unable to find Bitcoin address to send to. Please choose another payment method.', $exception->getMessage() );
	}

}
