<?php
/**
 * Add a custom post type for Bitcoin address.
 * Will have statuses 'unused', 'used', 'assigned'.
 * Will have postmeta for:
 * * its derive path
 * * which order it is for
 * * its transactions
 * * its balance
 * Its parent will be its xpub.
 *
 * WP_List_Table can show all addresses and their orders and balances and last activity date.
 *
 * @see \BrianHenryIE\WP_Bitcoin_Gateway\Admin\Addresses_List_Table
 * @see \BrianHenryIE\WP_Bitcoin_Gateway\Admin\Wallets_List_Table
 *
 * @package brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\WP_Includes;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet;
use BrianHenryIE\WP_Bitcoin_Gateway\API_Interface;

/**
 * Register the custom post types with WordPress.
 *
 * @see register_post_type()
 * @see register_post_status()
 *
 * @see wp-admin/edit.php?post_type=bh-bitcoin-wallet
 * @see wp-admin/edit.php?post_type=bh-bitcoin-address
 */
class Post {

	/**
	 * Array of plugin objects to pass to post types.
	 *
	 * @var array{api:API_Interface} $plugin_objects
	 */
	protected array $plugin_objects = array();

	/**
	 * Constructor
	 *
	 * @param API_Interface $api The main plugin functions.
	 */
	public function __construct( API_Interface $api ) {
		$this->plugin_objects['api'] = $api;
	}

	/**
	 * Registers the bh-bitcoin-wallet post type and its statuses.
	 *
	 * @hooked init
	 */
	public function register_wallet_post_type(): void {

		$labels = array(
			'name'          => _x( 'Bitcoin Wallets', 'post type general name', 'bh-wp-bitcoin-gateway' ),
			'singular_name' => _x( 'Bitcoin Wallet', 'post type singular name', 'bh-wp-bitcoin-gateway' ),
			'menu_name'     => 'Bitcoin Wallets',
		);

		$args = array(
			'labels'         => $labels,
			'description'    => 'Wallets used with WooCommerce Bitcoin gateways.',
			'public'         => true,
			'menu_position'  => 8,
			'supports'       => array( 'title', 'thumbnail', 'excerpt', 'comments' ),
			'has_archive'    => false,
			'show_in_menu'   => false,
			'plugin_objects' => $this->plugin_objects,
		);

		register_post_type( BITCOIN_WALLET::POST_TYPE, $args );

		register_post_status(
			'active',
			array(
				'label'                     => _x( 'Active', 'post', 'bh-wp-bitcoin-gateway' ),
				'public'                    => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of Bitcoin wallets that are in use. */
				'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>' ),
			)
		);

		register_post_status(
			'inactive',
			array(
				'label'                     => _x( 'Inactive', 'post', 'bh-wp-bitcoin-gateway' ),
				'public'                    => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of Bitcoin wallets that have been created but are not currently in use. */
				'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>' ),
			)
		);
	}

	/**
	 * Registers the bh-bitcoin-address post type and its statuses.
	 *
	 * @hooked init
	 */
	public function register_address_post_type(): void {

		$labels = array(
			'name'          => _x( 'Bitcoin Addresses', 'post type general name', 'bh-wp-bitcoin-gateway' ),
			'singular_name' => _x( 'Bitcoin Address', 'post type singular name', 'bh-wp-bitcoin-gateway' ),
			'menu_name'     => 'Bitcoin Addresses',
		);
		$args   = array(
			'labels'         => $labels,
			'description'    => 'Addresses used with WooCommerce Bitcoin gateways.',
			'public'         => true,
			'menu_position'  => 8,
			'supports'       => array( 'title', 'thumbnail', 'excerpt', 'comments' ),
			'has_archive'    => false,
			'show_in_menu'   => false,
			'plugin_objects' => $this->plugin_objects,
		);
		register_post_type( BITCOIN_ADDRESS::POST_TYPE, $args );

		register_post_status(
			'unknown',
			array(
				'post_type'                 => array( Bitcoin_Address::POST_TYPE ),
				'label'                     => _x( 'Unknown', 'post' ),
				'public'                    => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of Bitcoin addresses whose status is unknown. */
				'label_count'               => _n_noop( 'Unknown <span class="count">(%s)</span>', 'Unknown <span class="count">(%s)</span>' ),
			)
		);

		register_post_status(
			'unused',
			array(
				'post_type'                 => array( Bitcoin_Address::POST_TYPE ),
				'label'                     => _x( 'Unused', 'post' ),
				'public'                    => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of Bitcoin addresses that have yet to be used. */
				'label_count'               => _n_noop( 'Unused <span class="count">(%s)</span>', 'Unused <span class="count">(%s)</span>' ),
			)
		);

		register_post_status(
			'assigned',
			array(
				'post_type'                 => array( Bitcoin_Address::POST_TYPE ),
				'label'                     => _x( 'Assigned', 'post' ),
				'public'                    => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of Bitcoin addresses that have been assigned. */
				'label_count'               => _n_noop( 'Assigned <span class="count">(%s)</span>', 'Assigned <span class="count">(%s)</span>' ),
			)
		);

		register_post_status(
			'used',
			array(
				'post_type'                 => array( Bitcoin_Address::POST_TYPE ),
				'label'                     => _x( 'Used', 'post' ),
				'public'                    => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of Bitcoin addresses that have been used. */
				'label_count'               => _n_noop( 'Used <span class="count">(%s)</span>', 'Used <span class="count">(%s)</span>' ),
			)
		);
	}

}
