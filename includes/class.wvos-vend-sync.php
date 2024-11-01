<?php

/**
 * Created by PhpStorm.
 * User: apurba
 * Date: 09/02/18
 * Time: 15:53
 */

/**
 * if accessed directly, exit.
 */
if (!defined('ABSPATH')) {
    exit;
}

if(!class_exists('WVOS_Vend_WC_Sync')):
class WVOS_Vend_WC_Sync
{


    /**
     * Vend_WC_Sync constructor.
     * @param $name
     * @param $version
     * @param $id Only accept Parent product Vend ID
     */
        public function __construct($name, $version)
        {
            $this->name = $name;
            $this->version = $version;
        }


    /**
     * @param $id
     * @return array
     * Prepare Vend data to enter as WooCommerce Product
     */
        function build_object($id){
            $vend_object = new WVOS_Vend_Functions($this->name, $this->version);
            $products = $vend_object->wv_get_allproducts();
            $get_all_items = [];
            $convert_vend_to_woo = [];
            //select all product of current vend id
            foreach ($products as $product){
                if ($product['id'] == $id || $product['variant_parent_id'] == $id){
                    $get_all_items[] = $product;
                }
                if ($product['id'] == $id){
                    $convert_vend_to_woo['parent_id'] = $id;
                    $convert_vend_to_woo['producy_type'] = (bool)$product['has_variants'];

                    if(array_key_exists('images',$product)&& !empty($product['images'])){
                        $convert_vend_to_woo['feature_image'] = $product['images'][0]->url;
                    }
                }
            }

            foreach ( $get_all_items as $get_all_item ){

                $variants = array();
                $variant_options = $get_all_item['variant_options'];

                foreach (  $variant_options as $variant){
                    $variants[$variant->name] = $variant->value;
                }

                $convert_vend_to_woo['name'] = $get_all_item['handle'];
                $convert_vend_to_woo['sku']  = $get_all_item['sku'];
                $convert_vend_to_woo['description'] = $get_all_item['description'];
                $convert_vend_to_woo['categories'] = array_column($get_all_item['categories'],'name');
                $convert_vend_to_woo['available_attributes'] = array_column($get_all_item['variant_options'],'name');
                $seperate_variation = array(

                    'vend_id' => $get_all_item['id'],
                    'attributes' =>  $variants,
                    'price' => $get_all_item['price_including_tax'],
                    'stock' =>  $vend_object->wpp_get_vend_count($get_all_item['id']),
                    'sku'   => $get_all_item['sku']
                );
                $convert_vend_to_woo['variations'][] = $seperate_variation;

            }
            return $convert_vend_to_woo;
        }


        function add_product_image( $image_url,$alt=null, $post_id  ){
            $upload_dir = wp_upload_dir();
            $image_data = file_get_contents($image_url);
            $filename = basename($image_url);
            if($alt == null){
                $alt =  sanitize_file_name($filename);
            }
            if(wp_mkdir_p($upload_dir['path']))     $file = $upload_dir['path'] . '/' . $filename;
            else                                    $file = $upload_dir['basedir'] . '/' . $filename;
            file_put_contents($file, $image_data);

            $wp_filetype = wp_check_filetype($filename, null );
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' =>$alt,
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
            $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
            $res2= set_post_thumbnail( $post_id, $attach_id );
        }

    /**
     * @param $product_data
     * @return bool
     */
        function insert_simple_product ($product_data)
        {

            $post = '';
            //If same hande/title Product not exist
            if(get_page_by_title($product_data['name'],'OBJECT','product') == NULL )
                $post   = array( // Set up the basic post data to insert for our product

                    'post_author'  => get_current_user_id(),
                    'post_content' => ($product_data['description'] == null)?'':$product_data['description'],
                    'post_status'  => 'publish',
                    'post_title'   => $product_data['name'],
                    'post_parent'  => '',
                    'post_type'    => 'product'
                );

            $post_id = wp_insert_post($post); // Insert the post returning the new post id
            if (!$post_id) // If there is no post id something has gone wrong so don't proceed
            {
                return false;
            }

            update_post_meta($post_id, '_sku', $product_data['sku']); // Set its SKU
            update_post_meta( $post_id,'_visibility','visible'); // Set the product to visible, if not it won't show on the front end

            wp_set_object_terms($post_id, $product_data['categories'], 'product_cat'); // Set up its categories
            wp_set_object_terms($post_id, 'simple', 'product_type'); // Set it to a variable product type
            $stock_status = ((int)$product_data['variations'][0]['stock']>1)?'instock' : 'outofstock';
            update_post_meta($post_id, 'variable_vend_id', $product_data['variations'][0]['vend_id']);
            update_post_meta($post_id, '_sale_price', $product_data['variations'][0]['price']);
            update_post_meta($post_id, '_price', $product_data['variations'][0]['price']);
            update_post_meta($post_id, '_regular_price', $product_data['variations'][0]['price']);
            update_post_meta($post_id, '_manage_stock', 'yes');
            update_post_meta($post_id, '_stock', $product_data['variations'][0]['stock']);
            update_post_meta($post_id, '_stock_status', $stock_status);
            if(array_key_exists('feature_image',$product_data)){

                $this->add_product_image($product_data['feature_image'],$product_data['name'],$post_id);
            }
            return $post_id;

        }

