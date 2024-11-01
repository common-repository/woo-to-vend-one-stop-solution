<?php

/**
 * All API related functions
 */

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @package Woocommer_to_Vend
 * @subpackage WC_Vend_API
 * @author Apurba Podder <apurba.jnu@gmail.com>
 */
if( ! class_exists('WVOS_Vend_API') ) :

class WVOS_Vend_API {

    /**
     * Constructor function
     */
    public function __construct( $name, $version ) {
        $this->name = $name;
        $this->version = $version;
        $this->namespace = "{$this->name}/v{$this->version}";
        $this->woo_products = new WVOS_Vend_Public($name,$version);

    }
//http://7e3e3959.ngrok.io/?rest_route=/wc-vend/v1.0/sale-update
    public function register_rest_endpoints() {
        // when a product is updated in Vend
        register_rest_route( $this->namespace, '/product-update', array(
            'methods'   => 'POST',
            'callback'  => array( $this, 'callback_product_update' ),
        ) );
        // when an inventory is updated in Vend
        register_rest_route( $this->namespace, '/inventory-update', array(
            'methods'   => 'POST',
            'callback'  => array( $this, 'callback_inventory_update' ),
        ) );
        // when a sale is updated in Vend
        register_rest_route( $this->namespace, '/sale-update', array(
            'methods'   => 'POST',
            'callback'  => array( $this, 'callback_sale_update' ),
        ) );
    }

    // when a product is updated in Vend
    public function callback_product_update( $request, $cb='product' ) {
        $parameters = $request->get_params();
        $payload = json_decode( $parameters['payload'], true );
//        error_log(json_encode($payload));
        $this->woo_products->Woo_Product_update_product($payload);
        update_option('vend-product',$payload);
    }

    // when an inventory is updated in Vend
    public function callback_inventory_update( $request,$cb ='inventory' ) {
        $parameters = $request->get_params();
        $payload = json_decode( $parameters['payload'], true );
//	    error_log(json_encode($payload));
        $this->woo_products->Woo_Product_update_inventory($payload);
        update_option('vend-inventory',$payload);

    }

    // when a sale is updated in Vend
    public function callback_sale_update( $request, $cb = 'sell' ) {
        $parameters = $request->get_params();
        $payload = json_decode( $parameters['payload'], true );
//	    error_log(json_encode($payload));
        $this->woo_products->Woo_Product_update_sell($payload);
        update_option('vend-sale',$payload);
    }



}

endif;