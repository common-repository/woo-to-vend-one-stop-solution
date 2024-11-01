<?php

/**
 * All public facing functions
 */

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Woocommer_to_Vend
 * @subpackage WC_Vend_Public
 * @author Apurba Podder <apurba.jnu@gmail.com>
 */

if ( ! class_exists( 'WVOS_Vend_Functions' ) ) :

	class WVOS_Vend_Functions {


		public $vendInitialize;
		public $webhookSetting;

		/**
		 * Constructor function
		 */
		public function __construct( $name, $version ) {

			$this->name           = $name;
			$this->version        = $version;
			$this->credentials    = get_option( 'wv_credentials_settings' );
			$this->vendInitialize = $this->wv_initialize_vend();
			$this->webhookOption  =  'wvos_webhook';
			$this->webhookSetting = get_option( $this->webhookOption );
		}


		public function wv_initialize_vend() {
			if ( wvos_array_keys_exists( array( 'url_base', 'key_val' ), (array) $this->credentials ) ) {
				$base_url     = $this->credentials['url_base'];
				$access_token = $this->credentials['key_val'];
				$vend         = new VendAPI\VendAPI( $base_url, 'Bearer', $access_token );
				if ( isset( $vend ) ) {
					return $vend;
				}
			}

			return false;
		}

		function wpp_sync_vend_inventory( $products, $source_id, $customer_id ) {
			$sale                         = new VendAPI\VendSale( null, $this->vendInitialize );
			$sale->source_id              = $source_id;
			$sale->customer_id            = $customer_id;
			$sale->status                 = 'CLOSED';
			$sale->register_sale_products = $products;
			$sale->save();

			return $sale->id;
		}

		function wpp_get_vend_count( $product_vend_id ) {
			$vend         = $this->vendInitialize;
			$product_meta = $vend->getProduct( $product_vend_id )->getInventory();

			return $product_meta;
		}

		function wv_add_product_vend( $arg = array(), $update = false ) {

			if ( $update && ! isset( $arg['id'] ) ) {
				return;
			}
			$new_product = new \VendAPI\VendProduct( null, $this->vendInitialize );
			if ( count( $arg ) ) {
				foreach ( $arg as $key => $value ) {
					$new_product->$key = $value;
				}
			}
			$new_product->save();

			if ( ! $update ) {
				return $new_product->id;
			}

			return $new_product->name;

		}


		function wv_add_customer_vend( $arg = array(), $update = false ) {

			$id = null;

			if ( $update && ! empty( $arg['customer_id'] ) ) {
				$id = $arg['customer_id'];
			}

			$new_customer = new \VendAPI\VendCustomer( null, $this->vendInitialize );
			if ( count( $arg ) ) {
				foreach ( $arg as $key => $value ) {
					$new_customer->$key = $value;
				}
			}
			$new_customer->save( $update, $id );

			if ( $update ) {
				return 'This ' . $new_customer->id . ' Is updated Successfully';
			}

			return $new_customer->id;

		}

		public function set_webhook() {
			// get current site url
			$site_url = site_url();
			$options=[];
			// get option value
			// if option value is not set or option value url is not current site url then run the hook
			if ( ! $this->webhookSetting || $this->webhookSetting['url'] != $site_url):
				$route            = site_url() . '?rest_route=/wc-vend/v1.0';
				$sale_update      = $route . '/sale-update';
				$product_update   = $route . '/product-update';
				$inventory_update = $route . '/inventory-update';
				$response_array   = [];
				$webhook_data     = array(

					'product_webhook'   => array(
						'url'    => $product_update,
						'active' => true,
						'type'   => 'product.update'
					),
					'inventory_webhook' => array(
						'url'    => $inventory_update,
						'active' => true,
						'type'   => 'inventory.update'
					),
					'sale_webhook'      => array(
						'url'    => $sale_update,
						'active' => true,
						'type'   => 'sale.update'
					)
				);

				foreach ( $webhook_data as $value ) {
					$data             = 'data=' . urlencode( json_encode( $value ) );
					$response         = $this->initialize()->manageWebhook( $data );
					$response_array[] = $response;
				}
				$options['url']=$site_url;
				update_option($this->webhookOption, $options);
				return $response_array;
			endif;

			return false;
		}


		public function initialize() {
			return $this->initialize = $this->wv_initialize_vend();
		}

		public function wv_get_allproducts() {

			$initialize = $this->initialize();
			if ( $initialize ) {
				$initialize->automatic_depage = true;
				$initialize->automatic_depage = true;
				$this->set_webhook( $initialize );

				return $initialize->get_all_products();
			}

			return;
		}


		public function callback_wpp_debug() {
			return __( 'What are you looking for?', 'wc-vend' );
		}


	}

endif;