<?php
/**
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\WooCommerce;

use Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler\Background_Jobs;
use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use WC_Order;
use WC_Payment_Gateway;

class WC_Gateway_Bitcoin extends WC_Payment_Gateway {
	use LoggerAwareTrait;

	/**
	 * @override WC_Settings_API::$id
	 *
	 * @var string
	 */
	public $id = 'bitcoin_gateway';

	protected API_Interface $api;

	protected string $instructions;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		// TODO: Set the logger externally.
		$this->setLogger( new NullLogger() );

		// TODO: Is there a better way to do this?
		$this->api = $GLOBALS['nullcorps_wc_gateway_bitcoin'];

		$this->icon               = apply_filters( 'woocommerce_offline_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = __( 'Bitcoin', 'nullcorps-wc-gateway-bitcoin' );
		$this->method_description = __( 'Allows Bitcoin payments. Orders are marked as "on-hold" when received, and marked paid once the specified number of confirmations are met', 'nullcorps-wc-gateway-bitcoin' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * When saving the options, if the xpub is changed, initiate a background job to generate addresses.
	 *
	 * @return bool
	 */
	public function process_admin_options() {

		$xpub_before = $this->get_xpub();

		/** @var bool $processed */
		$processed  = parent::process_admin_options();
		$xpub_after = $this->get_xpub();

		if ( $xpub_before !== $xpub_after ) {
			$hook = Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK;
			if ( ! as_has_scheduled_action( $hook ) ) {
				as_schedule_single_action( time(), $hook );
			}
		}

		return $processed;
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {

		$settings_fields = array(

			'enabled'               => array(
				'title'   => __( 'Enable/Disable', 'nullcorps-wc-gateway-bitcoin' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Bitcoin Payment', 'nullcorps-wc-gateway-bitcoin' ),
				'default' => 'yes',
			),

			'title'                 => array(
				'title'       => __( 'Title', 'nullcorps-wc-gateway-bitcoin' ),
				'type'        => 'text',
				'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'nullcorps-wc-gateway-bitcoin' ),
				'default'     => __( 'Bitcoin Payment', 'nullcorps-wc-gateway-bitcoin' ),
				'desc_tip'    => false,
			),

			'description'           => array(
				'title'       => __( 'Description', 'nullcorps-wc-gateway-bitcoin' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'nullcorps-wc-gateway-bitcoin' ),
				'default'     => __( 'Pay quickly and easily with Bitcoin', 'nullcorps-wc-gateway-bitcoin' ),
				'desc_tip'    => false,
			),

			'instructions'          => array(
				'title'       => __( 'Instructions', 'nullcorps-wc-gateway-bitcoin' ),
				'type'        => 'textarea',
				'description' => __( 'Additional instructions to appear alongside the payment address and amount, before payment has been made.', 'nullcorps-wc-gateway-bitcoin' ),
				'default'     => 'NB: Please only send Bitcoin, which always has the ticker BTC, not any of the many clones. If you send coins other than Bitcoin (e.g. Bitcoin Cash) then those coins will be lost and your order will still not be paid.',
				'desc_tip'    => false,
			),

			'xpub'                  => array(
				'title'       => __( 'xpub', 'nullcorps-wc-gateway-bitcoin' ),
				'type'        => 'text',
				'description' => __( 'The xpub/zpub (master public key) for your HD wallet, which we use to locally generate the addresses to pay to (no API calls). Find it in Electrum under menu:wallet/information. It looks like xbpub2394234924loadsofnumbers', 'nullcorps-wc-gateway-bitcoin' ),
				'default'     => '',
				'desc_tip'    => false,
			),
			// TODO: Show balance here.

			'fiat_currency'         => array(
				'title'       => __( 'fiat-currency', 'nullcorps-wc-gateway-bitcoin' ),
				'type'        => 'select',
				'description' => __( 'The fiat equivalent currency to use - USD, EUR or GBP', 'nullcorps-wc-gateway-bitcoin' ),
				'default'     => in_array( get_option( 'woocommerce_currency' ), array( 'USD', 'EUR', 'GBP' ), true ) ? get_option( 'woocommerce_currency' ) : 'USD',
				'desc_tip'    => false,
				'options'     => array(
					'USD' => 'USD',
					'EUR' => 'EUR',
					'GBP' => 'GBP',
				),
			),

			'btc_rounding_decimals' => array(
				'title'       => __( 'btc-rounding-decimals', 'nullcorps-wc-gateway-bitcoin' ),
				'type'        => 'text',
				'description' => __( 'Integer, somewhere around 6 or 7 is probably ideal currently.', 'nullcorps-wc-gateway-bitcoin' ),
				'default'     => '7',
				'desc_tip'    => false,
			),

			'price_margin'          => array(
				'title'       => __( 'price-margin', 'nullcorps-wc-gateway-bitcoin' ),
				'type'        => 'text',
				'description' => __( 'A percentage amount of shortfall from the shown price which will be accepted to allow for rounding errors. Recommend value between 0 and 3', 'nullcorps-wc-gateway-bitcoin' ),
				'default'     => '2',
				'desc_tip'    => false,
			),

		);

		$log_levels        = array( 'none', LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG );
		$log_levels_option = array();
		foreach ( $log_levels as $log_level ) {
			$log_levels_option[ $log_level ] = ucfirst( $log_level );
		}

		$settings_fields['log_level'] = array(
			'title'    => __( 'Log Level', 'text-domain' ),
			'label'    => __( 'Enable Logging', 'text-domain' ),
			'type'     => 'select',
			'options'  => $log_levels_option,
			'desc'     => __( 'Increasingly detailed levels of logs. ', 'nullcorps-wc-gateway-bitcoin' ) . '<a href="' . admin_url( 'admin.php?page=nullcorps-woocommerce-gateway-bitcoin-logs' ) . '">View Logs</a>',
			'desc_tip' => false,
			'default'  => 'info',
		);

		$this->form_fields = apply_filters( 'wc_gateway_bitcoin_form_fields', $settings_fields, $this->id );
	}


	/**
	 * Returns false when the gateway is not configured / has no addresses to use.
	 *
	 * @see WC_Payment_Gateways::get_available_payment_gateways()
	 *
	 * @return bool
	 */
	public function is_available() {

		return parent::is_available() && $this->api->is_fresh_address_available_for_gateway( $this->id );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id
	 *
	 * @return array{result:string, redirect:string}
	 * @throws \Exception
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			// This should never happen.
			throw new \Exception( 'Error creating order.' );
		}

		$btc_address = $this->api->get_fresh_address_for_order( $order );

		if ( empty( $btc_address ) ) {
			throw new \Exception( 'Unable to find Bitcoin address to send to. Please choose another payment method.' );
		}

		// Mark as on-hold (we're awaiting the payment).
		$order->update_status( 'on-hold', __( 'Awaiting Bitcoin payment to address: ', 'nullcorps-wc-gateway-bitcoin' ) . '<a target="_blank" href="https://www.blockchain.com/btc/address/' . $btc_address . "\">{$btc_address}</a>." );

		$order->add_meta_data( Order::BITCOIN_ADDRESS_META_KEY, $btc_address );

		// Record the exchange rate at the time the order was placed.
		$order->add_meta_data( Order::EXCHANGE_RATE_AT_TIME_OF_PURCHASE_META_KEY, $this->api->get_exchange_rate( $order->get_currency() ) );

		$btc_total = $this->api->convert_fiat_to_btc( $order->get_currency(), $order->get_total() );

		$order->add_meta_data( Order::ORDER_TOTAL_BITCOIN_AT_TIME_OF_PURCHASE_META_KEY, $btc_total );

		$order->save();

		// Schedule background check for payment.
		$hook = Background_Jobs::CHECK_UNPAID_ORDER_HOOK;
		$args = array( 'order_id' => $order_id );
		if ( ! as_has_scheduled_action( $hook, $args ) ) {
			$timestamp = time() + ( 5 * MINUTE_IN_SECONDS );
			as_schedule_single_action( $timestamp, $hook, $args );
		}

		// Reduce stock levels.
		wc_reduce_stock_levels( $order_id );

		// Remove cart.
		WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	public function get_instructions(): string {
		return $this->settings['instructions'] ?? '';
	}

	public function get_xpub(): string {
		return $this->settings['xpub'];
	}

	public function get_price_margin(): int {
		return $this->settings['price_margin'] ?? 2;
	}
}
