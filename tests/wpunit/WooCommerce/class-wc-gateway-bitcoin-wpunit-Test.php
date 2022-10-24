<?php

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

use Codeception\Stub\Expected;
use Exception;
use Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler\Background_Jobs;
use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Address;
use Nullcorps\WC_Gateway_Bitcoin\API_Interface;
use WC_Order;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\WooCommerce\WC_Gateway_Bitcoin
 */
class WC_Gateway_Bitcoin_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::process_payment
	 */
	public function test_process_payment_schedules_action(): void {

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_fresh_address_for_order' => $this->makeEmpty( Crypto_Address::class ),
				'get_exchange_rate'           => '44444.0',
				'convert_fiat_to_btc'         => '0.0001',
			)
		);

		$sut = new WC_Gateway_Bitcoin();

		$order = new WC_Order();
		$order->set_total( '1000' );
		$order_id = $order->save();

		$scheduled_before = as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK );

		assert( false === $scheduled_before );

		$result = $sut->process_payment( $order_id );

		$scheduled_after = as_has_scheduled_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK );

		$this->assertNotFalse( $scheduled_after );

	}

	/**
	 * @covers ::process_admin_options
	 */
	public function test_generates_new_addresses_when_xpub_changes(): void {

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty(
			API_Interface::class,
			array(
				'generate_new_wallet' => Expected::once(
					function( string $xpub_after, string $gateway_id = null ) {
						return array();}
				),
			)
		);

		$sut                   = new WC_Gateway_Bitcoin();
		$sut->settings['xpub'] = 'before';

		$xpub_after = 'after';

		$_POST['woocommerce_bitcoin_gateway_xpub'] = $xpub_after;
		$id                                        = $sut->id;

		assert( false === as_next_scheduled_action( Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK, array( $xpub_after, $id ) ) );

		$sut->process_admin_options();

		$scheduled = as_next_scheduled_action( Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK, array( $xpub_after, $id ) );

		$this->assertNotFalse( $scheduled );
	}


	/**
	 * @covers ::process_admin_options
	 */
	public function test_does_not_generate_new_addresses_when_xpub_does_not_change(): void {

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty(
			API_Interface::class,
			array()
		);

		$sut                   = new WC_Gateway_Bitcoin();
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

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_fresh_address_available_for_gateway' => Expected::once(
					function( WC_Gateway_Bitcoin $gateway ) {
						return true;
					}
				),
			)
		);

		$sut = new WC_Gateway_Bitcoin();

		$result = $sut->is_available();

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::is_available
	 */
	public function test_checks_for_available_address_for_availability_false(): void {

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_fresh_address_available_for_gateway' => Expected::once(
					function( WC_Gateway_Bitcoin $gateway ) {
						return false;
					}
				),
			)
		);

		$sut = new WC_Gateway_Bitcoin();

		$result = $sut->is_available();

		$this->assertFalse( $result );
	}

	/**
	 * @covers ::is_available
	 */
	public function test_checks_for_available_address_for_availability_uses_cache(): void {

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty( API_Interface::class );

		$sut = new class() extends WC_Gateway_Bitcoin {
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

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = null;

		$sut = new WC_Gateway_Bitcoin();

		$result = $sut->is_available();

		$this->assertFalse( $result );
	}

	/**
	 * @covers ::get_instructions
	 */
	public function test_get_instructions(): void {

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty( API_Interface::class );

		add_filter(
			'wc_gateway_bitcoin_form_fields',
			function( array $settings_fields ): array {
				$settings_fields['instructions']['default'] = 'Expected';
				return $settings_fields;
			}
		);

		$sut = new WC_Gateway_Bitcoin();

		$result = $sut->get_instructions();

		$this->assertEquals( 'Expected', $result );

	}

	/**
	 * @covers ::process_payment
	 */
	public function test_process_payment_returns_exception_on_bad_order_id(): void {

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty( API_Interface::class );

		$sut = new WC_Gateway_Bitcoin();

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

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = null;

		$sut = new WC_Gateway_Bitcoin();

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

		$GLOBALS['nullcorps_wc_gateway_bitcoin'] = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_fresh_address_for_order' => Expected::once(
					function( WC_Order $order ) {
						throw new Exception( 'This message will not be shown!' );
					}
				),
			)
		);

		$sut = new WC_Gateway_Bitcoin();

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
