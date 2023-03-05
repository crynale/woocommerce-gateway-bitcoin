<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Settings_Interface;
use Codeception\Stub\Expected;
use WC_Gateway_BACS;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Payment_Gateways
 */
class Payment_Gateways_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::add_to_woocommerce
	 */
	public function test_add_to_woocommerce(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_server_has_dependencies' => Expected::once( true ),
			)
		);

		$sut = new Payment_Gateways( $api, $settings, $logger );

		$result = $sut->add_to_woocommerce( array() );

		$this->assertEquals( Bitcoin_Gateway::class, $result[0] );
	}

}
