<?php

/**
 * Settings page and settings section related functions
 */


/**
 * if accessed directly, exit.
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @package Woocommer_to_Vend
 * @subpackage WC_Vend_API
 * @author Apurba Podder <apurba.jnu@gmail.com>
 */
if (!class_exists('WVOS_Vend_Settings')) :
    require_once dirname(WCVEND) . '/vendor/class.settings-api.php';
    require_once dirname(WCVEND) . '/includes/class.wvos-vend-product-table.php';

    class WVOS_Vend_Settings
    {

        /**
         * Constructor function
         */
        public function __construct($name, $version)
        {
            $this->name = $name;
            $this->version = $version;
            $this->settings_api = new WeDevs_Settings_API;
            $this->vend_object = new WVOS_Vend_Functions($this->name, $this->version);
        }

        public function set_admin_notice()
        {


            if (!wvos_is_woocommerce_active()):
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e('Please Activate The WooCommerce Plugin, This plugin is dependent on WooCommerce plugin', 'wc-vend'); ?></p>
                </div>
                <?php
            endif;
        }

        /**
         * Enqueue JavaScripts and stylesheets
         */
        public function enqueue_scripts()
        {
            wp_enqueue_style($this->name, plugins_url('/assets/css/admin.css', WCVEND), '', $this->version, 'all');
            wp_enqueue_script($this->name, plugins_url('/assets/js/admin.js', WCVEND), array('jquery'), $this->version, true);
            wp_localize_script($this->name, 'ajax_data', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sync_vend_product'),
            ));

        }

        /**
         * sync vend product
         */

        public function sync_vend_data_to_woo()
        {

            $nonce = $_POST['nonce'];
            if (!wp_verify_nonce($nonce, 'sync_vend_product') && !is_user_logged_in()) return;
            $sync_object = new WVOS_Vend_WC_Sync($this->name, $this->version);
            if (array_key_exists('id',(array)$_POST)) {
                $vend_id = $_POST['id'];
                $post_object = wvos_get_product_by_meta('variable_vend_id', $vend_id);
                $post_id = '';
                if(isset($post_object)){

                    $post_id = key($post_object);
                }
                $post_type = $post_object[$post_id];
                if($post_type == 'product_variation'){
                    $post_id = wp_get_post_parent_id($post_id);
                }
                if( false == get_post_status($post_id) ){
                    $post_id= null;
                }
                if ($post_id != null) {
                    wp_die('This Id already Inserted, Post id is' . $post_id);
                }
                $Product_object = $sync_object->build_object($vend_id);
                wp_die($sync_object->insert_product($Product_object));
            }
            if (array_key_exists('data',(array)$_POST)) {
                $data = $_POST['data'];
                $insrted_id = array();
                if ($data !== 'all') {
                    $vend_ids = explode(',',$data);

                    foreach ($vend_ids as $id) {

                        $post_object = wvos_get_product_by_meta('variable_vend_id', $id);
                        $post_id = '';
                        if(isset($post_object)){

                            $post_id = key($post_object);
                        }
                        $post_type = $post_object[$post_id];
                        if($post_type == 'product_variation'){
                            $post_id = wp_get_post_parent_id($post_id);
                        }
                        if( false == get_post_status($post_id) ){
                            $post_id = null;
                        }

                        if  ( $post_id != null) {
                            continue;
                        }
                        $Product_object = $sync_object->build_object($id);
                        $sync_object->insert_product($Product_object);
                        $insrted_id[] = $id;
                    }

                } else {

                    $vend_ids = $this->vend_object->wv_get_allproducts();
                    $all_product_id = [];
                    foreach ($vend_ids as $data_object) {
                        if ($data_object['variant_parent_id'] != null) continue;
                        $all_product_id[] = $data_object['id'];
                    }
                    foreach ($all_product_id as $id) {

                        $post_object = wvos_get_product_by_meta('variable_vend_id', $id);
                        $post_id = '';
                        if(isset($post_object)){

                            $post_id = key($post_object);
                        }
                        $post_type = $post_object[$post_id];
                        if($post_type == 'product_variation'){
                            $post_id = wp_get_post_parent_id($post_id);
                        }

                        if( false == get_post_status($post_id) ){
                            $post_id = null;
                        }

                        if  ( $post_id != null) {
                            continue;
                        }

                        $Product_object = $sync_object->build_object($id);
                        $sync_object->insert_product($Product_object);
                        $insrted_id[] = $id;
                    }

                }
                wp_die(implode(', ', $insrted_id));
            }

        }

        /**
         * set vend hook
         */
        public function set_vend_hook()
        {
            $nonce = $_POST['nonce'];
            if (!wp_verify_nonce($nonce, 'sync_vend_product') && !is_user_logged_in()) return;
            $response = $this->vend_object->set_webhook();
            wp_die(json_encode($response));

        }

        public function admin_menu()
        {
            add_menu_page(__('Vend ', 'wc-vend'), __('Vend', 'wc-vend'), 'manage_options', 'vend-settings', array($this, 'plugin_page'), 'dashicons-editor-expand', '15.5');
            add_submenu_page('vend-settings', 'Product Status', 'Products', 'manage_options', 'wv_settings', array($this, 'product_page'));
//            add_submenu_page('vend-settings', 'Vend Notifications', 'Vend Notifications', 'manage_options', 'wv_vend_notifications', array($this, 'vend_notifications'));
        }


        public function product_page()
        {

            ?>

            <div class="loader loader--style3 hidden" title="2">

                <div class="text">
                    <h3><?php echo  esc_html('You May Leave this page, This Process is running on the Backgroung','wc-vend') ?></h3>
                </div>
                <svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                     width="50%" height="50%" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve">

                  <path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z">
                      <animateTransform attributeType="xml"
                                        attributeName="transform"
                                        type="rotate"
                                        from="0 25 25"
                                        to="360 25 25"
                                        dur="0.6s"
                                        repeatCount="indefinite"/>
                  </path>
                  </svg>
            </div>
            <?php

            $products = $this->vend_object->wv_get_allproducts();
            $build_products = [];


            foreach ($products as $product):
                $each_product = [];
                if ($product['variant_parent_id'] != null) continue;
                $product_id =  $product['id'];
                $Woo_product_id = wvos_get_product_by_meta('variable_vend_id',$product_id);
                $each_product['id'] =$product_id;
                $each_product['product_name'] = $product['name'];
                if(isset($Woo_product_id)){
                    $Woo_product_id = key($Woo_product_id);
                }
                if($product['has_variants']){
                    $Woo_product_id = wp_get_post_parent_id($Woo_product_id);
                }
                $each_product['post_id'] = ( get_post_status($Woo_product_id ) != false) ? $Woo_product_id : '';
                $each_product['product_image'] = (!empty($product['images'][0]->url)) ? $product['images'][0]->url : $product['image_url'];
                $each_product['fetch_data'] = '';
                $build_products[] = $each_product;
            endforeach;


            echo '<div class="wrap">';
            echo '<h3>Current webhooks</h3>';
            echo '<ul class="webhooks">';
            $webhooks = $this->vend_object->initialize()->manageWebhook();

            if(!empty($webhooks) && is_array($webhooks)){
                foreach ($webhooks as $webhook){
                    echo '<li>';
                    echo $webhook->url;
                    echo '</li>';
                }

            }else{
                echo '<li>'.esc_html('Click Set Webhook Button,It will do rest of the things for you','wc-vend'). '</li>';
            }
            echo '</ul>';
//            echo '<button id="set_webhook">'.esc_html("Set Webhook",'wc-vend').'</button>';
            echo '<div class="txp-table-div">';
            $product_table = new WVOS_Product_table();
            $product_table->views();
            $product_table->prepare_items($build_products);
            $product_table->display();
            echo '</div>';
            ?>
            </div>
            <?php


        }


        public function vend_notifications()
        {
            echo __FUNCTION__;
        }


        function admin_init()
        {

            //set the settings
            $this->settings_api->set_sections($this->get_settings_sections());
            $this->settings_api->set_fields($this->get_settings_fields());

            //initialize settings
            $this->settings_api->admin_init();
        }

        function get_settings_sections()
        {
            $sections = array(
                array(
                    'id' => 'wv_credentials_settings',
                    'title' => 'Credentials'
                ),

            );
            return $sections;
        }

        /**
         * Returns all the settings fields
         *
         * @return array settings fields
         */
        function get_settings_fields()
        {
            $settings_fields = array(

                'wv_credentials_settings' => array(
                    array(
                        'name' => 'key_val',
                        'label' => __('Access Key', 'wc-vend'),
                        'type' => 'text',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    array(
                        'name' => 'url_base',
                        'label' => __('Base URL', 'wc-vend'),
                        'type' => 'text',
                        'placeholder'=>'example.vendhq.com',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    array(
                        'name' => 'updated_interval',
                        'label' => __('Update Interval', 'wc-vend'),
                        'type' => 'number',
                        'min' => 0,
                        'max' => 100,
                        'sanitize_callback' => 'sanitize_text_field',
                        'desc'=>'Update between sync data and fetch data. Default 5'
                    ),

                    array(
                        'name' => 'add_customer',
                        'label' => __('Add Customer on Sale', 'wc-vend'),
                        'desc' => __('Do you want to add customer on vend from WordPress', 'wc-vend'),
                        'type' => 'select',
                        'default' => 'no',
                        'options' => array(
                            'yes' => 'Yes',
                            'no' => 'No'
                        )
                    ),

                    array(
                        'name' => 'customer_group_id',
                        'label' => __('Customer Group Id', 'wc-vend'),
                        'type' => 'text',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                ),
            );

            return $settings_fields;
        }

        function plugin_page()
        {
            echo '<div class="wrap">';
            $this->settings_api->show_navigation();
            $this->settings_api->show_forms();
            echo '</div>';
        }


    }

endif;