<?php
/**
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses;

use Exception;
use wpdb;


class Bitcoin_Wallet_Factory {

	/**
	 * Given a post_id,
	 *
	 * NB: post_name is 200 characters long. zpub is 111 characters.
	 *
	 * @param string $xpub
	 *
	 * @return int|null
	 */
	public function get_post_id_for_wallet( string $xpub ): ?int {

		$post_id = wp_cache_get( $xpub, Bitcoin_Wallet::POST_TYPE );

		if ( false !== $post_id ) {
			return (int) $post_id;
		}

		/** @var wpdb $wpdb */
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// @phpstan-ignore-next-line
		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name=%s", sanitize_title( $xpub ) ) );

		if ( ! is_null( $post_id ) ) {
			$post_id = intval( $post_id );
			wp_cache_add( $xpub, $post_id, Bitcoin_Wallet::POST_TYPE );
		}

		return $post_id;
	}

	/**
	 * Create a new Bitcoin_Wallet WordPress post for the provided address and optionally specify the associated gateway.
	 *
	 * @param string  $master_public_key The xpub/ypub/zpub of the wallet.
	 * @param ?string $gateway_id The WC_Payment_Gateway the wallet is being used with.
	 *
	 * @return int The wp_posts ID.
	 * @throws Exception
	 */
	public function save_new( string $master_public_key, ?string $gateway_id = null ): int {

		// TODO: Validate xpub, throw exception.

		$args = array();

		$args['post_title']   = $master_public_key;
		$args['post_status']  = ! is_null( $gateway_id ) ? 'active' : 'inactive';
		$args['post_excerpt'] = $master_public_key;
		$args['post_name']    = sanitize_title( $master_public_key ); // An indexed column.
		$args['post_type']    = Bitcoin_Wallet::POST_TYPE;

		// TODO: Add the gateway as meta.

		$post_id = wp_insert_post( $args, true );

		if ( is_wp_error( $post_id ) ) {
			throw new \Exception( 'Failed to save new wallet as wp_post' );
		}

		return $post_id;
	}

	/**
	 * Given the id of the wp_posts row storing the bitcoin wallet, return the typed Bitcoin_Wallet object.
	 *
	 * @param int $post_id WordPress wp_posts ID.
	 *
	 * @return Bitcoin_Wallet
	 * @throws Exception When the post_type of the post returned for the given post_id is not a Bitcoin_Address.
	 */
	public function get_by_post_id( int $post_id ): Bitcoin_Wallet {
		return new Bitcoin_Wallet( $post_id );
	}

}
