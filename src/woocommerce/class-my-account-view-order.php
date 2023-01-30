<?php
/**
 * Add the payment instructions to the order page.
 *
 * @see woocommerce/templates/myaccount/view-order.php
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce;

use BrianHenryIE\WC_Bitcoin_Gateway\API_Interface;
use WC_Order;

class My_Account_View_Order {

	protected API_Interface $api;

	const TEMPLATE_NAME = 'myaccount/view-order-bitcoin-instructions-status.php';

	public function __construct( API_Interface $api ) {
		$this->api = $api;
	}

	/**
	 *
	 * @hooked woocommerce_view_order
	 */
	public function print_status_instructions( int $order_id ): void {

		if ( ! $this->api->is_order_has_bitcoin_gateway( $order_id ) ) {
			return;
		}

		/** @var WC_Order $order */
		$order = wc_get_order( $order_id );

		$template_args = $this->api->get_formatted_order_details( $order, false );

		$template_args['template'] = self::TEMPLATE_NAME;

		wc_get_template( self::TEMPLATE_NAME, $template_args );
	}

}
