<?php

/**
 * All public facing functions
 */

/**
 * if accessed directly, exit.
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @package Woocommer_to_Vend
 * @subpackage WC_Vend_Public
 * @author Apurba Podder <apurba.jnu@gmail.com>
 */
if (!class_exists('WVOS_Vend_Public')) :

    class WVOS_Vend_Public
    {


        public $meta_key = 'variable_vend_id';

        /**
         * Constructor function
         */
        public function __construct($name, $version)
        {
            $this->name = $name;
            $this->version = $version;
            $this->settings_val = get_option('wv_credentials_settings');
            $this->update_interval = 5;
            if(wvos_array_keys_exists(array('updated_interval','add_customer','customer_group_id'),(array)$this->settings_val)){
                $this->update_interval =  $this->settings_val['updated_interval'];
                $this->add_customer = $this->settings_val['add_customer'];
                $this->customer_group_id = $this->settings_val['customer_group_id'];
            }
            $this->vendObject = new WVOS_Vend_Functions($this->name, $this->version);
        }

        /**
         * Enqueue JavaScripts and stylesheets
         */
        public function enqueue_scripts()
        {
            wp_enqueue_style($this->name, plugins_url('/assets/css/public.css', WCVEND), '', $this->version, 'all');
            wp_enqueue_script($this->name, plugins_url('/assets/js/public.js', WCVEND), array('jquery'), $this->version, true);
        }


        public function build_Woo_product()
        {
            $this->vendObject->wv_get_allproducts();

        }

        public function sync_vend_inventory($order_id)
        {
            $order = wc_get_order($order_id);
            $vend_id = '';

            if (sizeof($items = $order->get_items()) > 0) {
                $_products = array();
                foreach ($items as $item) {
                    $item_id = $item->get_product_id();
                    $product_obj = new WC_Product_Factory();
                    $product = $product_obj->get_product($item_id);


                    if ($product->get_type() == 'variable'):
                        $variation_id = $item->get_variation_id();
                        $vend_id = wvos_get_product_vend_id($variation_id);

                    elseif ($product->get_type() == 'simple'):
                        $vend_id = wvos_get_product_vend_id($item_id);
                    endif;

                    if (!empty($vend_id)) {
                        $_products[] = array(
                            'product_id' => $vend_id,
                            'quantity' => $item->get_quantity(),
                            'price' => $item->get_subtotal() / $item->get_quantity()
                        );
                    }
                }
                $get_customer_id = $order->get_user_id();
                $get_user_details = get_user_meta($get_customer_id);
                $customer_vend_id = null;
                if(array_key_exists('vend_user_id',$get_user_details)){
                    $customer_vend_id = $get_user_details['vend_user_id'][0];
                }

                if($this->add_customer == 'yes' && $customer_vend_id == null){

                    $args = array(
                        'customer_group_id'     =>  $this->customer_group_id,
                        'first_name'            => $get_user_details['billing_first_name'][0],
                        'last_name'             => $get_user_details['billing_last_name'][0],
                        'email'                 => $get_user_details['billing_email'][0],
                        'phone'                 => $get_user_details['billing_phone'][0],
                        'physical_address_1'    => $get_user_details['shipping_address_1'][0],
                        'physical_address_2 '   => $get_user_details['shipping_address_2'][0],
                        'postal_postcode'       => $get_user_details['shipping_postcode'][0],
                        'wp_user_id'            => $get_customer_id,
                    );
                    $customer_vend_id = $this->vendObject->wv_add_customer_vend($args);
                    update_user_meta($get_customer_id,'vend_user_id',$customer_vend_id);
                }

                $source_name = get_bloginfo('name');
                $sales_id =$this->vendObject->wpp_sync_vend_inventory($_products,$source_name,$customer_vend_id);
            }
        }

        //Woo Product update function on inventory hook
        public function Woo_Product_update_inventory($args)
        {

            $return_products_id = $args['product_id'];
            //find the post by vend id
            $product_meta = wvos_get_product_by_meta($this->meta_key, $return_products_id);
            if ($product_meta === null) return;
            $product_id = key($product_meta);
            $product_type = $product_meta[$product_id];
            $current_time = current_time('H:i:s');

            if (isset($product_id) && !empty($product_id)) {

                if ($product_type != 'product') {
                    $product_id = wp_get_post_parent_id($product_id);
                    $update_time = get_post_meta($product_id, '_vend_update_time', true);
                    if (strtotime($current_time) - strtotime($update_time) < $this->update_interval) return;
                    update_post_meta($product_id, '_vend_update_time', $current_time);
                    $product_id = key($product_meta);
                } else {
                    $update_time = get_post_meta($product_id, '_vend_update_time', true);
                    if (strtotime($current_time) - strtotime($update_time) < $this->update_interval) return;
                    update_post_meta($product_id, '_vend_update_time', $current_time);
                }
                //build the data to push
                $return_products_meta = $args['product'];
                $title = $return_products_meta['name'];
                $description = $return_products_meta['description'];
                $sku = $return_products_meta['sku'];
                //$price = $return_products_meta['supply_price'];
                $count = $args['count'];

                $metas = array(
                    '_stock_status' => ($count > 0) ? 'instock' : 'outofstock',
                    '_sku' => $sku,
                    //    '_price' => $price,
                    '_manage_stock' => 'yes',
                    '_stock' => $count,
                );
                //update post meta
                foreach ($metas as $key => $value) {
                    update_post_meta($product_id, $key, $value);
                }

                if ($product_type != 'product') {
                    $product_id = wp_get_post_parent_id($product_id);
                }
                //update post title,description
                wp_update_post(
                    array(
                        'ID' => $product_id,
                        'post_title' => $title,
                        'post_content' => $description,
                    )
                );
            }

        }


        //Woo Product update function on product hook
        public function Woo_Product_update_product($args)
        {
//	        error_log(json_encode($args));
//            $args = get_option('vend-product');
            $title = $args['base_name'];
            $description = $args['description'];
            $return_products = $args['inventory'];

            foreach ($return_products as $return_product):
                $return_products_id = $return_product['product_id'];
                $product_meta = wvos_get_product_by_meta($this->meta_key, $return_products_id);
                if ($product_meta === null) return;
                $product_id = key($product_meta);
                $product_type = $product_meta[$product_id];
                $price = $return_product['attributed_cost'];
                $current_time = current_time('H:i:s');
                if ($product_type != 'product') {
                    $product_id = wp_get_post_parent_id($product_id);
                    $update_time = get_post_meta($product_id, '_vend_update_time', true);
                    if (strtotime($current_time) - strtotime($update_time) < $this->update_interval) return;
                    update_post_meta($product_id, '_vend_update_time', $current_time);
                    $product_id = key($product_meta);
                } else {
                    $update_time = get_post_meta($product_id, '_vend_update_time', true);
                    if (strtotime($current_time) - strtotime($update_time) < $this->update_interval) return;
                    update_post_meta($product_id, '_vend_update_time', $current_time);
                }


                if (isset($product_id) && !empty($product_id)) {


                    //build the data to push
                    $count = $return_product['count'];
                    $metas = array(
                        '_stock_status' => ($count > 0) ? 'instock' : 'outofstock',
                        '_price' => $price,
                        '_manage_stock' => 'yes',
                        '_stock' => $count,
                    );
                    //update post meta
                    foreach ($metas as $key => $value) {
                        update_post_meta($product_id, $key, $value);
                    }


                }
            endforeach;

            if ($product_type != 'product') {
                $product_id = wp_get_post_parent_id($product_id);
            }
            //update post title,description
            wp_update_post(
                array(
                    'ID' => $product_id,
                    'post_title' => $title,
                    'post_content' => $description,
                )
            );


        }

        //Woo Product update function on sell hook
        public function Woo_Product_update_sell($args)
        {

//            $args = get_option('vend-sale');
            $args = $args['register_sale_products'];
            $current_time = current_time('H:i:s');
            foreach ($args as $arg) {
                $return_products_id = $arg['product_id'];
                $product_meta = wvos_get_product_by_meta($this->meta_key, $return_products_id);
                if ($product_meta === null) return;
                $product_id = key($product_meta);
                $product_type = $product_meta[$product_id];
                if (isset($product_id) && !empty($product_id)) {
                    if ($product_type != 'product') {
                        $product_id = wp_get_post_parent_id($product_id);
                        $update_time = get_post_meta($product_id, '_vend_update_time', true);
                        if (strtotime($current_time) - strtotime($update_time) < $this->update_interval) return;
                        update_post_meta($product_id, '_vend_update_time', $current_time);
                        $product_id = key($product_meta);
                    } else {
                        $update_time = get_post_meta($product_id, '_vend_update_time', true);
                        if (strtotime($current_time) - strtotime($update_time) < $this->update_interval) return;
                        update_post_meta($product_id, '_vend_update_time', $current_time);
                    }
                    //$price = $args['price'];
                    $count = $arg['quantity'];
                    $current_stock = get_post_meta($product_id, '_stock', true);
                    $count = $this->vendObject->wpp_get_vend_count($return_products_id);
                    $metas = array(
                        //    '_price' => $price,
                        '_manage_stock' => 'yes',
                        '_stock' => $count,
                    );
                    //update post meta
                    foreach ($metas as $key => $value) {
                        update_post_meta($product_id, $key, $value);
                    }

                }
                continue;

            }


        }


        public function update_vend_with_woo($id, $after, $before)
        {

            $vend_object = new WVOS_Vend_Functions($this->name, $this->version);
            $get_post_type = get_post_type($id);

            if ($get_post_type == 'product') {

				//get meta
                $get_the_vend_id = wvos_get_product_vend_id($id);
                $get_the_parent_vend_id = wvos_get_product_vend_id($id, '_vend_paren_id');

                if ($get_the_vend_id != null || $get_the_parent_vend_id != null) {

                    $active = 1;
                    if ($after->post_status != 'publish') {
                        $active = 0;
                    }

                    $args = array(
                        'id' => $get_the_parent_vend_id,
                        'name' => $after->post_title,
                        'description' => $after->post_content,
                        'active' => $active,
                    );
                    $_product = wc_get_product($id);
                    if ($_product->get_type() == 'simple') {
                        $args['id'] = $get_the_vend_id;
                        $args['sku'] = get_post_meta($id, '_sku', true);
                    }

                    $vend_object->wv_add_product_vend($args, true);
                }

            }


        }


        public function callback_wpp_debug()
        {
            return __('What are you looking for?', 'wc-vend');
        }
    }

endif;