<?php

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses;

use BrianHenryIE\ColorLogger\ColorLogger;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\BitWasp_API
 */
class BitWasp_API_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::generate_address
	 * @covers ::__construct
	 */
	public function test_generate_addresses_xpub_1(): void {

		$logger = new ColorLogger();

		$sut = new BitWasp_API( $logger );

		$public_address = $_ENV['XPUB'];

		$result = $sut->generate_address( $public_address, 1 );

		$this->assertEquals( $_ENV['XPUB_DERIVED_1'], $result );
	}

	/**
	 * @covers ::generate_address
	 * @covers ::__construct
	 */
	public function test_generate_addresses_xpub_10(): void {

		$logger = new ColorLogger();

		$sut = new BitWasp_API( $logger );

		$public_address = $_ENV['XPUB'];

		$result = $sut->generate_address( $public_address, 10 );

		$this->assertEquals( $_ENV['XPUB_DERIVED_10'], $result );
	}

	/**
	 * @covers ::generate_address
	 * @covers ::__construct
	 */
	public function test_generate_addresses_zpub_1(): void {

		$logger = new ColorLogger();

		$sut = new BitWasp_API( $logger );

		$public_address = $_ENV['ZPUB'];

		$result = $sut->generate_address( $public_address, 1 );

		$this->assertEquals( $_ENV['ZPUB_DERIVED_1'], $result );
	}


	/**
	 * @covers ::generate_address
	 * @covers ::__construct
	 */
	public function test_generate_addresses_zpub_10(): void {

		$logger = new ColorLogger();

		$sut = new BitWasp_API( $logger );

		$public_address = $_ENV['ZPUB'];

		$result = $sut->generate_address( $public_address, 10 );

		$this->assertEquals( $_ENV['ZPUB_DERIVED_10'], $result );
	}

}
