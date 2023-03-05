<?php
/**
 * @see https://docs.bitfinex.com/docs/rest-public
 * @see https://docs.bitfinex.com/v2/reference#rest-public-ticker
 *
 * @see https://api-pub.bitfinex.com/v2/conf/pub:list:pair:exchange
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Exchange_Rate;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Exchange_Rate_API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Bitfinex_API implements Exchange_Rate_API_Interface {
	use LoggerAwareTrait;

	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );
	}

	/**
	 * Fetch the current exchange from a remote API.
	 *
	 * @return string
	 */
	public function get_exchange_rate( string $currency ): string {

		$trading_pair = 'tBTC' . strtoupper( $currency );

		$url = "https://api-pub.bitfinex.com/v2/tickers?symbols={$trading_pair}";

		$request_response = wp_remote_get( $url );

		if ( is_wp_error( $request_response ) ) {
			throw new \Exception();
		}

		if ( 200 !== $request_response['response']['code'] ) {
			throw new \Exception();
		}

		$reponse_body = json_decode( $request_response['body'], true );

		// Multiple rates can be queried at the same time.

		/**
		 * SYMBOL                string The symbol of the requested ticker data,
		 * BID                   float  Price of last highest bid,
		 * BID_SIZE              float  Sum of the 25 highest bid sizes,
		 * ASK                   float  Price of last lowest ask,
		 * ASK_SIZE              float  Sum of the 25 lowest ask sizes,
		 * DAILY_CHANGE          float  Amount that the last price has changed since yesterday,
		 * DAILY_CHANGE_RELATIVE float  Relative price change since yesterday (*100 for percentage change),
		 * LAST_PRICE            float  Price of the last trade,
		 * VOLUME                float  Daily volume,
		 * HIGH                  float  Daily high,
		 * LOW                   float  Daily low
		 */
		$trading_pair_response = $reponse_body[0];

		$exchange_rate = $trading_pair_response[7];

		return $exchange_rate;
	}


}
