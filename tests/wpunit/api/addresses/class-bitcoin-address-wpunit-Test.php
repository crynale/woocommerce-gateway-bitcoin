<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address
 */
class Bitcoin_Address_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * When using `update_post_meta()` the last modified time of the post does not change. This
	 * is a test to see will it update if we use `update_post( array( 'meta_input' => array() )` instead.
	 *
	 * @covers ::set_order_id
	 */
	public function test_last_modified_time_is_updated(): void {

		$bitcoin_address_factory = new Bitcoin_Address_Factory();

		$wallet = $this->makeEmpty( Bitcoin_Wallet::class );

		$bitcoin_address_post_id = $bitcoin_address_factory->save_new( 'address', 2, $wallet );

		/** @var \WP_Post $bitcoin_address_post */
		$bitcoin_address_post = get_post( $bitcoin_address_post_id );

		$last_modified_time_before = $bitcoin_address_post->post_modified_gmt;

		$bitcoin_address_object = $bitcoin_address_factory->get_by_post_id( $bitcoin_address_post_id );

		sleep( 1 );

		$bitcoin_address_object->set_order_id( 123 );

		/** @var \WP_Post $bitcoin_address_post */
		$bitcoin_address_post = get_post( $bitcoin_address_post_id );

		$last_modified_time_after = $bitcoin_address_post->post_modified_gmt;

		$this->assertNotEquals( $last_modified_time_before, $last_modified_time_after );

	}

	/**
	 * @covers ::get_order_id
	 */
	public function test_get_order_id_null_before_set(): void {

		$bitcoin_address_factory = new Bitcoin_Address_Factory();

		$wallet = $this->makeEmpty( Bitcoin_Wallet::class );

		$bitcoin_address_post_id = $bitcoin_address_factory->save_new( 'address', 2, $wallet );

		$sut = $bitcoin_address_factory->get_by_post_id( $bitcoin_address_post_id );

		$result = $sut->get_order_id();

		$this->assertNull( $result );

	}

	/**
	 * @covers ::get_order_id
	 */
	public function test_get_order_id_after_set(): void {

		$bitcoin_address_factory = new Bitcoin_Address_Factory();

		$wallet = $this->makeEmpty( Bitcoin_Wallet::class );

		$bitcoin_address_post_id = $bitcoin_address_factory->save_new( 'address', 2, $wallet );

		$sut = $bitcoin_address_factory->get_by_post_id( $bitcoin_address_post_id );

		$sut->set_order_id( 123 );

		$sut = $bitcoin_address_factory->get_by_post_id( $bitcoin_address_post_id );

		$result = $sut->get_order_id();

		$this->assertEquals( 123, $result );
	}

	/**
	 * @covers ::set_status
	 */
	public function test_set_status(): void {

		$bitcoin_address_factory = new Bitcoin_Address_Factory();

		$wallet = $this->makeEmpty( Bitcoin_Wallet::class );

		$bitcoin_address_post_id = $bitcoin_address_factory->save_new( 'address', 2, $wallet );

		$sut = $bitcoin_address_factory->get_by_post_id( $bitcoin_address_post_id );

		$sut->set_status( 'assigned' );

		$sut = $bitcoin_address_factory->get_by_post_id( $bitcoin_address_post_id );

		$result = $sut->get_status();

		$this->assertEquals( 'assigned', $result );
	}

	/**
	 * @covers ::get_balance
	 */
	public function test_get_balance_used(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'bh-bitcoin-address',
				'post_status' => 'used',
				'meta_input'  => array(
					Bitcoin_Address::BALANCE_META_KEY => '1.23456789',
				),
			)
		);

		$bitcoin_address_factory = new Bitcoin_Address_Factory();

		$sut = $bitcoin_address_factory->get_by_post_id( $post_id );

		$result = $sut->get_balance();

		$this->assertEquals( '1.23456789', $result );
	}

	/**
	 * @covers ::get_balance
	 */
	public function test_get_balance_unused(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'bh-bitcoin-address',
				'post_status' => 'unused',
			)
		);

		$bitcoin_address_factory = new Bitcoin_Address_Factory();

		$sut = $bitcoin_address_factory->get_by_post_id( $post_id );

		$result = $sut->get_balance();

		$this->assertEquals( '0.0', $result );
	}

	/**
	 * @covers ::get_balance
	 */
	public function test_get_balance_unknown(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'bh-bitcoin-address',
				'post_status' => 'unknown',
			)
		);

		$bitcoin_address_factory = new Bitcoin_Address_Factory();

		$sut = $bitcoin_address_factory->get_by_post_id( $post_id );

		$result = $sut->get_balance();

		$this->assertNull( $result );
	}

}
