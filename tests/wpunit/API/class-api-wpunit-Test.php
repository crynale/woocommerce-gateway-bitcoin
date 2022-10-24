<?php

namespace Nullcorps\WC_Gateway_Bitcoin\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Address;
use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Address_Factory;
use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Wallet;
use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage\Crypto_Wallet_Factory;
use Nullcorps\WC_Gateway_Bitcoin\Settings_Interface;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\WC_Gateway_Bitcoin;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\API\API
 */
class API_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::generate_new_addresses_for_wallet
	 */
	public function test_generate_addresses_for_gateway(): void {

		$test_xpub = 'zpub6n37hVDJHFyDG1hBERbMBVjEd6ws6zVhg9bMs5STo21i9DgDE9Z9KTedtGxikpbkaucTzpj79n6Xg8Zwb9kY8bd9GyPh9WVRkM55uK7w97K';

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$wallet                = $this->makeEmpty( Crypto_Wallet::class );
		$crypto_wallet_factory = $this->makeEmpty(
			Crypto_Wallet_Factory::class,
			array(
				'get_post_id_for_wallet' => Expected::once( 123 ),
				'get_by_post_id'         => Expected::once( $wallet ),
			)
		);

		$address                = $this->makeEmpty( Crypto_Address::class );
		$crypto_address_factory = $this->makeEmpty(
			Crypto_Address_Factory::class,
			array(
				'save_new'       => Expected::exactly( 5, 123 ),
				'get_by_post_id' => Expected::exactly( 5, $address ),
			)
		);

		$api = new API( $settings, $logger, $crypto_wallet_factory, $crypto_address_factory );

		$result = $api->generate_new_addresses_for_wallet( $test_xpub, 5 );

	}

}
