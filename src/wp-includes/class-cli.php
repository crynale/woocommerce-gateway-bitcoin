<?php
/**
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\WP_Includes;

use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WC_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use BrianHenryIE\WC_Bitcoin_Gateway\Settings_Interface;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Order;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;
use WP_CLI;
use WP_CLI_Command;

class CLI extends WP_CLI_Command {
	use LoggerAwareTrait;

	/**
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * All CLI functions call into an instance of the API_Interface.
	 *
	 * @var API_Interface $api The main plugin API definition.
	 */
	protected API_Interface $api;

	/**
	 * @param API_Interface      $api The main plugin functions.
	 * @param Settings_Interface $settings The plugin's settings.
	 * @param LoggerInterface    $logger A PSR logger.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {
		parent::__construct();
		$this->setLogger( $logger );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Generate new addresses for all gateways.
	 *
	 * `wp bh-bitcoin generate-new-addresses --debug=bh-wc-bitcoin-gateway`
	 *
	 * @param array<int|string, string> $args Takes no arguments.
	 */
	public function generate_new_addresses( array $args ): void {

		$result = $this->api->generate_new_addresses();
		$this->api->check_new_addresses_for_transactions();

		// TODO: Print a table of new addresses and their status.
		// Print a summary of the table.

		WP_CLI::log( 'Finished generate-new-addresses.' );
	}

	/**
	 * Query the blockchain for updates for a specific address (xpub or post_id) or order id.
	 *
	 * `wp bh-bitcoin update-address a1b2c3e4 --debug=bh-wc-bitcoin-gateway`
	 *
	 * @param array<int|string, string> $args The address.
	 *
	 * @throws WP_CLI\ExitException When given input that does not match a known xpub, or post_id for a bitcoin address or relevant WooCommerce order.
	 */
	public function update_address( array $args ): void {

		$input = $args[0];

		$address_factory = new Bitcoin_Address_Factory();

		try {
			switch ( get_post_type( intval( $input ) ) ) {
				case Bitcoin_Address::POST_TYPE:
					$this->logger->debug( 'CLI input was a bh-bitcoin-address post_id: ' . $input );
					$bitcoin_address = new Bitcoin_Address( intval( $input ) );
					break;
				case 'shop_order':
					$input = intval( $input );
					$this->logger->debug( 'CLI input was WooCommerce shop_order:' . $input );
					$order = wc_get_order( $input );
					if ( ( $order instanceof WC_Order ) && $this->api->is_order_has_bitcoin_gateway( $input ) ) {
						$input                   = $order->get_meta( Order::BITCOIN_ADDRESS_META_KEY );
						$bitcoin_address_post_id = $address_factory->get_post_id_for_address( $input );
						$bitcoin_address         = $address_factory->get_by_post_id( $bitcoin_address_post_id );
					} else {
						WP_CLI::error( "Order {$input} is not a Bitcoin order" );
						return;
					}
					break;
				default:
					$bitcoin_address_post_id = $address_factory->get_post_id_for_address( $input );
					$bitcoin_address         = $address_factory->get_by_post_id( $bitcoin_address_post_id );
			}

			$result = $this->api->query_api_for_address_transactions( $bitcoin_address );

			WP_CLI::log( wp_json_encode( $result ) );

			WP_CLI::log( 'Finished update-address.' );

		} catch ( \Exception $exception ) {
			WP_CLI::error( $exception->getMessage() );
		}
	}

}
