<?php
/**
 * All meta box and meta fields related functions
 */

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @package Woocommer_to_Vend
 * @subpackage WC_Vend_Metabox
 * @author Apurba Podder <apurba.jnu@gmail.com>
 */

if ( ! class_exists('WVOS_Vend_Metabox') ) :

    class WVOS_Vend_Metabox  {



        public function __construct( $name, $version ) {
            $this->name = $name;
            $this->version = $version;
        }


        public function simple_vend_id_field() {
            global $post;
            $variation_id = $post->ID;
            $value = get_post_meta( $variation_id, 'variable_vend_id', true );
            woocommerce_wp_text_input( array(
                'id'            => "variable_vend_id{$variation_id}",
                'name'          => "variable_vend_id[{$variation_id}]",
                'value'         => $value,
                'placeholder'   => __( '022894d1-fdb8-11e7-e6e7-f81decb0c91b', 'woocommerce', 'woocommerce' ),
                'label'         => __( 'Vend ID', 'woocommerce' ),
                'desc_tip'      => true,
                'description'   => __( 'Leave it empty if you don\'t want to sync it with Vend' ),
                'type'          => 'text',
                'wrapper_class' => 'form-row form-row-firstx hide_if_variation_virtual',
            ) );
        }

        public function save_simple_vend_id( $post_id ) {
            if( isset( $_POST['variable_vend_id'] )) {
                $_variations = $_POST['variable_vend_id'];
                foreach ( $_variations as $variation_id => $vend_key ) {
                    update_post_meta( $variation_id, 'variable_vend_id', $_variations[ $variation_id ] );
                }
            }
        }

        public function variation_vend_id_field( $loop, $variation_data, $variation ) {
            $variation_id = $variation->ID;
            $value = get_post_meta( $variation_id, 'variable_vend_id', true );
            woocommerce_wp_text_input( array(
                'id'            => "variable_vend_id{$variation_id}",
                'name'          => "variable_vend_id[{$variation_id}]",
                'value'         => $value,
                'placeholder'   => __( 'Product ID from Vend. E.g. 022894d1-fdb8-11e7-e6e7-f81decb0c91b', 'woocommerce', 'woocommerce' ),
                'label'         => __( 'Vend ID', 'woocommerce' ),
                'desc_tip'      => true,
                'description'   => __( 'Leave it empty if you don\'t want to sync it with Vend' ),
                'type'          => 'text',
                'wrapper_class' => 'form-row form-row-firstx hide_if_variation_virtual',
            ) );
        }

        public function save_variable_vend_id( $post_id ) {
            if( isset( $_POST['variable_vend_id'] ) ) {
                $_variations = $_POST['variable_vend_id'];
                foreach ( $_variations as $variation_id => $vend_key ) {
                    update_post_meta( $variation_id, 'variable_vend_id', $_variations[ $variation_id ] );
                }
            }
        }
    }

endif;








