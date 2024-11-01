<?php
/*
Plugin Name: WooCommerce to Vend One stop Solution
Description: WooCommerce to Vend OneStop Solution
Plugin URI: https://themexplorer.org
Author: Apurba Podder
Author URI: http://apurba.me
Version: 1.0
Text Domain: wc-vend
Domain Path: /languages
*/

//Eroor checking
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(E_ALL);
/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class for the plugin
 * @package Woocommer_to_Vend
 * @author Apurba Podder <apurba.jnu@gmail.com>
 */
if( ! class_exists('WVOS_Vend') ) :

class WVOS_Vend {
	
	public static $_instance;
	public $plugin_name;
	public $plugin_version;

	public function __construct() {
		self::define();
		self::includes();
		self::hooks();
	}

	/**
	 * Define constants
	 */
	public function define(){
		define( 'WCVEND', __FILE__ );
		$this->plugin_name = 'wc-vend';
		$this->plugin_version = '1.0';
	}

	/**
	 * Includes files
	 */
	public function includes(){
		require_once dirname( WCVEND ) . '/includes/wc-vend-functions.php';
		require_once dirname( WCVEND ) . '/includes/class.wvos-vend-public.php';
		require_once dirname( WCVEND ) . '/includes/class.wvos-vend-api.php';
		require_once dirname( WCVEND ) . '/includes/class.wvos-vend-metabox.php';
		require_once dirname( WCVEND ) . '/includes/class.wvos-vend-settings.php';
		require_once dirname( WCVEND ) . '/includes/class.wvos-vend-functions.php';
		require_once dirname( WCVEND ) . '/includes/class.wvos-vend-sync.php';
        require_once dirname( WCVEND ) . '/vendor/VendAPI-1.5.2/src/VendAPI/VendAPI.php';
        require_once dirname( WCVEND ) . '/vendor/autoload.php';

	}


	/**
	 * Hooks
	 */
	public function hooks() {
		// i18n
		add_action( 'plugins_loaded', array( $this, 'i18n' ) );

		// public hooks
		$public = ( isset( $public ) && ! is_null( $public ) ) ? $public : new WVOS_Vend_Public( $this->plugin_name, $this->plugin_version );
		add_action( 'wp_enqueue_scripts', array( $public, 'enqueue_scripts' ) );
		add_action( 'woocommerce_order_status_completed', array( $public,'sync_vend_inventory' ) );
        add_action( 'post_updated', array($public,'update_vend_with_woo'), 10, 3 );
		add_shortcode( 'wpp_debug', array( $public, 'callback_wpp_debug' ) );

		
		$api = ( isset( $api ) && ! is_null( $api ) ) ? $api : new WVOS_Vend_API( $this->plugin_name, $this->plugin_version );
		add_action( 'rest_api_init', array( $api, 'register_rest_endpoints' ) );

		// metabox
		$metabox = ( isset( $metabox ) && ! is_null( $metabox ) ) ? $metabox : new WVOS_Vend_Metabox( $this->plugin_name, $this->plugin_version );
		add_action( 'woocommerce_product_options_pricing', array( $metabox,'simple_vend_id_field' ) );
		add_action( 'save_post', array( $metabox,'save_simple_vend_id' ) );
		add_action( 'woocommerce_variation_options_pricing', array( $metabox,'variation_vend_id_field' ), 10, 3 );
		add_action( 'save_post', array( $metabox,'save_variable_vend_id' ) );
		add_action( 'woocommerce_save_product_variation', array( $metabox,'save_variable_vend_id' ) );

        // settings hooks
        $settings = (isset($settings) && !is_null($settings)) ? $settings : new WVOS_Vend_Settings($this->plugin_name, $this->plugin_version);
        add_action('admin_menu', array($settings, 'admin_menu'));
        add_action('admin_init', array($settings, 'admin_init'));
        add_action('admin_enqueue_scripts', array($settings, 'enqueue_scripts'));
        add_action('wp_ajax_sync_vend_data', array($settings, 'sync_vend_data_to_woo'));
        add_action('wp_ajax_set_vend_hook', array($settings, 'set_vend_hook'));
        add_action('admin_notices', array($settings,'set_admin_notice'));
	}




	/**
	 * Internationalization
	 */
	public function i18n() {
		load_plugin_textdomain( 'wc-vend', false, dirname( plugin_basename( WCVEND ) ) . '/languages/' );
	}

	/**
	 * Cloning is forbidden.
	 */
	private function __clone() { }

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	private function __wakeup() { }

	/**
	 * Instantiate the plugin
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

endif;


WVOS_Vend::instance();
function wvos_myplugin_activate() {

    if(!wvos_is_woocommerce_active()){
        wp_die(_e('Please Install The WooCommerce Plugin, This plugin is dependent on WooCommerce plugin','wc-vend'));
    }

}
register_activation_hook( __FILE__, 'wvos_myplugin_activate' );