<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       apurba.me
 * @since      1.0.0
 *
 * @package    Fb_Notify
 * @subpackage Fb_Notify/admin/partials
 */
/**
 * if accessed directly, exit.
 */
if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(WCVEND) . '/includes/class.wvos-vend-wp-table.php';

if(!class_exists('WVOS_Product_table')):

class WVOS_Product_table extends WVOS_WP_Table {



    public $found_data;



    function get_columns(){
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'product_name'  => 'Product Name',
            'post_id'       => 'Post Id',
            'product_image' => 'Product Image',
            'fetch_data'    => 'Fetch'
        );
        return $columns;
    }

    function prepare_items($items=null) {

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $items;

        $per_page = apply_filters('wv_vend_product_table',20);
        $current_page = $this->get_pagenum();

        $total_items = count($items);
        // only ncessary because we have sample data
        $this->found_data = array_slice($items,(($current_page-1)*$per_page),$per_page);

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page                     //WE have to determine how many items to show on a page
        ) );
        $this->items = $this->found_data;

    }

    function column_default($item, $column_name) {
        return $item[$column_name];
    }

    function column_post_id($item){
        return "<a class='txp-profile-link' target='_blank' href=".get_edit_post_link($item['post_id']).">".esc_html($item['post_id'])."</a>";
    }

    function extra_tablenav( $which ) {


        if ( $which == "top" ) {

            ?>
            <select name="vend_product_fetch" id="vend_product_fetch">
                <option value="">Bulk Actions</option>
                <option value="selected"><?php esc_html_e('Selected Item Only','wc-vend') ?></option>
                <option value="all"><?php esc_html_e('Fetch all','wc-vend') ?></option>
            </select>
            <?php
        }

        if ( $which == "bottom" ){
            //The code that goes after the table is there

        }


    }

    function column_product_image($item){
        $pic_url = esc_url($item['product_image']);
        return '<img width="100px" src='."$pic_url".' alt="'.esc_attr($item['product_name']).'">';
    }

    function column_fetch_data($item){

        if(!empty($item['post_id'])){
            return '';
        }
        return  '<a class="row-title button button-primary wv_sync" data-vend_id ="'. $item['id'].'"  href="#">Sync</a>';
    }


    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" class="wv_selected_id" name="wv_selected_id[]" value="%s" />', $item['id']
        );
    }

    function get_views()
    {
        $views = array(

        );


        return $views;
    }

}
endif;


