<?php

namespace BrianHenryIE\WC_Bitcoin_Gateway\API;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\API\Settings
 */
class Settings_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::get_plugin_name
	 */
	public function test_get_plugin_name(): void {

		$sut = new Settings();

		$this->assertEquals( 'Bitcoin Gateway', $sut->get_plugin_name() );
	}

	/**
	 * @covers ::get_plugin_version
	 */
	public function test_get_plugin_version(): void {
		global $plugin_root_dir, $plugin_name_php;

		$plugin_file = $plugin_root_dir . DIRECTORY_SEPARATOR . 'bh-wc-bitcoin-gateway.php';

		$plugin_file_contents = (string) file_get_contents( $plugin_file );

		preg_match( '/\s+\*\s+Version:\s+(\d+\.\d+\.\d+)/', $plugin_file_contents, $output_array );

		$sut = new Settings();

		$this->assertEquals( $sut->get_plugin_version(), $output_array[1] );
	}

	/**
	 * @covers ::get_log_level
	 */
	public function test_get_log_level(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'woocommerce_bitcoin_gateway_settings', \WP_Mock\Functions::type( 'array' ) ),
				'return' => array( 'log_level' => 'notice' ),
			)
		);

		$sut = new Settings();

		$result = $sut->get_log_level();

		$this->assertEquals( 'notice', $result );
	}

	/**
	 * @covers ::get_log_level
	 */
	public function test_get_log_level_no_value_default_info(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'woocommerce_bitcoin_gateway_settings', \WP_Mock\Functions::type( 'array' ) ),
				'return' => array(),
			)
		);

		$sut = new Settings();

		$result = $sut->get_log_level();

		$this->assertEquals( 'info', $result );
	}

	/**
	 * @covers ::get_log_level
	 */
	public function test_get_log_level_bad_value_default_info(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'woocommerce_bitcoin_gateway_settings', \WP_Mock\Functions::type( 'array' ) ),
				'return' => array( 'log_level' => 'not-a-real-log-level' ),
			)
		);

		$sut = new Settings();

		$result = $sut->get_log_level();

		$this->assertEquals( 'info', $result );
	}


}