    /**
     * @param $product_data
     * @return bool
     */
        function insert_variable_product ($product_data)
        {

            $post = '';

            if(get_page_by_title($product_data['name'],'OBJECT','product') == NULL )
                $post   = array( // Set up the basic post data to insert for our product

                    'post_author'  => get_current_user_id(),
                    'post_content' => ($product_data['description'] == null)?'':$product_data['description'],
                    'post_status'  => 'publish',
                    'post_title'   => $product_data['name'],
                    'post_parent'  => '',
                    'post_type'    => 'product'
                );

            $post_id = wp_insert_post($post); // Insert the post returning the new post id
            if (!$post_id) // If there is no post id something has gone wrong so don't proceed
            {
                return false;
            }

            update_post_meta($post_id, '_sku', $product_data['sku']); // Set its SKU
            update_post_meta( $post_id,'_visibility','visible'); // Set the product to visible, if not it won't show on the front end

            update_post_meta($post_id,'_vend_paren_id',$product_data['parent_id']);
            wp_set_object_terms($post_id, $product_data['categories'], 'product_cat'); // Set up its categories
            wp_set_object_terms($post_id, 'variable', 'product_type'); // Set it to a variable product type

            $this->insert_product_attributes($post_id, $product_data['available_attributes'], $product_data['variations']); // Add attributes passing the new post id, attributes & variations
            $this->insert_product_variations($post_id, $product_data['variations']); // Insert variations passing the new post id & variations
            $this->add_product_image($product_data['feature_image'],$product_data['name'],$post_id);
            return $post_id;
        }

    /***
     * @param $post_id
     * @param $available_attributes
     * @param $variations
     */
        function insert_product_attributes ($post_id, $available_attributes, $variations)
        {



            $product_attributes = array();

            foreach ($available_attributes as  $attribute_no =>$attribute) // Go through each attribute
            {


                $values = array(); // Set up an array to store the current attributes values.


                foreach ($variations as $variation) // Loop each variation in the file
                {
                    $attribute_keys = array_keys($variation['attributes']); // Get the keys for the current variations attributes

                    foreach ($attribute_keys as $key) // Loop through each key
                    {

                        if ($key === $attribute) // If this attributes key is the top level attribute add the value to the $values array
                        {

                            $values[] = $variation['attributes'][$key];

                        }
                    }
                }

                // Essentially we want to end up with something like this for each attribute:
                // $values would contain: array('small', 'medium', 'medium', 'large');

                $values = array_unique($values); // Filter out duplicate values
                $values = implode('|',$values);



                $product_attributes[strtolower($attribute)] = array(

                    'name' => strtolower($attribute),
                    'value' => $values,
                    'position' => $attribute_no,
                    'is_visible' => 1,
                    'is_variation' => 1,
                    'is_taxonomy' => 0
                );

            }


            update_post_meta($post_id, '_product_attributes', $product_attributes);

        }

    /**
     * @param $post_id
     * @param $variations
     */
        function insert_product_variations ($post_id, $variations)
        {
            foreach ($variations as $index => $variation)
            {
                $variation_post = array( // Setup the post data for the variation

                    'post_title'  => 'Variation #'.$index.' of '.count($variations).' for product#'. $post_id,
                    'post_name'   => 'product-'.$post_id.'-variation-'.$index,
                    'post_status' => 'publish',
                    'post_parent' => $post_id,
                    'post_type'   => 'product_variation',
                    'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index
                );


                $variation_post_id = wp_insert_post($variation_post); // Insert the variation

                foreach ($variation['attributes'] as $attribute => $value) // Loop through the variations attributes
                {

                    update_post_meta($variation_post_id, 'attribute_'.strtolower($attribute), $value);

                    // Again without variables: update_post_meta(25, 'attribute_pa_size', 'small')
                }


                $stock_status = ((int)$variation['stock']>1)?'instock' : 'outofstock';
                update_post_meta($variation_post_id, 'variable_vend_id', $variation['vend_id']);
                update_post_meta($variation_post_id, '_price', $variation['price']);
                update_post_meta($variation_post_id, '_regular_price', $variation['price']);
                update_post_meta($variation_post_id, '_manage_stock', 'yes');
                update_post_meta($variation_post_id, '_stock', $variation['stock']);
                update_post_meta($variation_post_id, '_stock_status', $stock_status);
                update_post_meta($variation_post_id, '_sku', $variation['sku']);

            }
        }

    /**
     * @param $convert_vend_to_woo
     */

        function insert_product($convert_vend_to_woo){
            if(array_key_exists('producy_type',(array)$convert_vend_to_woo) && $convert_vend_to_woo['producy_type'] == true){
               return $this->insert_variable_product($convert_vend_to_woo);
            }else{
                return $this->insert_simple_product($convert_vend_to_woo);
            }

        }



}
endif;