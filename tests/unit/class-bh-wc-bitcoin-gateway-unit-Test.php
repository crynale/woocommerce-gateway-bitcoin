<?php
/**
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WC_Bitcoin_Gateway\Admin\Dependencies_Notice;
use BrianHenryIE\WC_Bitcoin_Gateway\Admin\Plugins_Page;
use BrianHenryIE\WC_Bitcoin_Gateway\Admin\Register_List_Tables;
use BrianHenryIE\WC_Bitcoin_Gateway\Frontend\Frontend_Assets;
use BrianHenryIE\WC_Bitcoin_Gateway\Integrations\Woo_Cancel_Abandoned_Order;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Admin_Order_UI;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Email;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\HPOS;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Order;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Payment_Gateways;
use BrianHenryIE\WC_Bitcoin_Gateway\WP_Includes\I18n;
use WP_Mock\Matcher\AnyInstance;

/**
 * Class BH_WC_Bitcoin_Gateway_Unit_Test
 *
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\BH_WC_Bitcoin_Gateway
 */
class BH_WC_Bitcoin_Gateway_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::set_locale
	 */
	public function test_set_locale_hooked(): void {

		\WP_Mock::expectActionAdded(
			'init',
			array( new AnyInstance( I18n::class ), 'load_plugin_textdomain' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_admin_hooks
	 */
	public function test_admin_hooks(): void {

		$this->markTestSkipped( 'Not using Admin class right now' );

		\WP_Mock::expectActionAdded(
			'admin_enqueue_scripts',
			array( new AnyInstance( Admin::class ), 'enqueue_styles' )
		);

		\WP_Mock::expectActionAdded(
			'admin_enqueue_scripts',
			array( new AnyInstance( Admin::class ), 'enqueue_scripts' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}


	/**
	 * @covers ::define_plugins_page_hooks
	 */
	public function test_plugins_page_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'plugin_action_links_bh-wc-bitcoin-gateway/bh-wc-bitcoin-gateway.php',
			array( new AnyInstance( Plugins_Page::class ), 'add_settings_action_link' )
		);

		\WP_Mock::expectFilterAdded(
			'plugin_action_links_bh-wc-bitcoin-gateway/bh-wc-bitcoin-gateway.php',
			array( new AnyInstance( Plugins_Page::class ), 'add_orders_action_link' )
		);

		\WP_Mock::expectFilterAdded(
			'plugin_row_meta',
			array( new AnyInstance( Plugins_Page::class ), 'split_author_link_into_two_links' ),
			10,
			2
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => 'bh-wc-bitcoin-gateway/bh-wc-bitcoin-gateway.php',
			)
		);
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_frontend_hooks
	 */
	public function test_frontend_hooks(): void {

		\WP_Mock::expectActionAdded(
			'wp_enqueue_scripts',
			array( new AnyInstance( Frontend_Assets::class ), 'enqueue_styles' )
		);

		\WP_Mock::expectActionAdded(
			'wp_enqueue_scripts',
			array( new AnyInstance( Frontend_Assets::class ), 'enqueue_scripts' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_email_hooks
	 */
	public function test_email_hooks(): void {

		\WP_Mock::expectActionAdded(
			'woocommerce_email_before_order_table',
			array( new AnyInstance( Email::class ), 'print_instructions' ),
			10,
			3
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_payment_gateway_hooks
	 */
	public function test_payment_gateway_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'woocommerce_payment_gateways',
			array( new AnyInstance( Payment_Gateways::class ), 'add_to_woocommerce' )
		);

		\WP_Mock::expectActionAdded(
			'woocommerce_blocks_payment_method_type_registration',
			array( new AnyInstance( Payment_Gateways::class ), 'register_woocommerce_block_checkout_support' )
		);

		\WP_Mock::expectFilterAdded(
			'woocommerce_available_payment_gateways',
			array( new AnyInstance( Payment_Gateways::class ), 'add_logger_to_gateways' ),
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_order_hooks
	 */
	public function test_define_order_hooks(): void {

		\WP_Mock::expectActionAdded(
			'woocommerce_order_status_changed',
			array( new AnyInstance( Order::class ), 'schedule_check_for_transactions' ),
			10,
			3
		);

		\WP_Mock::expectActionAdded(
			'woocommerce_order_status_changed',
			array( new AnyInstance( Order::class ), 'unschedule_check_for_transactions' ),
			10,
			3
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_action_scheduler_hooks
	 */
	public function test_define_action_scheduler_hooks(): void {

		\WP_Mock::expectActionAdded(
			Background_Jobs::CHECK_UNPAID_ORDER_HOOK,
			array( new AnyInstance( Background_Jobs::class ), 'check_unpaid_order' )
		);

		\WP_Mock::expectActionAdded(
			Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK,
			array( new AnyInstance( Background_Jobs::class ), 'generate_new_addresses' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_admin_order_ui_hooks
	 */
	public function test_define_admin_order_ui_hooks(): void {

		\WP_Mock::expectActionAdded(
			'add_meta_boxes',
			array( new AnyInstance( Admin_Order_UI::class ), 'register_address_transactions_meta_box' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_dependencies_admin_notice_hooks
	 */
	public function test_define_dependencies_admin_notice_hooks(): void {

		\WP_Mock::expectActionAdded(
			'admin_notices',
			array( new AnyInstance( Dependencies_Notice::class ), 'print_dependencies_notice' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_wp_list_page_ui_hooks
	 */
	public function test_define_wp_list_page_ui_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'wp_list_table_class_name',
			array( new AnyInstance( Register_List_Tables::class ), 'register_bitcoin_address_table' ),
			10,
			2
		);

		\WP_Mock::expectFilterAdded(
			'wp_list_table_class_name',
			array( new AnyInstance( Register_List_Tables::class ), 'register_bitcoin_wallet_table' ),
			10,
			2
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}


	/**
	 * @covers ::define_integration_woo_cancel_abandoned_order_hooks
	 */
	public function test_define_integration_woo_cancel_abandoned_order_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'woo_cao_gateways',
			array( new AnyInstance( Woo_Cancel_Abandoned_Order::class ), 'enable_cao_for_bitcoin' )
		);

		\WP_Mock::expectFilterAdded(
			'woo_cao_before_cancel_order',
			array( new AnyInstance( Woo_Cancel_Abandoned_Order::class ), 'abort_canceling_partially_paid_order' ),
			10,
			3
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_woocommerce_features_hooks
	 */
	public function test_define_woocommerce_features_hooks(): void {

		\WP_Mock::expectActionAdded(
			'before_woocommerce_init',
			array( new AnyInstance( HPOS::class ), 'declare_compatibility' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}
}
